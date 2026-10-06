import os
import tempfile
import time

import ollama
import torch

from fastapi import FastAPI, File, UploadFile
from fastapi.responses import FileResponse
from faster_whisper import WhisperModel
from supertonic import TTS


# ============================================================
# CONFIGURACIÓN
# ============================================================

OLLAMA_MODEL = "qwen2.5:3b"
WHISPER_MODEL = "small"

VOICE = "F4"
LANG = "es"


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
# ZOE
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
# FASTAPI
# ============================================================

app = FastAPI(
    title="ZOE Voice Server",
    version="1.0"
)


# ============================================================
# CARGAR MODELOS
# ============================================================

print()
print("=" * 60)
print("             ZOE VOICE SERVER")
print("=" * 60)


print("\n🧠 Cargando Whisper...")

whisper = WhisperModel(
    WHISPER_MODEL,
    device="cuda",
    compute_type="float16"
)

print("✅ Whisper GPU listo")


print("\n🔊 Cargando Supertonic...")

tts = TTS(
    auto_download=True
)

voice_style = tts.get_voice_style(
    VOICE
)

print(
    f"✅ Supertonic {VOICE} listo"
)


print("\n🤖 Conectando con Ollama...")

ollama.list()

print(
    f"✅ Qwen {OLLAMA_MODEL} listo"
)


# ============================================================
# HISTORIAL
# ============================================================

mensajes = [
    {
        "role": "system",
        "content": SYSTEM_PROMPT
    }
]


# ============================================================
# TRANSCRIBIR
# ============================================================

def transcribir(
    archivo
):

    print(
        "\n🧠 Transcribiendo..."
    )

    segments, info = whisper.transcribe(
        archivo,
        language="es",
        task="transcribe",
        vad_filter=True,
        condition_on_previous_text=False
    )

    texto = " ".join(
        segment.text.strip()
        for segment in segments
        if segment.text.strip()
    )

    texto = texto.strip()

    if not texto:
        return ""


    # ========================================================
    # FILTROS
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
                "⚠️ Transcripción descartada:"
            )

            print(
                texto
            )

            return ""


    return texto


# ============================================================
# GENERAR RESPUESTA
# ============================================================

def responder(
    texto
):

    print(
        "\n👤 Usuario:"
    )

    print(
        texto
    )


    mensajes.append(
        {
            "role": "user",
            "content": texto
        }
    )


    print(
        "\n🤖 Qwen pensando..."
    )


    respuesta = ollama.chat(
        model=OLLAMA_MODEL,
        messages=mensajes
    )


    texto_zoe = (
        respuesta["message"]["content"]
        .strip()
    )


    mensajes.append(
        {
            "role": "assistant",
            "content": texto_zoe
        }
    )


    print(
        "\n🔊 ZOE:"
    )

    print(
        texto_zoe
    )


    return texto_zoe


# ============================================================
# GENERAR AUDIO
# ============================================================

def generar_audio(
    texto
):

    print(
        "\n🔊 Generando voz..."
    )


    wav, duration = tts.synthesize(
        texto,
        voice_style=voice_style,
        lang=LANG
    )


    archivo = os.path.join(
        tempfile.gettempdir(),
        "zoe_server_"
        f"{int(time.time() * 1000)}.wav"
    )


    tts.save_audio(
        wav,
        archivo
    )


    print(
        "✅ Audio generado"
    )


    return archivo


# ============================================================
# ENDPOINT DE SALUD
# ============================================================

@app.get("/")
def inicio():

    return {
        "status": "ok",
        "service": "ZOE Voice Server",
        "whisper": WHISPER_MODEL,
        "ollama": OLLAMA_MODEL,
        "voice": VOICE,
        "language": LANG
    }


# ============================================================
# ENDPOINT DE VOZ
# ============================================================

@app.post("/zoe/voz")
async def zoe_voz(
    audio: UploadFile = File(...)
):

    archivo_entrada = os.path.join(
        tempfile.gettempdir(),
        "zoe_input_"
        f"{int(time.time() * 1000)}"
        f"_{audio.filename}"
    )


    # --------------------------------------------------------
    # Guardar audio recibido
    # --------------------------------------------------------

    contenido = await audio.read()

    with open(
        archivo_entrada,
        "wb"
    ) as f:

        f.write(
            contenido
        )


    try:

        # ----------------------------------------------------
        # Whisper
        # ----------------------------------------------------

        texto = transcribir(
            archivo_entrada
        )


        if not texto:

            return {
                "ok": False,
                "mensaje": "No se detectó una frase válida."
            }


        # ----------------------------------------------------
        # Qwen
        # ----------------------------------------------------

        texto_zoe = responder(
            texto
        )


        # ----------------------------------------------------
        # Supertonic
        # ----------------------------------------------------

        archivo_salida = generar_audio(
            texto_zoe
        )


        return FileResponse(
            archivo_salida,
            media_type="audio/wav",
            filename="zoe.wav"
        )


    finally:

        try:

            if os.path.exists(
                archivo_entrada
            ):

                os.remove(
                    archivo_entrada
                )

        except Exception:

            pass