import os
import uuid
import tempfile
import asyncio
import json
import re
import time
import logging
from pathlib import Path
from typing import Dict, Any, Optional
from pydantic import BaseModel, Field

# ============================================================
# CONFIGURACIÓN DE LOGGING
# ============================================================
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("ZOE-Gateway")

BASE_DIR = Path(__file__).resolve().parent

# ============================================================
# CUDA
# ============================================================

CUDA_DIRS = [
    BASE_DIR / ".venv-supertonic" / "Lib" / "site-packages" / "nvidia" / "cublas" / "bin",
    BASE_DIR / ".venv-supertonic" / "Lib" / "site-packages" / "nvidia" / "cudnn" / "bin",
]

for dll_dir in CUDA_DIRS:
    if dll_dir.is_dir():
        try:
            os.add_dll_directory(str(dll_dir))
        except Exception:
            pass

        os.environ["PATH"] = (
            str(dll_dir)
            + os.pathsep
            + os.environ.get("PATH", "")
        )

# ============================================================
# IMPORTACIONES
# ============================================================

import numpy as np
import soundfile as sf

from fastapi import FastAPI, WebSocket, WebSocketDisconnect, status
from fastapi.responses import JSONResponse

from faster_whisper import WhisperModel
from supertonic import TTS
import ollama

# ============================================================
# CONFIGURACIÓN GENERAL
# ============================================================

WHISPER_MODEL = "small"
OLLAMA_MODEL = "qwen2.5:3b"
VOICE = "F4"
LANGUAGE = "es"
SAMPLE_RATE = 16000

HOST = "0.0.0.0"
PORT = 8000
SESION_TTL_SECONDS = 3600  # 1 hora

# ============================================================
# FASTAPI
# ============================================================

app = FastAPI(
    title="ZOE Voice Gateway",
    version="2.0.0",
)

# ============================================================
# GESTIÓN DE SESIONES CON TTL
# ============================================================

sesiones: Dict[str, Dict[str, Any]] = {}

def limpiar_sesiones_inactivas():
    """Elimina sesiones inactivas para evitar fugas de memoria RAM."""
    ahora = time.time()
    expiradas = [
        sid for sid, data in sesiones.items()
        if ahora - data.get("last_active", ahora) > SESION_TTL_SECONDS
    ]
    for sid in expiradas:
        del sesiones[sid]
        logger.info(f"🧹 Sesión expirada y eliminada: {sid}")

def nueva_sesion() -> str:
    limpiar_sesiones_inactivas()
    session_id = uuid.uuid4().hex

    sesiones[session_id] = {
        "last_active": time.time(),
        "mensajes": [
            {
                "role": "system",
                "content": """
Eres ZOE, la asistente virtual de SIGEFIV.

REGLA ABSOLUTA DE IDIOMA:
- Responde SIEMPRE y ÚNICAMENTE en español.
- Usa español latinoamericano natural.
- No mezcles español e inglés.
- Aunque el usuario hable en inglés, responde en español.
- Mantén esta regla durante toda la conversación.

IDENTIDAD:
- Tú eres ZOE.
- Cuando hables de ti misma, usa siempre primera persona: "soy", "puedo", "te ayudo", "estoy".
- NUNCA digas que el usuario es ZOE.
- Si el usuario pregunta "¿quién eres?", responde claramente que eres ZOE, la asistente virtual de SIGEFIV.

PERSONALIDAD:
- Eres cálida, natural, amable y conversacional.
- Habla como una asistente humana, no como un robot.
- Responde de forma clara, sencilla y breve.
- Usa puntuación y expresiones naturales en español, por ejemplo: "¡Hola!".
""",
            }
        ]
    }
    logger.info(f"✨ Nueva sesión creada: {session_id}")
    return session_id

def actualizar_actividad(session_id: str):
    if session_id in sesiones:
        sesiones[session_id]["last_active"] = time.time()


# ============================================================
# CARGAR MODELOS
# ============================================================

logger.info("=" * 60)
logger.info("🤖 INICIANDO ZOE VOICE GATEWAY")
logger.info("=" * 60)

logger.info("🧠 Cargando Whisper...")
whisper = WhisperModel(
    WHISPER_MODEL,
    device="cuda",
    compute_type="float16",
)

logger.info("🔊 Cargando Supertonic...")
tts = TTS(auto_download=True)
voice_style = tts.get_voice_style(VOICE)

logger.info("🤖 ZOE lista y operativa.")


# ============================================================
# FUNCIONES NÚCLEO (WHISPER, OLLAMA, TTS)
# ============================================================

