import os
import tempfile
import time
import threading

import numpy as np
import sounddevice as sd
import ollama
import torch
import pygame

from faster_whisper import WhisperModel
from supertonic import TTS
from silero_vad import load_silero_vad, get_speech_timestamps


# ============================================================
# CONFIGURACIÓN
# ============================================================

OLLAMA_MODEL = "qwen2.5:3b"
WHISPER_MODEL = "small"

VOICE = "F4"
LANG = "es"

SAMPLE_RATE = 16000

# Bloque de audio
BLOQUE = 0.25

# Silencio necesario para terminar una frase
SILENCIO_FINAL = 0.8

# Máxima duración de una intervención
MAX_DURACION = 15

# Sensibilidad del VAD
VAD_THRESHOLD = 0.55

# Bloques consecutivos necesarios para confirmar
# una interrupción
BLOQUES_INTERRUPCION = 3


# ============================================================
# PERSONALIDAD DE ZOE
# ============================================================

SYSTEM_PROMPT = """
Eres ZOE, la asistente virtual de SIGEFIV.

REGLAS ABSOLUTAS:

- Responde siempre y únicamente en español.
- Usa español latinoamericano natural.
- No mezcles español e inglés.
- Sé cálida, natural y conversacional.
- Habla como una asistente humana, no como un robot.
- Responde de forma clara y breve.
- No inventes información.
- Si no conoces un dato, dilo claramente.
- No repitas innecesariamente la pregunta del usuario.

Tu nombre es ZOE.
"""


# ============================================================
# NVIDIA CUDA
# ============================================================

BASE = os.path.dirname(
    os.path.abspath(__file__)
)

CUDA_PATHS = [
    os.path.join(
        BASE,
        ".venv-supertonic",
        "Lib",
        "site-packages",
        "nvidia",
        "cublas",
        "bin"
    ),

    os.path.join(
        BASE,
        ".venv-supertonic",
        "Lib",
        "site-packages",
        "nvidia",
        "cudnn",
        "bin"
    ),
]


for path in CUDA_PATHS:

    if os.path.exists(path):

        try:
            os.add_dll_directory(path)
        except Exception:
            pass

        os.environ["PATH"] = (
            path
            + os.pathsep
            + os.environ["PATH"]
        )


# ============================================================
# SILERO VAD
# ============================================================

print("Cargando Silero VAD...")

vad_model = load_silero_vad()

print("✅ VAD listo")


# ============================================================
# ESTADO
# ============================================================

zoe_hablando = False
interrumpida = False


# ============================================================
# DETECTAR VOZ
# ============================================================

def detectar_voz(audio):

    if audio is None or len(audio) == 0:
        return False

    tensor = torch.from_numpy(
        audio.astype(np.float32)
    )

    timestamps = get_speech_timestamps(
        tensor,
        vad_model,
        sampling_rate=SAMPLE_RATE,
        threshold=VAD_THRESHOLD,
        min_speech_duration_ms=100,
        min_silence_duration_ms=100
    )

    return len(timestamps) > 0


# ============================================================
# ESCUCHAR AL USUARIO
# ============================================================

def escuchar():

    print("\n👂 Esperando que hables...")

    audio_total = []

    hablando = False

    tiempo_silencio = 0.0
    tiempo_total = 0.0

    with sd.InputStream(
        samplerate=SAMPLE_RATE,
        channels=1,
        dtype="float32"
    ) as stream:

        while True:

            audio, _ = stream.read(
                int(
                    SAMPLE_RATE * BLOQUE
                )
            )

            audio = np.squeeze(
                audio
            ).copy()

            audio_total.append(
                audio
            )

            hay_voz = detectar_voz(
                audio
            )

            # --------------------------------------------
            # Comienzo de voz
            # --------------------------------------------

            if hay_voz:

                if not hablando:

                    print(
                        "🎤 Te escucho..."
                    )

                hablando = True

                tiempo_silencio = 0.0

            # --------------------------------------------
            # Silencio
            # --------------------------------------------

            elif hablando:

                tiempo_silencio += BLOQUE

            # --------------------------------------------
            # Fin de frase
            # --------------------------------------------

            if (
                hablando
                and tiempo_silencio >= SILENCIO_FINAL
            ):

                break

            # --------------------------------------------
            # Seguridad
            # --------------------------------------------

            tiempo_total += BLOQUE

            if tiempo_total >= MAX_DURACION:

                break


    if not audio_total:

        return None


    return np.concatenate(
        audio_total
    )


# ============================================================
# VIGILAR INTERRUPCIÓN
# ============================================================

