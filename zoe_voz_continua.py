import os
import time
import wave
import tempfile
import uuid
import threading

# ============================================================
# CUDA
# ============================================================

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

CUDA_DIRS = [
    os.path.join(
        BASE_DIR,
        ".venv-supertonic",
        "Lib",
        "site-packages",
        "nvidia",
        "cublas",
        "bin",
    ),
    os.path.join(
        BASE_DIR,
        ".venv-supertonic",
        "Lib",
        "site-packages",
        "nvidia",
        "cudnn",
        "bin",
    ),
]

for dll_dir in CUDA_DIRS:
    if os.path.isdir(dll_dir):
        try:
            os.add_dll_directory(dll_dir)
        except Exception:
            pass

        os.environ["PATH"] = (
            dll_dir
            + os.pathsep
            + os.environ.get("PATH", "")
        )


# ============================================================
# IMPORTACIONES
# ============================================================

import numpy as np
import sounddevice as sd
import torch
import pygame

from silero_vad import load_silero_vad
from silero_vad import get_speech_timestamps

from faster_whisper import WhisperModel

from supertonic import TTS

import ollama


# ============================================================
# CONFIGURACIÓN
# ============================================================

SAMPLE_RATE = 16000
VENTANA = 512

BUFFER_VAD_SEGUNDOS = 1.0

BUFFER_VAD_MUESTRAS = int(
    SAMPLE_RATE * BUFFER_VAD_SEGUNDOS
)

VAD_THRESHOLD = 0.25

SILENCIO_FINAL = 0.8

MAX_DURACION = 15

PRE_BUFFER_SEGUNDOS = 0.8

BARGEIN_VENTANA = 512
BARGEIN_THRESHOLD = 0.25
BARGEIN_BLOQUES = 3
BARGEIN_MIN_DURACION = 0.45

WHISPER_MODEL = "small"

OLLAMA_MODEL = "qwen2.5:3b"

VOICE = "F4"

LANGUAGE = "es"


# ============================================================
# CARGAR SILERO VAD
# ============================================================

print("🧠 Cargando Silero VAD...")

vad = load_silero_vad()


# ============================================================
# CARGAR WHISPER
# ============================================================

print("📝 Cargando Whisper...")

whisper = WhisperModel(
    WHISPER_MODEL,
    device="cuda",
    compute_type="float16",
)


# ============================================================
# CARGAR SUPERTONIC
# ============================================================

print("🔊 Cargando Supertonic...")

tts = TTS(
    auto_download=True
)

voice_style = tts.get_voice_style(
    VOICE
)


# ============================================================
# AUDIO
# ============================================================

print("🔊 Inicializando audio...")

pygame.mixer.init(
    frequency=24000,
    size=-16,
    channels=1,
)


# ============================================================
# HISTORIAL DE ZOE
# ============================================================

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
- Aunque el usuario hable en inglés,
  responde en español.

PERSONALIDAD:

Eres cálida, natural, amable y conversacional.

Habla como una asistente humana,
no como un robot.

Responde de forma clara y breve.