def transcribir(audio: np.ndarray) -> str:
    if audio is None or len(audio) == 0:
        return ""

    audio = np.asarray(audio, dtype=np.float32)

    if float(np.max(np.abs(audio))) < 0.005:
        return ""

    try:
        segments, _ = whisper.transcribe(
            audio,
            language=LANGUAGE,
            task="transcribe",
            vad_filter=True,
            condition_on_previous_text=False,
            beam_size=5,
        )

        texto = " ".join(
            segmento.text.strip()
            for segmento in segments
        ).strip()

    except Exception as e:
        logger.error(f"❌ Error en Whisper: {e}")
        return ""

    frases_falsas = [
        "subtítulos realizados por la comunidad de amara.org",
        "subtítulos por la comunidad de amara.org",
        "subtitulos realizados por la comunidad",
        "amara.org",
    ]

    if any(frase in texto.lower() for frase in frases_falsas):
        return ""

    return texto


def preguntar_zoe(session_id: str, texto: str) -> str:
    actualizar_actividad(session_id)
    sesion = sesiones[session_id]
    mensajes = sesion["mensajes"]

    mensajes.append({
        "role": "user",
        "content": texto,
    })

    try:
        respuesta = ollama.chat(
            model=OLLAMA_MODEL,
            messages=mensajes,
        )

        contenido = respuesta["message"]["content"].strip()

        mensajes.append({
            "role": "assistant",
            "content": contenido,
        })

        return contenido

    except Exception as e:
        logger.error(f"❌ Error en Ollama: {e}")
        return "Lo siento, tuve un problema al procesar tu solicitud."


def preparar_texto_voz(texto: str) -> str:
    if not texto:
        return ""

    texto = str(texto).strip()
    texto = " ".join(texto.split())

    # Normalización de dinero
    texto = re.sub(r"S/\.?\s*([0-9]+(?:[.,][0-9]{1,2})?)", r"\1 soles", texto, flags=re.IGNORECASE)
    
    # Símbolos
    texto = texto.replace("%", " por ciento").replace("&", " y ")

    # Pausas conversacionales y limpieza
    texto = re.sub(r"\s*:\s*", ": ", texto)
    texto = re.sub(r"\s*;\s*", "; ", texto)
    texto = re.sub(r"^(Hola|Buenos días|Buenas tardes|Buenas noches)([,.]?)(\s+)", r"\1\2... ", texto, flags=re.IGNORECASE)
    texto = re.sub(r"\s+¿", "... ¿", texto)

    return re.sub(r"\s+", " ", texto).strip()


def sintetizar(texto: str) -> Path:
    texto_voz = preparar_texto_voz(texto)
    logger.info(f"🔊 Sintetizando texto para ZOE: {texto_voz}")

    wav, _ = tts.synthesize(
        texto_voz,
        voice_style=voice_style,
        lang=LANGUAGE,
    )

    nombre = f"zoe_{uuid.uuid4().hex}.wav"
    ruta = Path(tempfile.gettempdir()) / nombre
    tts.save_audio(wav, str(ruta))

    return ruta


# ============================================================
# ENDPOINTS REST
# ============================================================

@app.get("/")
async def health():
    return {
        "status": "ok",
        "service": "ZOE Voice Gateway",
        "version": "2.0.0",
        "whisper": WHISPER_MODEL,
        "ollama": OLLAMA_MODEL,
        "voice": VOICE,
        "transport": "websocket",
    }


@app.post("/zoe/session")
async def crear_sesion_endpoint():
    session_id = nueva_sesion()
    return {"status": "ok", "session_id": session_id}


# ============================================================
# WEBSOCKET MEJORADO CON BARGE-IN Y VALIDACIÓN
# ============================================================

