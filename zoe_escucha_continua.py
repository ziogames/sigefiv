import os
import time
import wave
import tempfile

# ============================================================
# CUDA: DLL de NVIDIA
# ============================================================

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

CUDA_DIRS = [
    os.path.join(BASE_DIR, ".venv-supertonic", "Lib", "site-packages", "nvidia", "cublas", "bin"),
    os.path.join(BASE_DIR, ".venv-supertonic", "Lib", "site-packages", "nvidia", "cudnn", "bin"),
]

for dll_dir in CUDA_DIRS:
    if os.path.isdir(dll_dir):
        try:
            os.add_dll_directory(dll_dir)
        except Exception:
            pass
        os.environ["PATH"] = dll_dir + os.pathsep + os.environ.get("PATH", "")

import numpy as np
import sounddevice as sd
import torch
import pygame

from silero_vad import load_silero_vad, get_speech_timestamps
from faster_whisper import WhisperModel
from supertonic import TTS
import ollama


# ============================================================
# CONFIGURACIÓN
# ============================================================

SAMPLE_RATE = 16000
VENTANA = 512

VAD_THRESHOLD = 0.35
SILENCIO_FINAL = 0.8
MAX_DURACION = 15
PRE_BUFFER_SEGUNDOS = 1.0

WHISPER_MODEL = "small"
OLLAMA_MODEL = "qwen2.5:3b"
VOICE = "F4"
LANGUAGE = "es"


# ============================================================
# CARGAR MODELOS
# ============================================================

print("🧠 Cargando Silero VAD...")
vad = load_silero_vad()

print("📝 Cargando Whisper...")
whisper = WhisperModel(
    WHISPER_MODEL,
    device="cuda",
    compute_type="float16",
)

print("🔊 Cargando Supertonic...")
tts = TTS(auto_download=True)
voice_style = tts.get_voice_style(VOICE)

print("🔊 Inicializando audio...")
pygame.mixer.init(
    frequency=24000,
    size=-16,
    channels=1,
)

mensajes = [
    {
        "role": "system",
        "content": """
Eres ZOE, la asistente virtual de SIGEFIV.

REGLA ABSOLUTA DE IDIOMA:
- Responde SIEMPRE y ÚNICAMENTE en español.
- Usa español latinoamericano natural.
- NO uses inglés.
- NO mezcles español e inglés.
- Aunque el usuario hable en inglés, responde en español.

PERSONALIDAD:
Eres cálida, natural, amable y conversacional.
Habla como una asistente humana, no como un robot.
Responde de forma clara y breve.

No inventes información.
""",
    }
]


# ============================================================
# VAD
# ============================================================

def detectar_voz(audio):
    if audio is None or len(audio) == 0:
        return False

    audio = np.asarray(audio, dtype=np.float32)

    try:
        timestamps = get_speech_timestamps(
            torch.from_numpy(audio),
            vad,
            threshold=VAD_THRESHOLD,
            sampling_rate=SAMPLE_RATE,
        )
        return len(timestamps) > 0

    except Exception as e:
        print(f"⚠️ Error VAD: {e}")
        return False


# ============================================================
# GUARDAR AUDIO DE DEPURACIÓN
# ============================================================

def guardar_debug(audio):
    if audio is None or len(audio) == 0:
        print("❌ guardar_debug recibió audio vacío.")
        return

    ruta = os.path.abspath("debug_voz.wav")

    try:
        audio = np.asarray(audio, dtype=np.float32)

        audio_int16 = np.clip(
            audio * 32767,
            -32768,
            32767,
        ).astype(np.int16)

        with wave.open(ruta, "wb") as archivo:
            archivo.setnchannels(1)
            archivo.setsampwidth(2)
            archivo.setframerate(SAMPLE_RATE)
            archivo.writeframes(audio_int16.tobytes())

        print(f"💾 Audio guardado: {ruta}")

    except Exception as e:
        print(f"⚠️ No se pudo guardar debug_voz.wav: {e}")