def vigilar_interrupcion():

    global zoe_hablando
    global interrumpida

    print(
        "🎧 Vigilando interrupción..."
    )

    bloques_con_voz = 0

    audio_interrupcion = []

    try:

        with sd.InputStream(
            samplerate=SAMPLE_RATE,
            channels=1,
            dtype="float32"
        ) as stream:

            while zoe_hablando:

                audio, _ = stream.read(
                    int(
                        SAMPLE_RATE * BLOQUE
                    )
                )

                audio = np.squeeze(
                    audio
                ).copy()

                hay_voz = detectar_voz(
                    audio
                )

                # ----------------------------------------
                # Guardamos audio mientras vigilamos
                # ----------------------------------------

                if hay_voz:

                    bloques_con_voz += 1

                    audio_interrupcion.append(
                        audio
                    )

                else:

                    bloques_con_voz = 0

                    # Guardamos el silencio inmediatamente
                    # anterior para conservar el contexto.
                    if audio_interrupcion:

                        audio_interrupcion.append(
                            audio
                        )

                        # Limitamos el buffer para no
                        # acumular demasiado silencio.
                        max_bloques = int(
                            2.0 / BLOQUE
                        )

                        if len(audio_interrupcion) > max_bloques:

                            audio_interrupcion = (
                                audio_interrupcion[
                                    -max_bloques:
                                ]
                            )

                # ----------------------------------------
                # Confirmar interrupción
                # ----------------------------------------

                if (
                    bloques_con_voz
                    >= BLOQUES_INTERRUPCION
                ):

                    print(
                        "\n🛑 Interrupción confirmada"
                    )

                    interrumpida = True

                    try:

                        pygame.mixer.music.stop()

                    except Exception:

                        pass

                    if audio_interrupcion:

                        return np.concatenate(
                            audio_interrupcion
                        )

                    return None

    except Exception as e:

        print(
            "\n⚠️ Error vigilando "
            f"interrupción: {e}"
        )

    return None


# ============================================================
# WHISPER
# ============================================================

def transcribir(
    audio,
    whisper
):

    if audio is None:

        return ""

    print(
        "🧠 Transcribiendo..."
    )

    segments, info = whisper.transcribe(
        audio,
        language="es",
        task="transcribe",
        vad_filter=True,
        condition_on_previous_text=False
    )

    segmentos = list(
        segments
    )

    if not segmentos:

        return ""

    texto = " ".join(
        segment.text.strip()
        for segment in segmentos
        if segment.text.strip()
    )

    texto = texto.strip()

    if not texto:

        return ""

    # ========================================================
    # FILTRO DE TRANSCRIPCIONES FALSAS
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

    texto_normalizado = (
        texto
        .lower()
        .strip()
    )

    for frase in frases_falsas:

        if frase in texto_normalizado:

            print(
                "\n⚠️ Transcripción descartada:"
            )

            print(
                texto
            )

            return ""

    return texto


# ============================================================
# HABLAR
# ============================================================

def hablar(
    texto,
    tts,
    voice_style
):

    global zoe_hablando
    global interrumpida

    print(
        "\n🔊 ZOE:"
    )

    print(
        texto
    )

    # --------------------------------------------
    # Generar audio
    # --------------------------------------------

    wav, duration = tts.synthesize(
        texto,
        voice_style=voice_style,
        lang=LANG
    )

    # --------------------------------------------
    # Archivo temporal único
    # --------------------------------------------

    archivo = os.path.join(
        tempfile.gettempdir(),
        "zoe_respuesta_"
        f"{int(time.time() * 1000)}.wav"
    )

    tts.save_audio(
        wav,
        archivo
    )

    hilo = None

    resultado = {
        "audio": None
    }

    try:

        # ----------------------------------------
        # Inicializar pygame
        # ----------------------------------------

        if not pygame.mixer.get_init():

            pygame.mixer.init()

        pygame.mixer.music.load(
            archivo
        )

        interrumpida = False
        zoe_hablando = True

        pygame.mixer.music.play()

        print(
            "🔊 ZOE está hablando..."
        )

        # ----------------------------------------
        # Hilo detector de interrupción
        # ----------------------------------------

        def vigilar():

            resultado["audio"] = (
                vigilar_interrupcion()
            )

        hilo = threading.Thread(
            target=vigilar,
            daemon=True
        )

        hilo.start()

        # ----------------------------------------
        # Esperar reproducción
        # ----------------------------------------

        while pygame.mixer.music.get_busy():

            if interrumpida:

                break

            time.sleep(
                0.03
            )

    except Exception as e:

        print(
            "\n❌ Error reproduciendo audio:"
        )

        print(
            e
        )

    finally:

        try:

            pygame.mixer.music.stop()

        except Exception:

            pass

        zoe_hablando = False

        if hilo is not None:

            try:

                hilo.join(
                    timeout=1.0
                )

            except Exception:

                pass

        # ----------------------------------------
        # Eliminar archivo
        # ----------------------------------------

        try:

            if os.path.exists(
                archivo
            ):

                os.remove(
                    archivo
                )

        except Exception:

            pass

    return resultado["audio"]