@app.websocket("/ws/zoe")
async def websocket_zoe(websocket: WebSocket):
    await websocket.accept()
    session_id = nueva_sesion()
    audio_buffer = bytearray()
    
    # Referencia a la tarea actual de síntesis/envío de audio para permitir cancelación limpia (Barge-In)
    current_audio_task: Optional[asyncio.Task] = None

    logger.info(f"📱 Cliente Android conectado vía WebSocket: {session_id}")

    try:
        await websocket.send_json({
            "type": "session",
            "session_id": session_id,
            "audio": {
                "format": "pcm_s16le",
                "sample_rate": SAMPLE_RATE,
                "channels": 1,
            },
        })

        while True:
            mensaje = await websocket.receive()

            # Desconexión de red limpia
            if mensaje.get("type") == "websocket.disconnect":
                break

            # Manejo de Audio Binario (PCM)
            if mensaje.get("bytes") is not None:
                datos_audio = mensaje["bytes"]
                if datos_audio:
                    audio_buffer.extend(datos_audio)
                continue

            # Manejo de Mensajes JSON
            if mensaje.get("text") is None:
                continue

            try:
                datos = json.loads(mensaje["text"])
            except json.JSONDecodeError:
                await websocket.send_json({"type": "error", "message": "Mensaje JSON inválido."})
                continue

            tipo = datos.get("type")
            actualizar_actividad(session_id)

            if tipo == "ping":
                await websocket.send_json({"type": "pong"})

            elif tipo == "text":
                texto = str(datos.get("text", "")).strip()
                if not texto:
                    continue

                respuesta = await asyncio.to_thread(preguntar_zoe, session_id, texto)
                await websocket.send_json({"type": "response", "text": respuesta})

            elif tipo == "start_audio":
                audio_buffer.clear()
                # Si llega un inicio de audio nuevo y hay síntesis previa sonando, la cancelamos
                if current_audio_task and not current_audio_task.done():
                    current_audio_task.cancel()
                
                await websocket.send_json({"type": "audio_started"})

            elif tipo == "end_audio":
                if not audio_buffer:
                    await websocket.send_json({"type": "error", "message": "No se recibió audio."})
                    continue

                datos_pcm = bytes(audio_buffer)
                audio_buffer.clear()

                audio_int16 = np.frombuffer(datos_pcm, dtype="<i2")
                audio_float = audio_int16.astype(np.float32) / 32768.0

                await websocket.send_json({"type": "transcribing"})

                texto = await asyncio.to_thread(transcribir, audio_float)

                if not texto:
                    await websocket.send_json({"type": "no_text"})
                    continue

                await websocket.send_json({"type": "transcription", "text": texto})
                await websocket.send_json({"type": "awaiting_backend"})

            elif tipo == "response_text":
                respuesta = str(datos.get("text", "")).strip()
                if not respuesta:
                    continue

                # Función interna asíncrona para manejar el envío seguro del archivo de audio con cancelación
                async def enviar_audio_generado():
                    ruta_audio = None
                    try:
                        await websocket.send_json({"type": "synthesizing", "text": respuesta})
                        
                        ruta_audio = await asyncio.to_thread(sintetizar, respuesta)

                        if not ruta_audio.exists() or ruta_audio.stat().st_size <= 44:
                            raise RuntimeError("No se generó un WAV válido.")

                        audio_wav = await asyncio.to_thread(ruta_audio.read_bytes)

                        await websocket.send_json({"type": "audio_start", "format": "wav", "size": len(audio_wav)})
                        await websocket.send_bytes(audio_wav)
                        await websocket.send_json({"type": "audio_end"})

                    except asyncio.CancelledError:
                        logger.info("⚠️ Síntesis de audio interrumpida por Barge-In.")
                        raise
                    except Exception as e:
                        logger.error(f"❌ Error generando/enviando audio: {e}")
                        await websocket.send_json({"type": "error", "message": "No se pudo generar el audio de ZOE."})
                    finally:
                        if ruta_audio:
                            try:
                                ruta_audio.unlink(missing_ok=True)
                            except Exception:
                                pass

                # Cancelar tarea previa de audio si existía y seguía activa
                if current_audio_task and not current_audio_task.done():
                    current_audio_task.cancel()

                current_audio_task = asyncio.create_task(enviar_audio_generado())

            elif tipo == "interrupt":
                # Barge-in: El usuario interrumpió a Zoe
                if current_audio_task and not current_audio_task.done():
                    current_audio_task.cancel()
                
                audio_buffer.clear()
                await websocket.send_json({"type": "interrupted"})
                logger.info(f"⚡ Sesión {session_id}: Interrumpido por el usuario (Barge-In).")

            elif tipo == "close":
                await websocket.close()
                break

            else:
                await websocket.send_json({"type": "error", "message": f"Tipo no soportado: {tipo}"})

    except WebSocketDisconnect:
        logger.info(f"📴 Cliente Android desconectado: {session_id}")
    except Exception as e:
        logger.error(f"❌ Error crítico en WebSocket ({session_id}): {e}")
    finally:
        if current_audio_task and not current_audio_task.done():
            current_audio_task.cancel()
        if session_id in sesiones:
            del sesiones[session_id]


# ============================================================
# INICIO DEL SERVIDOR
# ============================================================

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host=HOST, port=PORT)