# ============================================================
# ESCUCHAR UNA FRASE
# ============================================================

def escuchar():
    print("\n🎤 Escuchando...")

    pre_buffer = []

    max_pre_buffer = int(
        PRE_BUFFER_SEGUNDOS * SAMPLE_RATE / VENTANA
    )

    audio_frase = []

    hablando = False
    ultimo_audio = time.time()
    inicio_frase = None

    try:
        with sd.InputStream(
            samplerate=SAMPLE_RATE,
            channels=1,
            dtype="float32",
            blocksize=VENTANA,
        ) as stream:

            while True:
                datos, overflowed = stream.read(VENTANA)

                if overflowed:
                    print("⚠️ Overflow de audio.")

                bloque = np.squeeze(datos).astype(np.float32)

                pre_buffer.append(bloque.copy())

                if len(pre_buffer) > max_pre_buffer:
                    pre_buffer.pop(0)

                hay_voz = detectar_voz(bloque)

                if hay_voz:

                    if not hablando:
                        hablando = True
                        inicio_frase = time.time()

                        print("🗣️ Te escucho...")

                        # Conservamos el audio previo
                        audio_frase.extend(pre_buffer)

                    else:
                        audio_frase.append(bloque.copy())

                    ultimo_audio = time.time()

                elif hablando:

                    # Conservamos también el silencio final.
                    audio_frase.append(bloque.copy())

                    tiempo_silencio = time.time() - ultimo_audio
                    duracion = time.time() - inicio_frase

                    if tiempo_silencio >= SILENCIO_FINAL:

                        print(
                            f"🔇 Fin de frase ({duracion:.2f} segundos)"
                        )

                        audio = np.concatenate(audio_frase)

                        print(
                            f"🎧 Audio capturado: "
                            f"{len(audio) / SAMPLE_RATE:.2f} segundos"
                        )

                        return audio

                    if duracion >= MAX_DURACION:

                        print("⏱️ Duración máxima alcanzada.")

                        audio = np.concatenate(audio_frase)

                        print(
                            f"🎧 Audio capturado: "
                            f"{len(audio) / SAMPLE_RATE:.2f} segundos"
                        )

                        return audio

    except KeyboardInterrupt:

        print("\n🛑 ZOE detenida.")
        return None

    except Exception as e:

        print(f"❌ Error de captura: {e}")
        return None


# ============================================================
# TRANSCRIBIR
# ============================================================

def transcribir(audio):

    print("📝 Transcribiendo...")

    if audio is None:

        print("❌ Audio recibido: None")
        return ""

    if len(audio) == 0:

        print("❌ Audio recibido: 0 muestras")
        return ""

    audio = np.asarray(
        audio,
        dtype=np.float32
    )

    # ========================================================
    # DIAGNÓSTICO DEL AUDIO
    # ========================================================

    maximo = float(
        np.max(
            np.abs(audio)
        )
    )

    rms = float(
        np.sqrt(
            np.mean(
                audio ** 2
            )
        )
    )

    duracion = len(audio) / SAMPLE_RATE

    print(f"📊 Muestras: {len(audio)}")
    print(f"📊 Duración: {duracion:.2f} segundos")
    print(f"📊 Máximo: {maximo:.6f}")
    print(f"📊 RMS: {rms:.6f}")

    # ========================================================
    # GUARDAR AUDIO
    # ========================================================

    guardar_debug(audio)

    # ========================================================
    # NIVEL MÍNIMO
    # ========================================================

    if maximo < 0.005:

        print(
            "⚠️ El audio tiene un nivel demasiado bajo."
        )

        return ""

    # ========================================================
    # WHISPER
    # ========================================================

    try:

        print("🧠 Enviando audio a Whisper...")

        segments, info = whisper.transcribe(
            audio,
            language="es",
            task="transcribe",
            vad_filter=True,
            condition_on_previous_text=False,
            beam_size=5,
        )

        # IMPORTANTE:
        # faster-whisper devuelve un generador.
        # Hay que consumirlo para ejecutar realmente
        # la transcripción.
        segmentos = list(segments)

        texto = " ".join(
            segmento.text.strip()
            for segmento in segmentos
        ).strip()

        print(
            f"📝 Whisper recibió: '{texto}'"
        )

    except Exception as e:

        print(
            f"❌ Error de Whisper: {e}"
        )

        return ""

    # ========================================================
    # FILTRO DE ALUCINACIONES
    # ========================================================

    frases_falsas = [
        "subtítulos realizados por la comunidad de amara.org",
        "subtítulos por la comunidad de amara.org",
        "subtitulos realizados por la comunidad de amara.org",
        "subtitulos por la comunidad de amara.org",
        "subtítulos realizados por la comunidad",
        "subtitulos realizados por la comunidad",
        "amara.org",
    ]

    texto_normalizado = texto.lower()

    for frase in frases_falsas:

        if frase in texto_normalizado:

            print("⚠️ Transcripción descartada.")

            return ""

    return texto


