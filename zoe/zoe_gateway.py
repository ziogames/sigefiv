import os
import io
import uuid
import tempfile
import asyncio
import json
import re
import time
import logging
from pathlib import Path
from contextlib import asynccontextmanager
from typing import Dict, List, Any, Optional, AsyncGenerator

from pydantic import BaseModel, Field
import numpy as np
import soundfile as sf
from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from faster_whisper import WhisperModel
from supertonic import TTS
from ollama import AsyncClient as OllamaAsyncClient

# ============================================================
# 1. CONFIGURACIÓN DE LOGGING
# ============================================================
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(name)s: %(message)s",
)
logger = logging.getLogger("ZOE-Gateway")

BASE_DIR = Path(__file__).resolve().parent

# ============================================================
# 2. CONFIGURACIÓN CUDA / DLLs
# ============================================================
CUDA_DIRS = [
    BASE_DIR / ".venv-supertonic" / "Lib" / "site-packages" / "nvidia" / "cublas" / "bin",
    BASE_DIR / ".venv-supertonic" / "Lib" / "site-packages" / "nvidia" / "cudnn" / "bin",
]

for dll_dir in CUDA_DIRS:
    if dll_dir.is_dir():
        if hasattr(os, "add_dll_directory"):
            try:
                os.add_dll_directory(str(dll_dir))
            except Exception:
                pass
        os.environ["PATH"] = f"{dll_dir}{os.pathsep}{os.environ.get('PATH', '')}"


# ============================================================
# 3. CONFIGURACIÓN TIPADA CENTRALIZADA
# ============================================================
class GatewayConfig(BaseModel):
    whisper_model: str = Field(default=os.getenv("WHISPER_MODEL", "small"))
    whisper_device: str = Field(default=os.getenv("WHISPER_DEVICE", "cuda"))
    whisper_compute: str = Field(default=os.getenv("WHISPER_COMPUTE", "float16"))
    ollama_model: str = Field(default=os.getenv("OLLAMA_MODEL", "qwen2.5:3b"))
    ollama_host: str = Field(default=os.getenv("OLLAMA_HOST", "http://127.0.0.1:11434"))
    voice: str = Field(default=os.getenv("ZOE_VOICE", "F4"))
    language: str = Field(default=os.getenv("ZOE_LANGUAGE", "es"))
    sample_rate: int = Field(default=int(os.getenv("ZOE_SAMPLE_RATE", "16000")))
    host: str = Field(default=os.getenv("HOST", "0.0.0.0"))
    port: int = Field(default=int(os.getenv("PORT", "8000")))
    session_ttl_seconds: int = Field(default=int(os.getenv("SESION_TTL_SECONDS", "3600")))
    max_history_messages: int = Field(default=20)  # Evita degradación de latencia en Ollama
    max_audio_seconds: int = Field(default=60)     # Límite de seguridad en RAM por grabación

    @property
    def max_audio_bytes(self) -> int:
        # 2 bytes por muestra (int16) * sample_rate * segundos
        return self.sample_rate * 2 * self.max_audio_seconds


config = GatewayConfig()

SYSTEM_PROMPT = """Eres ZOE, la asistente virtual de SIGEFIV.

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
- Usa puntuación y expresiones naturales en español, por ejemplo: "¡Hola!"."""


# ============================================================
# 4. EXPRESIONES REGULARES Y CONSTANTES PRECOMPILADAS
# ============================================================
RE_SOLES = re.compile(r"S/\.?\s*([0-9]+(?:[.,][0-9]{1,2})?)", flags=re.IGNORECASE)
RE_COLON = re.compile(r"\s*:\s*")
RE_SEMICOLON = re.compile(r"\s*;\s*")
RE_SALUDO = re.compile(
    r"^(Hola|Buenos días|Buenas tardes|Buenas noches)([,.]?)(\s+)",
    flags=re.IGNORECASE,
)
RE_PREGUNTA = re.compile(r"\s+¿")
RE_ESPACIOS = re.compile(r"\s+")

HALLUCINATION_PHRASES = (
    "subtítulos realizados por la comunidad de amara.org",
    "subtítulos por la comunidad de amara.org",
    "subtitulos realizados por la comunidad",
    "amara.org",
)


# ============================================================
# 5. GESTOR DE SESIONES CON VENTANA DESLIZANTE
# ============================================================
class SessionData:
    __slots__ = ("session_id", "last_active", "mensajes")

    def __init__(self, session_id: str):
        self.session_id = session_id
        self.last_active = time.monotonic()
        self.mensajes: List[Dict[str, str]] = [
            {"role": "system", "content": SYSTEM_PROMPT}
        ]

    def touch(self) -> None:
        self.last_active = time.monotonic()

    def agregar_mensaje(self, role: str, content: str) -> None:
        self.touch()
        self.mensajes.append({"role": role, "content": content})
        # Mantener system prompt (índice 0) + últimos N mensajes
        if len(self.mensajes) > config.max_history_messages + 1:
            self.mensajes = [self.mensajes[0]] + self.mensajes[-config.max_history_messages:]


