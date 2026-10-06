import os
import subprocess
import tempfile

import numpy as np
import sounddevice as sd
import ollama

from faster_whisper import WhisperModel
from supertonic import TTS


# ============================================================
# CONFIGURACIÓN
# ============================================================

OLLAMA_MODEL = "qwen2.5:3b"
WHISPER_MODEL = "small"
VOICE = "F4"
LANG = "es"

SAMPLE_RATE = 16000
DURACION = 5

SYSTEM_PROMPT = """
Eres ZOE, la asistente virtual de SIGEFIV.

REGLAS:
- Responde siempre y únicamente en español.
- Usa español latinoamericano natural.
- No mezcles español e inglés.
- Sé cálida, natural y conversacional.
- Responde de forma clara y breve.
- No inventes información.

Tu nombre es ZOE.
"""


# ============================================================
# NVIDIA CUDA
# ============================================================

BASE = os.path.dirname(os.path.abspath(__file__))

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
        os.add_dll_directory(path)
        os.environ["PATH"] = path + os.pathsep + os.environ["PATH"]


# ============================================================
# GRABAR
# ============================================================

def escuchar():
    print("\n🎤 Escuchando...")

    audio = sd.rec(
        int(DURACION * SAMPLE_RATE),
        samplerate=SAMPLE_RATE,
        channels=1,
        dtype="float32"
    )

    sd.wait()

    return np.squeeze(audio)


# ============================================================
# WHISPER
# ============================================================

def transcribir(audio, whisper):

    segments, info = whisper.transcribe(
        audio,
        language="es",
        vad_filter=True
    )

    texto = " ".join(
        segment.text.strip()
        for segment in segments
    )

    return texto.strip()


# ============================================================
# HABLAR
# ============================================================

def hablar(texto, tts, voice_style):

    print("\n🔊 ZOE:")
    print(texto)

    wav, duration = tts.synthesize(
        texto,
        voice_style=voice_style,
        lang=LANG
    )

    archivo = os.path.join(
        tempfile.gettempdir(),
        "zoe_respuesta.wav"
    )

    tts.save_audio(wav, archivo)

    subprocess.run(
        [
            "powershell",
            "-NoProfile",
            "-Command",
            f'(New-Object Media.SoundPlayer "{archivo}").PlaySync()'
        ],
        check=True
    )


# ============================================================
# MAIN
# ============================================================

def main():

    print("=" * 60)
    print("              ZOE VOZ - SIGEFIV")
    print("=" * 60)

    print("\nCargando Whisper...")
    whisper = WhisperModel(
        WHISPER_MODEL,
        device="cuda",
        compute_type="float16"
    )

    print("✅ Whisper GPU listo")

    print("\nCargando Supertonic...")
    tts = TTS(auto_download=True)
    voice_style = tts.get_voice_style(VOICE)

    print(f"✅ Voz {VOICE} lista")

    print("\nConectando con Ollama...")
    ollama.list()

    print(f"✅ Qwen {OLLAMA_MODEL} listo")

    mensajes = [
        {
            "role": "system",
            "content": SYSTEM_PROMPT
        }
    ]

    print("\n" + "=" * 60)
    print("ZOE está lista.")
    print("Habla durante 5 segundos.")
    print("Presiona Ctrl+C para salir.")
    print("=" * 60)

    while True:

        try:

            # ----------------------------
            # ESCUCHAR
            # ----------------------------

            audio = escuchar()

            # ----------------------------
            # TRANSCRIBIR
            # ----------------------------

            texto = transcribir(
                audio,
                whisper
            )

            if not texto:
                print("No entendí nada.")
                continue

            print("\n👤 Tú:")
            print(texto)

            # ----------------------------
            # ENVIAR A QWEN
            # ----------------------------

            mensajes.append(
                {
                    "role": "user",
                    "content": texto
                }
            )

            respuesta = ollama.chat(
                model=OLLAMA_MODEL,
                messages=mensajes
            )

            texto_zoe = respuesta["message"]["content"].strip()

            mensajes.append(
                {
                    "role": "assistant",
                    "content": texto_zoe
                }
            )

            # ----------------------------
            # HABLAR
            # ----------------------------

            hablar(
                texto_zoe,
                tts,
                voice_style
            )

        except KeyboardInterrupt:

            print("\n\nZOE: Hasta luego.")
            break

        except Exception as e:

            print("\n❌ Error:")
            print(e)


if __name__ == "__main__":
    main()