import os
import subprocess
import tempfile

import ollama
from supertonic import TTS


MODEL = "qwen2.5:3b"
VOICE = "F4"
LANG = "es"

SYSTEM_PROMPT = """
Eres ZOE, la asistente virtual de SIGEFIV.

REGLAS:
- Responde siempre y únicamente en español.
- Usa español latinoamericano natural.
- No mezcles español e inglés.
- Sé cálida, natural y conversacional.
- Responde de forma clara y breve.
- No inventes información.
"""


def hablar(texto, tts, voice_style):
    print("\n🔊 ZOE:")
    print(texto)

    wav, duration = tts.synthesize(
        texto,
        voice_style=voice_style,
        lang=LANG,
    )

    archivo = os.path.join(
        tempfile.gettempdir(),
        "zoe_respuesta.wav"
    )

    tts.save_audio(wav, archivo)

    # Reproducir con Windows
    subprocess.run(
        [
            "powershell",
            "-NoProfile",
            "-Command",
            f'(New-Object Media.SoundPlayer "{archivo}").PlaySync()'
        ],
        check=True,
    )


def main():
    print("Inicializando ZOE...")
    print(f"Modelo: {MODEL}")
    print(f"Voz: {VOICE}")
    print(f"Idioma: español ({LANG})")

    # Cargar Supertonic
    tts = TTS(auto_download=True)
    voice_style = tts.get_voice_style(VOICE)

    # Historial de conversación
    mensajes = [
        {
            "role": "system",
            "content": SYSTEM_PROMPT,
        }
    ]

    print("\nZOE está lista.")
    print("Escribe 'salir' para terminar.\n")

    while True:
        usuario = input("Tú: ").strip()

        if not usuario:
            continue

        if usuario.lower() in ("salir", "exit", "quit"):
            print("ZOE: Hasta luego.")
            break

        mensajes.append(
            {
                "role": "user",
                "content": usuario,
            }
        )

        try:
            respuesta = ollama.chat(
                model=MODEL,
                messages=mensajes,
            )

            texto = respuesta["message"]["content"].strip()

            mensajes.append(
                {
                    "role": "assistant",
                    "content": texto,
                }
            )

            hablar(
                texto,
                tts,
                voice_style,
            )

        except Exception as e:
            print("\n❌ Error:")
            print(e)


if __name__ == "__main__":
    main()