# ============================================================
# MAIN
# ============================================================

def main():

    print()

    print(
        "=" * 60
    )

    print(
        "              ZOE VOZ - SIGEFIV"
    )

    print(
        "              MODO BARGE-IN"
    )

    print(
        "=" * 60
    )

    # ========================================================
    # WHISPER
    # ========================================================

    print(
        "\nCargando Whisper..."
    )

    whisper = WhisperModel(
        WHISPER_MODEL,
        device="cuda",
        compute_type="float16"
    )

    print(
        "✅ Whisper GPU listo"
    )

    # ========================================================
    # SUPERTONIC
    # ========================================================

    print(
        "\nCargando Supertonic..."
    )

    tts = TTS(
        auto_download=True
    )

    voice_style = tts.get_voice_style(
        VOICE
    )

    print(
        f"✅ Voz {VOICE} lista"
    )

    # ========================================================
    # OLLAMA
    # ========================================================

    print(
        "\nConectando con Ollama..."
    )

    ollama.list()

    print(
        f"✅ Qwen {OLLAMA_MODEL} listo"
    )

    # ========================================================
    # HISTORIAL
    # ========================================================

    mensajes = [
        {
            "role": "system",
            "content": SYSTEM_PROMPT
        }
    ]

    # ========================================================
    # LISTO
    # ========================================================

    print()

    print(
        "=" * 60
    )

    print(
        "ZOE está lista."
    )

    print()

    print(
        "Habla normalmente."
    )

    print(
        "Puedes interrumpir a ZOE mientras habla."
    )

    print()

    print(
        "Ctrl+C para salir."
    )

    print(
        "=" * 60
    )

    # ========================================================
    # BUCLE PRINCIPAL
    # ========================================================

    while True:

        try:

            # --------------------------------------------
            # ESCUCHAR
            # --------------------------------------------

            audio = escuchar()

            if audio is None:

                continue

            # --------------------------------------------
            # TRANSCRIBIR
            # --------------------------------------------

            texto = transcribir(
                audio,
                whisper
            )

            if not texto:

                print(
                    "⚪ No se detectó "
                    "una frase válida."
                )

                continue

            print()

            print(
                "👤 Tú:"
            )

            print(
                texto
            )

            # --------------------------------------------
            # HISTORIAL
            # --------------------------------------------

            mensajes.append(
                {
                    "role": "user",
                    "content": texto
                }
            )

            # --------------------------------------------
            # QWEN
            # --------------------------------------------

            print(
                "🤖 Pensando..."
            )

            respuesta = ollama.chat(
                model=OLLAMA_MODEL,
                messages=mensajes
            )

            texto_zoe = (
                respuesta["message"]["content"]
                .strip()
            )

            # --------------------------------------------
            # GUARDAR RESPUESTA
            # --------------------------------------------

            mensajes.append(
                {
                    "role": "assistant",
                    "content": texto_zoe
                }
            )

            # --------------------------------------------
            # HABLAR
            # --------------------------------------------

            audio_interrupcion = hablar(
                texto_zoe,
                tts,
                voice_style
            )

            # =================================================
            # PROCESAR INTERRUPCIÓN
            # =================================================

            if audio_interrupcion is not None:

                print(
                    "\n🎤 Procesando interrupción..."
                )

                texto_interrupcion = transcribir(
                    audio_interrupcion,
                    whisper
                )

                if texto_interrupcion:

                    print()

                    print(
                        "👤 Tú:"
                    )

                    print(
                        texto_interrupcion
                    )

                    # ------------------------------------
                    # Agregar al historial
                    # ------------------------------------

                    mensajes.append(
                        {
                            "role": "user",
                            "content": texto_interrupcion
                        }
                    )

                    # ------------------------------------
                    # Qwen
                    # ------------------------------------

                    print(
                        "🤖 Pensando..."
                    )

                    respuesta = ollama.chat(
                        model=OLLAMA_MODEL,
                        messages=mensajes
                    )

                    texto_zoe = (
                        respuesta["message"]["content"]
                        .strip()
                    )

                    # ------------------------------------
                    # Guardar respuesta
                    # ------------------------------------

                    mensajes.append(
                        {
                            "role": "assistant",
                            "content": texto_zoe
                        }
                    )

                    # ------------------------------------
                    # Nueva respuesta
                    # ------------------------------------

                    hablar(
                        texto_zoe,
                        tts,
                        voice_style
                    )

        except KeyboardInterrupt:

            print()
            print()

            print(
                "ZOE: Hasta luego."
            )

            break

        except Exception as e:

            print()
            print(
                "❌ Error:"
            )

            print(
                e
            )


# ============================================================
# EJECUTAR
# ============================================================

if __name__ == "__main__":

    main()