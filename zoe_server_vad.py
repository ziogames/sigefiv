import os
import tempfile
import wave
import numpy as np
import sounddevice as sd

from fastapi import FastAPI, UploadFile, File
from fastapi.responses import FileResponse
from faster_whisper import WhisperModel
from supertonic import TTS
import ollama

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

for path in CUDA_DIRS:
    if os.path.exists(path):
        os.add_dll_directory(path)
        os.environ["PATH"] = path + os.pathsep + os.environ["PATH"]


# ============================================================
# CONFIGURACIÓN
# ============================================================

SAMPLE_RATE = 16000
BLOQUE = 0.25

VAD_THRESHOLD = 0.55
SILENCIO_FINAL = 0.8
MAX_DURACION = 15

MODEL_WHISPER = "small"
MODEL_OLLAMA = "qwen2.5:3b"
VOZ = "F4"

app = FastAPI()


# ============================================================
# MODELOS
# ============================================================

print("🧠 Cargando Whisper...")
whisper = WhisperModel(
    MODEL_WHISPER,
    device="cuda",
    compute_type="float16",
)

print("🔊 Cargando Supertonic...")
tts = TTS(auto_download=True)
style = tts.get_voice_style(VOZ)

print("🤖 ZOE lista.")


# ============================================================
# PROMPT
# ============================================================

SYSTEM_PROMPT = """
Eres ZOE, la asistente virtual de SIGEFIV.

REGLA ABSOLUTA DE IDIOMA:
- Responde SIEMPRE y ÚNICAMENTE en español.
- Usa español latinoamericano natural.
- NO uses inglés.
- NO mezcles español e inglés.
- Aunque el usuario hable en inglés, responde en español.

PERSONALIDAD:
- Eres cálida.
- Eres natural.
- Eres amable.
- Eres conversacional.
- Habla como una asistente humana, no como un robot.
- Responde de forma clara y breve.

REGLA ABSOLUTA:
NO INVENTES INFORMACIÓN.
"""


mensajes = [
    {
        "role": "system",
        "content": SYSTEM_PROMPT,
    }
]


# ============================================================
# WHISPER
# ============================================================

def transcribir(audio):

    segments, info = whisper.transcribe(
        audio,
        language="es",
        task="transcribe",
        vad_filter=True,
        condition_on_previous_text=False,
    )

    texto = " ".join(
        segment.text.strip()
        for segment in segments
    ).strip()

    frases_falsas = [
        "subtítulos realizados por la comunidad de amara.org",
        "subtítulos por la comunidad de amara.org",
        "subtitulos realizados por la comunidad de amara.org",
        "subtitulos por la comunidad de amara.org",
        "subtítulos realizados por la comunidad",
        "subtitulos realizados por la comunidad",
        "amara.org",
    ]

    texto_lower = texto.lower()

    for frase in frases_falsas:
        if frase in texto_lower:
            return ""

    return texto


# ============================================================
# ZOE
# ============================================================

def preguntar_zoe(texto):

    mensajes.append(
        {
            "role": "user",
            "content": texto,
        }
    )

    respuesta = ollama.chat(
        model=MODEL_OLLAMA,
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


# ============================================================
# TTS
# ============================================================

def generar_audio(texto):

    wav, duracion = tts.synthesize(
        texto,
        voice_style=style,
        lang="es",
    )

    archivo = tempfile.NamedTemporaryFile(
        suffix=".wav",
        delete=False,
    )

    archivo.close()

    tts.save_audio(
        wav,
        archivo.name,
    )

    return archivo.name


# ============================================================
# ENDPOINT
# ============================================================

@app.get("/")
def inicio():

    return {
        "status": "ok",
        "service": "ZOE Voice Server",
        "whisper": MODEL_WHISPER,
        "ollama": MODEL_OLLAMA,
        "voice": VOZ,
        "language": "es",
    }


@app.post("/zoe/voz")
async def zoe_voz(
    audio: UploadFile = File(...)
):

    archivo_entrada = tempfile.NamedTemporaryFile(
        suffix=".wav",
        delete=False,
    )

    contenido = await audio.read()

    archivo_entrada.write(contenido)
    archivo_entrada.close()

    try:

        with wave.open(
            archivo_entrada.name,
            "rb",
        ) as wf:

            frames = wf.readframes(
                wf.getnframes()
            )

            audio_np = np.frombuffer(
                frames,
                dtype=np.int16,
            ).astype(np.float32) / 32768.0

        texto = transcribir(audio_np)

        print("🎤 Usuario:", texto)

        if not texto:

            return {
                "texto": "",
                "respuesta": "",
            }

        respuesta = preguntar_zoe(texto)

        print("🤖 ZOE:", respuesta)

        archivo_salida = generar_audio(
            respuesta
        )

        return FileResponse(
            archivo_salida,
            media_type="audio/wav",
            filename="zoe.wav",
        )

    finally:

        try:
            os.remove(archivo_entrada.name)
        except:
            pass