class SessionManager:
    def __init__(self, ttl_seconds: int):
        self.ttl = ttl_seconds
        self._sessions: Dict[str, SessionData] = {}

    def create(self) -> SessionData:
        sid = uuid.uuid4().hex
        session = SessionData(sid)
        self._sessions[sid] = session
        logger.info(f"✨ Nueva sesión creada: {sid}")
        return session

    def get(self, sid: str) -> Optional[SessionData]:
        session = self._sessions.get(sid)
        if session:
            session.touch()
        return session

    def remove(self, sid: str) -> None:
        if self._sessions.pop(sid, None) is not None:
            logger.info(f"🗑️ Sesión cerrada y liberada: {sid}")

    def cleanup_expired(self) -> int:
        ahora = time.monotonic()
        expiradas = [
            sid for sid, s in self._sessions.items()
            if (ahora - s.last_active) > self.ttl
        ]
        for sid in expiradas:
            del self._sessions[sid]
            logger.info(f"🧹 Sesión expirada por TTL: {sid}")
        return len(expiradas)

    @property
    def active_count(self) -> int:
        return len(self._sessions)


session_manager = SessionManager(ttl_seconds=config.session_ttl_seconds)


# ============================================================
# 6. MOTOR DE IA Y HARDWARE (PROTECCIÓN DE CONCURRENCIA GPU)
# ============================================================
class ZOEAIEngine:
    def __init__(self):
        self.whisper: Optional[WhisperModel] = None
        self.tts: Optional[TTS] = None
        self.voice_style: Optional[Any] = None
        self.ollama_client: Optional[OllamaAsyncClient] = None

        # Locks asíncronos para evitar colisiones de hilos en CUDA
        self.stt_lock = asyncio.Lock()
        self.tts_lock = asyncio.Lock()

    def load_models(self) -> None:
        logger.info("=" * 60)
        logger.info("🤖 INICIANDO ZOE VOICE GATEWAY (Modo Alto Rendimiento)")
        logger.info("=" * 60)

        logger.info(f"🧠 Cargando Faster-Whisper ({config.whisper_model} en {config.whisper_device})...")
        self.whisper = WhisperModel(
            config.whisper_model,
            device=config.whisper_device,
            compute_type=config.whisper_compute,
        )

        logger.info(f"🔊 Cargando Supertonic TTS (Voz: {config.voice})...")
        self.tts = TTS(auto_download=True)
        self.voice_style = self.tts.get_voice_style(config.voice)

        self.ollama_client = OllamaAsyncClient(host=config.ollama_host)

        # Warmup: inicializa los grafos CUDA para que la primera consulta no tenga lag
        logger.info("🔥 Ejecutando Warmup de GPU...")
        dummy_audio = np.zeros(config.sample_rate // 2, dtype=np.float32)
        list(self.whisper.transcribe(dummy_audio, language=config.language)[0])
        self.tts.synthesize("Hola.", voice_style=self.voice_style, lang=config.language)
        logger.info("✅ Modelos cargados y precalentados en VRAM.")

    def _transcribe_sync(self, audio: np.ndarray) -> str:
        if self.whisper is None or audio is None or audio.size == 0:
            return ""

        # Filtro rápido de silencio en CPU antes de invocar GPU
        if float(np.max(np.abs(audio))) < 0.005:
            return ""

        try:
            segments, _ = self.whisper.transcribe(
                audio,
                language=config.language,
                task="transcribe",
                vad_filter=True,
                condition_on_previous_text=False,
                beam_size=5,
            )
            texto = " ".join(seg.text.strip() for seg in segments).strip()
        except Exception as e:
            logger.error(f"❌ Error en Whisper: {e}")
            return ""

        texto_lower = texto.lower()
        if any(frase in texto_lower for frase in HALLUCINATION_PHRASES):
            return ""

        return texto

    async def transcribir(self, audio: np.ndarray) -> str:
        async with self.stt_lock:
            return await asyncio.to_thread(self._transcribe_sync, audio)

    async def preguntar_ollama(self, session: SessionData, texto: str) -> str:
        session.agregar_mensaje("user", texto)
        try:
            respuesta = await self.ollama_client.chat(
                model=config.ollama_model,
                messages=session.mensajes,
                keep_alive="30m",  # Mantiene el modelo cargado en VRAM
            )
            contenido = respuesta["message"]["content"].strip()
            session.agregar_mensaje("assistant", contenido)
            return contenido
        except Exception as e:
            logger.error(f"❌ Error en Ollama: {e}")
            return "Lo siento, tuve un problema al procesar tu solicitud."

    @staticmethod
    def preparar_texto_voz(texto: str) -> str:
        if not texto:
            return ""
        texto = " ".join(str(texto).strip().split())
        texto = RE_SOLES.sub(r"\1 soles", texto)
        texto = texto.replace("%", " por ciento").replace("&", " y ")
        texto = RE_COLON.sub(": ", texto)
        texto = RE_SEMICOLON.sub("; ", texto)
        texto = RE_SALUDO.sub(r"\1\2... ", texto)
        texto = RE_PREGUNTA.sub("... ¿", texto)
        return RE_ESPACIOS.sub(" ", texto).strip()

    def _sintetizar_bytes_sync(self, texto: str) -> bytes:
        texto_voz = self.preparar_texto_voz(texto)
        if not texto_voz:
            raise ValueError("Texto vacío tras normalización.")

        logger.info(f"🔊 Sintetizando: {texto_voz}")
        wav, _ = self.tts.synthesize(
            texto_voz,
            voice_style=self.voice_style,
            lang=config.language,
        )

        # Intento 1: Serialización directa en memoria RAM (Cero uso de disco)
        sr = getattr(self.tts, "sample_rate", None)
        if sr and isinstance(wav, np.ndarray):
            buffer = io.BytesIO()
            sf.write(buffer, np.squeeze(wav), sr, format="WAV", subtype="PCM_16")
            wav_bytes = buffer.getvalue()
            if len(wav_bytes) > 44:
                return wav_bytes

        # Fallback seguro: usa el método nativo del SDK sobre archivo temporal
        ruta = Path(tempfile.gettempdir()) / f"zoe_{uuid.uuid4().hex}.wav"
        try:
            self.tts.save_audio(wav, str(ruta))
            if not ruta.exists() or ruta.stat().st_size <= 44:
                raise RuntimeError("No se generó un WAV válido.")
            return ruta.read_bytes()
        finally:
            ruta.unlink(missing_ok=True)

    async def sintetizar_bytes(self, texto: str) -> bytes:
        async with self.tts_lock:
            return await asyncio.to_thread(self._sintetizar_bytes_sync, texto)


engine = ZOEAIEngine()


# ============================================================
# 7. CICLO DE VIDA (LIFESPAN) Y RECOLECTOR EN SEGUNDO PLANO
# ============================================================
async def _cleanup_loop() -> None:
    """Tarea en segundo plano que limpia sesiones inactivas cada 5 minutos."""
    while True:
        await asyncio.sleep(300)
        session_manager.cleanup_expired()


@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncGenerator[None, None]:
    await asyncio.to_thread(engine.load_models)
    cleanup_task = asyncio.create_task(_cleanup_loop())
    logger.info("🚀 ZOE lista y operativa.")
    try:
        yield
    finally:
        cleanup_task.cancel()
        logger.info("🛑 Apagando ZOE Voice Gateway...")


app = FastAPI(
    title="ZOE Voice Gateway",
    version="2.2.0",
    lifespan=lifespan,
)


# ============================================================
# 8. ENDPOINTS REST
# ============================================================
@app.get("/")
async def health():
    return {
        "status": "ok",
        "service": "ZOE Voice Gateway",
        "version": "2.2.0",
        "whisper": config.whisper_model,
        "ollama": config.ollama_model,
        "voice": config.voice,
        "active_sessions": session_manager.active_count,
        "transport": "websocket",
    }


@app.post("/zoe/session")
async def crear_sesion_endpoint():
    session = session_manager.create()
    return {"status": "ok", "session_id": session.session_id}


# ============================================================
# 9. WEBSOCKET CON BARGE-IN Y ESCRITURA SINCRONIZADA
# ============================================================
@app.websocket("/ws/zoe")
async def websocket_zoe(websocket: WebSocket):
    await websocket.accept()
    session = session_manager.create()
    session_id = session.session_id
    audio_buffer = bytearray()

    current_audio_task: Optional[asyncio.Task] = None
    send_lock = asyncio.Lock()  # Evita colisiones entre tareas al escribir en el socket

    async def safe_send_json(payload: Dict[str, Any]) -> None:
        async with send_lock:
            await websocket.send_json(payload)

    async def safe_send_bytes(data: bytes) -> None:
        async with send_lock:
            await websocket.send_bytes(data)

    async def cancelar_audio_en_curso() -> None:
        nonlocal current_audio_task
        if current_audio_task and not current_audio_task.done():
            current_audio_task.cancel()
            try:
                await current_audio_task
            except asyncio.CancelledError:
                pass
        current_audio_task = None

    logger.info(f"📱 Cliente Android conectado vía WebSocket: {session_id}")

    try:
        await safe_send_json({
            "type": "session",
            "session_id": session_id,
            "audio": {
                "format": "pcm_s16le",
                "sample_rate": config.sample_rate,
                "channels": 1,
            },
        })

        while True:
            mensaje = await websocket.receive()

            if mensaje.get("type") == "websocket.disconnect":
                break

            # 1. Recepción de fragmentos binarios PCM
            datos_audio = mensaje.get("bytes")
            if datos_audio is not None:
                if datos_audio:
                    if len(audio_buffer) + len(datos_audio) <= config.max_audio_bytes:
                        audio_buffer.extend(datos_audio)
                    else:
                        logger.warning(f"⚠️ Límite de buffer alcanzado en sesión {session_id}")
                continue

            # 2. Recepción de comandos JSON
            texto_raw = mensaje.get("text")
            if texto_raw is None:
                continue

            try:
                datos = json.loads(texto_raw)
            except json.JSONDecodeError:
                await safe_send_json({"type": "error", "message": "Mensaje JSON inválido."})
                continue

            tipo = datos.get("type")
            session.touch()

            if tipo == "ping":
                await safe_send_json({"type": "pong"})

            elif tipo == "text":
                texto = str(datos.get("text", "")).strip()
                if not texto:
                    continue
                respuesta = await engine.preguntar_ollama(session, texto)
                await safe_send_json({"type": "response", "text": respuesta})

            elif tipo == "start_audio":
                audio_buffer.clear()
                await cancelar_audio_en_curso()
                await safe_send_json({"type": "audio_started"})

            elif tipo == "end_audio":
                if not audio_buffer:
                    await safe_send_json({"type": "error", "message": "No se recibió audio."})
                    continue

                # Conversión vectorizada sin copias intermedias innecesarias
                audio_int16 = np.frombuffer(audio_buffer, dtype="<i2")
                audio_float = audio_int16.astype(np.float32) * (1.0 / 32768.0)
                audio_buffer.clear()

                await safe_send_json({"type": "transcribing"})

                texto = await engine.transcribir(audio_float)
                if not texto:
                    await safe_send_json({"type": "no_text"})
                    continue

                await safe_send_json({"type": "transcription", "text": texto})
                await safe_send_json({"type": "awaiting_backend"})

            elif tipo == "response_text":
                respuesta = str(datos.get("text", "")).strip()
                if not respuesta:
                    continue

                await cancelar_audio_en_curso()

                async def enviar_audio_generado(texto_a_sintetizar: str):
                    try:
                        await safe_send_json({"type": "synthesizing", "text": texto_a_sintetizar})
                        audio_wav = await engine.sintetizar_bytes(texto_a_sintetizar)

                        # Enviamos cabecera, bytes y cierre en una secuencia atómica
                        async with send_lock:
                            await websocket.send_json({
                                "type": "audio_start",
                                "format": "wav",
                                "size": len(audio_wav),
                            })
                            await websocket.send_bytes(audio_wav)
                            await websocket.send_json({"type": "audio_end"})

                    except asyncio.CancelledError:
                        logger.info(f"⚠️ Síntesis cancelada por Barge-In ({session_id}).")
                        raise
                    except Exception as e:
                        logger.error(f"❌ Error generando/enviando audio: {e}")
                        await safe_send_json({
                            "type": "error",
                            "message": "No se pudo generar el audio de ZOE.",
                        })

                current_audio_task = asyncio.create_task(enviar_audio_generado(respuesta))

            elif tipo == "interrupt":
                await cancelar_audio_en_curso()
                audio_buffer.clear()
                await safe_send_json({"type": "interrupted"})
                logger.info(f"⚡ Sesión {session_id}: Interrumpida por el usuario (Barge-In).")

            elif tipo == "close":
                await websocket.close()
                break

            else:
                await safe_send_json({"type": "error", "message": f"Tipo no soportado: {tipo}"})

    except WebSocketDisconnect:
        logger.info(f"📴 Cliente Android desconectado: {session_id}")
    except Exception as e:
        logger.error(f"❌ Error crítico en WebSocket ({session_id}): {e}")
    finally:
        await cancelar_audio_en_curso()
        session_manager.remove(session_id)


# ============================================================
# 10. INICIO DEL SERVIDOR
# ============================================================
if __name__ == "__main__":
    import uvicorn

    uvicorn.run(
        app,
        host=config.host,
        port=config.port,
        ws_ping_interval=20,
        ws_ping_timeout=20,
        log_level="info",
    )