# ============================================================
# QWEN
# ============================================================

def preguntar_zoe(texto):

    global mensajes

    if not texto:
        return ""

    mensajes.append(
        {
            "role": "user",
            "content": texto,
        }
    )

    try:

        respuesta = ollama.chat(
            model=OLLAMA_MODEL,
            messages=mensajes,
        )

        contenido = respuesta["message"]["content"].strip()

        mensajes.append(
            {
                "role": "assistant",
                "content": contenido,
            }
        )

        return contenido

    except Exception as e:

        print(
            f"❌ Error de Ollama: {e}"
        )

        return (
            "Lo siento, tuve un problema "
            "al procesar tu solicitud."
        )


# ============================================================
# SUPERTONIC
# ============================================================

def hablar(texto):

    if not texto:
        return

    print(
        f"🤖 ZOE: {texto}"
    )

    try:

        wav, duracion = tts.synthesize(
            texto,
            voice_style=voice_style,
            lang=LANGUAGE,
        )

        ruta_audio = os.path.join(
            tempfile.gettempdir(),
            "zoe_respuesta.wav",
        )

        tts.save_audio(
            wav,
            ruta_audio,
        )

        pygame.mixer.music.load(
            ruta_audio
        )

        pygame.mixer.music.play()

        while pygame.mixer.music.get_busy():

            time.sleep(0.05)

        pygame.mixer.music.stop()

    except Exception as e:

        print(
            f"❌ Error de Supertonic: {e}"
        )


# ============================================================
# PROGRAMA PRINCIPAL
# ============================================================

def main():

    print()
    print("=" * 60)
    print("🤖 ZOE - CONVERSACIÓN CONTINUA")
    print("=" * 60)
    print()
    print(f"Modelo IA : {OLLAMA_MODEL}")
    print(f"Whisper   : {WHISPER_MODEL}")
    print(f"Voz       : {VOICE}")
    print(f"Idioma    : {LANGUAGE}")
    print()
    print("🤖 ZOE está lista.")
    print()
    print("Presiona CTRL+C para salir.")
    print("=" * 60)

    while True:

        try:

            audio = escuchar()

            if audio is None:
                continue

            texto = transcribir(audio)

            if not texto:

                print(
                    "⚠️ No entendí la frase."
                )

                continue

            print(
                f"👤 Tú: {texto}"
            )

            respuesta = preguntar_zoe(
                texto
            )

            if not respuesta:
                continue

            hablar(
                respuesta
            )

        except KeyboardInterrupt:

            print()
            print("🛑 ZOE finalizada.")
            break

        except Exception as e:

            print(
                f"❌ Error general: {e}"
            )

            time.sleep(1)


# ============================================================
# INICIO
# ============================================================

if __name__ == "__main__":
    main()