No inventes información.
""",
    }
]


# ============================================================
# DETECTAR VOZ
# ============================================================

def detectar_voz(audio):

    if audio is None:
        return False

    if len(audio) == 0:
        return False

    audio = np.asarray(
        audio,
        dtype=np.float32
    )

    try:

        timestamps = get_speech_timestamps(
            torch.from_numpy(audio),
            vad,
            threshold=VAD_THRESHOLD,
            sampling_rate=SAMPLE_RATE,
        )

        return len(timestamps) > 0

    except Exception as e:

        print(
            f"⚠️ Error VAD: {e}"
        )

        return False


# ============================================================
# GUARDAR AUDIO DEBUG
# ============================================================

def guardar_debug(audio):

    if audio is None:
        return

    if len(audio) == 0:
        return

    ruta = os.path.abspath(
        "debug_voz.wav"
    )

    try:

        audio = np.asarray(
            audio,
            dtype=np.float32
        )

        audio_int16 = np.clip(
            audio * 32767,
            -32768,
            32767,
        ).astype(np.int16)

        with wave.open(
            ruta,
            "wb"
        ) as archivo:

            archivo.setnchannels(1)

            archivo.setsampwidth(2)

            archivo.setframerate(
                SAMPLE_RATE
            )

            archivo.writeframes(
                audio_int16.tobytes()
            )

        print(
            f"💾 Audio guardado: {ruta}"
        )

    except Exception as e:

        print(
            f"⚠️ Error guardando audio: {e}"
        )


# ============================================================
# ESCUCHAR CONTINUAMENTE
# ============================================================

def escuchar():

    print()
    print("🎤 Escuchando...")

    buffer_vad = np.zeros(
        0,
        dtype=np.float32
    )

    max_pre_buffer = int(
        PRE_BUFFER_SEGUNDOS
        * SAMPLE_RATE
    )

    pre_buffer = np.zeros(
        0,
        dtype=np.float32
    )

    audio_frase = []

    hablando = False

    inicio_frase = None

    ultimo_audio = None

    try:

        with sd.InputStream(
            samplerate=SAMPLE_RATE,
            channels=1,
            dtype="float32",
            blocksize=VENTANA,
        ) as stream:

            while True:

                datos, overflowed = stream.read(
                    VENTANA
                )

                if overflowed:

                    print(
                        "⚠️ Overflow de audio."
                    )

                bloque = np.squeeze(
                    datos
                ).astype(
                    np.float32
                )

                # =================================================
                # PRE-BUFFER
                # =================================================

                pre_buffer = np.concatenate(
                    [
                        pre_buffer,
                        bloque,
                    ]
                )

                if len(pre_buffer) > max_pre_buffer:

                    pre_buffer = (
                        pre_buffer[
                            -max_pre_buffer:
                        ]
                    )

                # =================================================
                # BUFFER VAD
                # =================================================

                buffer_vad = np.concatenate(
                    [
                        buffer_vad,
                        bloque,
                    ]
                )

                if len(buffer_vad) > BUFFER_VAD_MUESTRAS:

                    buffer_vad = (
                        buffer_vad[
                            -BUFFER_VAD_MUESTRAS:
                        ]
                    )

                if len(buffer_vad) < BUFFER_VAD_MUESTRAS:

                    continue

                # =================================================
                # DETECTAR VOZ
                # =================================================

                hay_voz = detectar_voz(
                    buffer_vad
                )

                ahora = time.time()

                # =================================================
                # COMIENZO DE FRASE
                # =================================================

                if hay_voz and not hablando:

                    hablando = True

                    inicio_frase = ahora

                    ultimo_audio = ahora

                    print(
                        "🗣️ Te escucho..."
                    )

                    audio_frase = [
                        pre_buffer.copy()
                    ]

                    audio_frase.append(
                        bloque.copy()
                    )

                    continue

                # =================================================
                # CONTINUAMOS HABLANDO
                # =================================================

                if hablando:

                    audio_frase.append(
                        bloque.copy()
                    )

                    if hay_voz:

                        ultimo_audio = ahora

                    duracion = (
                        ahora
                        - inicio_frase
                    )

                    tiempo_silencio = (
                        ahora
                        - ultimo_audio
                    )

                    if (
                        tiempo_silencio
                        >= SILENCIO_FINAL
                    ):

                        print(
                            f"🔇 Fin de frase "
                            f"({duracion:.2f} segundos)"
                        )

                        audio = np.concatenate(
                            audio_frase
                        )

                        print(
                            f"🎧 Audio capturado: "
                            f"{len(audio) / SAMPLE_RATE:.2f} segundos"
                        )

                        return audio

                    if duracion >= MAX_DURACION:

                        print(
                            "⏱️ Duración máxima alcanzada."
                        )

                        audio = np.concatenate(
                            audio_frase
                        )

                        print(
                            f"🎧 Audio capturado: "
                            f"{len(audio) / SAMPLE_RATE:.2f} segundos"
                        )

                        return audio

    except KeyboardInterrupt:

        print(
            "\n🛑 ZOE detenida."
        )

        return None

    except Exception as e:

        print(
            f"❌ Error de captura: {e}"
        )

        return None


# ============================================================
# TRANSCRIBIR
# ============================================================

def transcribir(audio):

    print(
        "📝 Transcribiendo..."
    )

    if audio is None:

        print(
            "❌ Audio = None"
        )

        return ""

    if len(audio) == 0:

        print(
            "❌ Audio vacío"
        )

        return ""

    audio = np.asarray(
        audio,
        dtype=np.float32
    )

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

    duracion = (
        len(audio)
        / SAMPLE_RATE
    )

    print(
        f"📊 Muestras: {len(audio)}"
    )

    print(
        f"📊 Duración: {duracion:.2f} segundos"
    )

    print(
        f"📊 Máximo: {maximo:.6f}"
    )

    print(
        f"📊 RMS: {rms:.6f}"
    )

    guardar_debug(
        audio
    )

    if maximo < 0.005:

        print(
            "⚠️ El audio tiene un nivel demasiado bajo."
        )

        return ""

    try:

        print(
            "🧠 Enviando audio a Whisper..."
        )

        segments, info = whisper.transcribe(
            audio,
            language="es",
            task="transcribe",
            vad_filter=True,
            condition_on_previous_text=False,
            beam_size=5,
        )

        segmentos = list(
            segments
        )

        texto = " ".join(
            segmento.text.strip()
            for segmento in segmentos
        ).strip()

        print(
            f"📝 Whisper recibió: "
            f"'{texto}'"
        )

    except Exception as e:

        print(
            f"❌ Error de Whisper: {e}"
        )

        return ""

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

            print(
                "⚠️ Transcripción descartada."
            )

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

        contenido = (
            respuesta[
                "message"
            ][
                "content"
            ]
            .strip()
        )

        mensajes.append(
            {
                "role": "assistant",
                "content": contenido,
            }
        )

        return contenido

    except Exception as e:

        print(
            f"❌ Error Ollama: {e}"
        )

        return (
            "Lo siento, tuve un problema "
            "al procesar tu solicitud."
        )


# ============================================================
# BARGE-IN
# ============================================================

def vigilar_interrupcion(evento_detener, audio_interrupcion):
    bloques_voz = 0
    capturando = False
    audio = []
    try:
        with sd.InputStream(samplerate=SAMPLE_RATE, channels=1, dtype="float32", blocksize=BARGEIN_VENTANA) as stream:
            while not evento_detener.is_set():
                datos, overflowed = stream.read(BARGEIN_VENTANA)
                if overflowed:
                    continue
                bloque = np.squeeze(datos).astype(np.float32)
                hay_voz = detectar_voz(bloque)
                if hay_voz:
                    bloques_voz += 1
                    if capturando:
                        audio.append(bloque.copy())
                    if not capturando and bloques_voz >= BARGEIN_BLOQUES:
                        capturando = True
                        audio = [bloque.copy()]
                        print()
                        print("🗣️ Interrupción detectada.")
                        print("⛔ ZOE deteniendo su voz...")
                        evento_detener.set()
                else:
                    bloques_voz = 0
                    if capturando:
                        audio.append(bloque.copy())
    except Exception as e:
        if not evento_detener.is_set():
            print(f"⚠️ Error monitor BARGE-IN: {e}")
    if capturando and audio:
        resultado = np.concatenate(audio)
        duracion = len(resultado) / SAMPLE_RATE
        if duracion >= BARGEIN_MIN_DURACION:
            audio_interrupcion.append(resultado)
        else:
            print("⚠️ Interrupción demasiado corta.")


# ============================================================
# HABLAR - REPRODUCCIÓN ROBUSTA
# ============================================================

def hablar(texto):
    if not texto:
        return False, None

    print(f"🤖 ZOE: {texto}")
    ruta_audio = None
    evento_detener = threading.Event()
    audio_interrupcion = []
    monitor = None

    try:
        wav, duracion = tts.synthesize(texto, voice_style=voice_style, lang=LANGUAGE)
        nombre = "zoe_" + uuid.uuid4().hex + ".wav"
        ruta_audio = os.path.join(tempfile.gettempdir(), nombre)
        print("🔊 Audio generado correctamente.")
        print(f"💾 Archivo: {ruta_audio}")
        tts.save_audio(wav, ruta_audio)
        if not os.path.exists(ruta_audio):
            print("❌ Supertonic no creó el archivo.")
            return False, None
        for _ in range(20):
            try:
                if os.path.getsize(ruta_audio) > 44:
                    break
            except Exception:
                pass
            time.sleep(0.05)
        pygame.mixer.music.load(ruta_audio)
        pygame.mixer.music.play()
        monitor = threading.Thread(target=vigilar_interrupcion, args=(evento_detener, audio_interrupcion), daemon=True)
        monitor.start()
        print("👂 ZOE está escuchando por si la interrumpes...")
        while pygame.mixer.music.get_busy():
            if evento_detener.is_set():
                pygame.mixer.music.stop()
                break
            time.sleep(0.03)
        pygame.mixer.music.stop()
        try:
            pygame.mixer.music.unload()
        except Exception:
            pass
        evento_detener.set()
        if monitor is not None:
            monitor.join(timeout=1.0)
        if audio_interrupcion:
            audio = audio_interrupcion[0]
            print(f"🎧 Interrupción capturada: {len(audio) / SAMPLE_RATE:.2f} segundos")
            return True, audio
        return False, None
    except Exception as e:
        print(f"❌ Error Supertonic: {e}")
        evento_detener.set()
        if monitor is not None:
            monitor.join(timeout=1.0)
        try:
            pygame.mixer.music.stop()
        except Exception:
            pass
        return False, None
    finally:
        if ruta_audio:
            try:
                if os.path.exists(ruta_audio):
                    os.remove(ruta_audio)
            except Exception:
                pass


# ============================================================
# MAIN
# ============================================================

def main():

    print()

    print(
        "=" * 60
    )

    print(
        "🤖 ZOE - CONVERSACIÓN CONTINUA"
    )

    print(
        "=" * 60
    )

    print()

    print(
        f"Modelo IA : {OLLAMA_MODEL}"
    )

    print(
        f"Whisper   : {WHISPER_MODEL}"
    )

    print(
        f"Voz       : {VOICE}"
    )

    print(
        f"Idioma    : {LANGUAGE}"
    )

    print()

    print(
        "🤖 ZOE está lista."
    )

    print()

    print(
        "Presiona CTRL+C para salir."
    )

    print(
        "=" * 60
    )

    while True:

        try:

            audio = escuchar()

            if audio is None:

                continue

            texto = transcribir(
                audio
            )

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

            fue_interrumpida, audio_interrupcion = hablar(
                respuesta
            )

            if fue_interrumpida and audio_interrupcion is not None:
                texto_interrupcion = transcribir(audio_interrupcion)
                if texto_interrupcion:
                    print(f"👤 Tú (interrupción): {texto_interrupcion}")
                    respuesta_interrupcion = preguntar_zoe(texto_interrupcion)
                    if respuesta_interrupcion:
                        hablar(respuesta_interrupcion)

        except KeyboardInterrupt:

            print()

            print(
                "🛑 ZOE finalizada."
            )

            break

        except Exception as e:

            print(
                f"❌ Error general: {e}"
            )

            time.sleep(
                1
            )


# ============================================================
# INICIO
# ============================================================

if __name__ == "__main__":

    main()
