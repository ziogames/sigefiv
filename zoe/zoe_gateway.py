import os
import uuid
import tempfile
import asyncio
import json
import re
from pathlib import Path

# ============================================================
# ZOE SERVER DEFINITIVO - BASE
# ============================================================
# Esta primera versión conserva el motor probado:
# Whisper + Qwen + Supertonic F4.
#
# Arquitectura preparada para:
# Android <-> WebSocket <-> ZOE
# y posteriormente:
# ZOE <-> n8n <-> SIGEFIV
#
# No modifica SIGEFIV ni n8n todavía.

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

from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from fastapi.responses import JSONResponse, FileResponse

from faster_whisper import WhisperModel
from supertonic import TTS
import ollama

# ============================================================
# CONFIGURACIÓN
# ============================================================

WHISPER_MODEL = "small"
OLLAMA_MODEL = "qwen2.5:3b"
VOICE = "F4"
LANGUAGE = "es"
SAMPLE_RATE = 16000

HOST = "0.0.0.0"
PORT = 8000

# ============================================================
# FASTAPI
# ============================================================

app = FastAPI(
    title="ZOE Voice Gateway",
    version="1.0.0",
)

# ============================================================
# SESIONES
# ============================================================

sesiones = {}


def nueva_sesion():
    session_id = uuid.uuid4().hex

    sesiones[session_id] = {
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
- No confundas "tú" y "yo".

PERSONALIDAD:
- Eres cálida, natural, amable y conversacional.
- Habla como una asistente humana, no como un robot.
- Responde de forma clara, sencilla y breve.
- Usa puntuación y expresiones naturales en español, por ejemplo: "¡Hola!".
- Evita respuestas rígidas o repetitivas.

REGLA ABSOLUTA:
- NO INVENTAR INFORMACIÓN.
- Si no conoces un dato, dilo claramente.
- No atribuyas al usuario acciones, datos o identidades que no haya proporcionado.
- Cuando necesites datos de SIGEFIV, en el futuro los consultarás mediante las herramientas/API correspondientes.

EJEMPLO:
Usuario: "¿Quién eres?"
ZOE: "Soy ZOE, la asistente virtual de SIGEFIV. ¿En qué puedo ayudarte?"
""",
            }
        ]
    }

    return session_id


# ============================================================
# CARGAR MODELOS
# ============================================================

print()
print("=" * 60)
print("🤖 ZOE VOICE GATEWAY")
print("=" * 60)

print("🧠 Cargando Whisper...")
whisper = WhisperModel(
    WHISPER_MODEL,
    device="cuda",
    compute_type="float16",
)

print("🔊 Cargando Supertonic...")
tts = TTS(auto_download=True)
voice_style = tts.get_voice_style(VOICE)

print("🤖 ZOE lista.")

# ============================================================
# WHISPER
# ============================================================

def transcribir(audio):
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
        print(f"❌ Error Whisper: {e}")
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

    if any(frase in texto_normalizado for frase in frases_falsas):
        return ""

    return texto


# ============================================================
# QWEN
# ============================================================

def preguntar_zoe(session_id, texto):
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

        contenido = (
            respuesta["message"]["content"]
            .strip()
        )

        mensajes.append({
            "role": "assistant",
            "content": contenido,
        })

        return contenido

    except Exception as e:
        print(f"❌ Error Ollama: {e}")
        return "Lo siento, tuve un problema al procesar tu solicitud."


# ============================================================
# PREPARAR TEXTO PARA LA VOZ DE ZOE
# ============================================================

def preparar_texto_voz(texto):
    """
    Prepara exclusivamente la copia que recibirá Supertonic.

    No modifica el texto mostrado en Android.

    Objetivos:
    - mejorar la lectura de dinero y porcentajes;
    - introducir pausas conversacionales;
    - evitar símbolos que el TTS pueda pronunciar de forma extraña;
    - mantener frases cortas y naturales.
    """

    if not texto:
        return ""

    texto = str(texto).strip()

    # ------------------------------------------------------------
    # NORMALIZACIÓN BÁSICA
    # ------------------------------------------------------------

    texto = " ".join(texto.split())

    # ------------------------------------------------------------
    # DINERO
    # ------------------------------------------------------------

    # S/ 25.50 -> "25.50 soles"
    texto = re.sub(
        r"S/\.?\s*([0-9]+(?:[.,][0-9]{1,2})?)",
        r"\1 soles",
        texto,
        flags=re.IGNORECASE,
    )

    texto = re.sub(
        r"S/\.\s*([0-9]+(?:[.,][0-9]{1,2})?)",
        r"\1 soles",
        texto,
        flags=re.IGNORECASE,
    )

    # ------------------------------------------------------------
    # SÍMBOLOS
    # ------------------------------------------------------------

    texto = texto.replace("%", " por ciento")
    texto = texto.replace("&", " y ")

    # ------------------------------------------------------------
    # PAUSAS CONVERSACIONALES
    # ------------------------------------------------------------

    # Dos puntos: una pequeña pausa antes de continuar.
    texto = re.sub(r"\s*:\s*", ": ", texto)

    # Punto y coma: pausa más marcada.
    texto = re.sub(r"\s*;\s*", "; ", texto)

    # Después de saludos, una pequeña separación.
    texto = re.sub(
        r"^(Hola|Buenos días|Buenas tardes|Buenas noches)([,.]?)(\s+)",
        r"\1\2... ",
        texto,
        flags=re.IGNORECASE,
    )

    # Antes de preguntas independientes, favorecer una pausa.
    texto = re.sub(r"\s+¿", "... ¿", texto)

    # ------------------------------------------------------------
    # FRASES MUY LARGAS
    # ------------------------------------------------------------

    # Evitar que una oración excesivamente larga suene como una
    # lectura continua. Solo agregamos pausas suaves.
    partes = re.split(r"(?<=[.!?])\s+", texto)

    resultado = []

    for parte in partes:
        parte = parte.strip()

        if not parte:
            continue

        # Frases largas: separar naturalmente en comas.
        palabras = parte.split()

        if len(palabras) > 24 and "," not in parte:
            mitad = len(palabras) // 2
            parte = (
                " ".join(palabras[:mitad])
                + "... "
                + " ".join(palabras[mitad:])
            )

        resultado.append(parte)

    texto = " ".join(resultado)

    # Limpieza final.
    texto = re.sub(r"\s+", " ", texto).strip()

    return texto


# ============================================================
# SUPERTONIC
# ============================================================

def sintetizar(texto):
    texto_voz = preparar_texto_voz(texto)

    print(f"🔊 Texto para ZOE: {texto_voz}")

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
# HEALTH
# ============================================================

@app.get("/")
async def health():
    return {
        "status": "ok",
        "service": "ZOE Voice Gateway",
        "version": "1.0.0",
        "whisper": WHISPER_MODEL,
        "ollama": OLLAMA_MODEL,
        "voice": VOICE,
        "language": LANGUAGE,
        "transport": "websocket",
    }


# ============================================================
# CREAR SESIÓN
# ============================================================

@app.post("/zoe/session")
async def crear_sesion():
    session_id = nueva_sesion()

    return {
        "status": "ok",
        "session_id": session_id,
    }


# ============================================================
# TEXTO - PRUEBA DEL CORE
# ============================================================

@app.post("/zoe/text")
async def zoe_text(data: dict):
    texto = str(data.get("text", "")).strip()
    session_id = data.get("session_id")

    if not texto:
        return JSONResponse(
            {"status": "error", "message": "Texto vacío"},
            status_code=400,
        )

    if not session_id or session_id not in sesiones:
        session_id = nueva_sesion()

    respuesta = preguntar_zoe(
        session_id,
        texto,
    )

    return {
        "status": "ok",
        "session_id": session_id,
        "text": respuesta,
    }


# ============================================================
# WEBSOCKET - BASE DEFINITIVA
# ============================================================

@app.websocket("/ws/zoe")
async def websocket_zoe(websocket: WebSocket):

    await websocket.accept()

    session_id = nueva_sesion()

    # Audio PCM recibido desde Android:
    # 16 kHz / mono / PCM signed 16-bit little-endian.
    audio_buffer = bytearray()

    # Evento lógico para BARGE-IN.
    # Android puede avisar que el usuario empezó a hablar.
    interrupcion = False

    print(
        f"📱 Cliente conectado: {session_id}"
    )

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

            # =================================================
            # DESCONEXIÓN
            # =================================================

            if mensaje.get("type") == "websocket.disconnect":
                break

            # =================================================
            # AUDIO BINARIO
            # =================================================

            if mensaje.get("bytes") is not None:

                datos_audio = mensaje["bytes"]

                if datos_audio:
                    audio_buffer.extend(datos_audio)

                continue

            # =================================================
            # MENSAJE JSON
            # =================================================

            if mensaje.get("text") is None:
                continue

            try:
                datos = json.loads(mensaje["text"])
            except Exception:
                await websocket.send_json({
                    "type": "error",
                    "message": "Mensaje JSON inválido.",
                })
                continue

            tipo = datos.get("type")

            # -------------------------------------------------
            # PING
            # -------------------------------------------------

            if tipo == "ping":

                await websocket.send_json({
                    "type": "pong",
                })

            # -------------------------------------------------
            # TEXTO
            # -------------------------------------------------

            elif tipo == "text":

                texto = str(
                    datos.get("text", "")
                ).strip()

                if not texto:
                    continue

                respuesta = await asyncio.to_thread(
                    preguntar_zoe,
                    session_id,
                    texto,
                )

                await websocket.send_json({
                    "type": "response",
                    "text": respuesta,
                })

            # -------------------------------------------------
            # INICIO DE AUDIO
            # -------------------------------------------------

            elif tipo == "start_audio":

                audio_buffer.clear()
                interrupcion = False

                await websocket.send_json({
                    "type": "audio_started",
                })

            # -------------------------------------------------
            # FIN DE AUDIO
            # -------------------------------------------------

            elif tipo == "end_audio":

                if not audio_buffer:

                    await websocket.send_json({
                        "type": "error",
                        "message": "No se recibió audio.",
                    })
                    continue

                # Copia para liberar el buffer inmediatamente.
                datos_pcm = bytes(audio_buffer)
                audio_buffer.clear()

                # PCM S16LE -> float32 para Whisper.
                audio_int16 = np.frombuffer(
                    datos_pcm,
                    dtype="<i2",
                )

                audio_float = (
                    audio_int16.astype(np.float32)
                    / 32768.0
                )

                await websocket.send_json({
                    "type": "transcribing",
                })

                texto = await asyncio.to_thread(
                    transcribir,
                    audio_float,
                )

                if not texto:

                    await websocket.send_json({
                        "type": "no_text",
                    })
                    continue

                await websocket.send_json({
                    "type": "transcription",
                    "text": texto,
                })

                # -------------------------------------------------
                # IMPORTANTE
                # -------------------------------------------------
                # La consulta transcrita NO pasa por Qwen aquí.
                # Android la enviará al backend real de SIGEFIV.
                # Cuando Android reciba la respuesta financiera,
                # enviará un mensaje "response_text" para que
                # este Gateway solamente genere la voz.
                # -------------------------------------------------

                await websocket.send_json({
                    "type": "awaiting_backend",
                })

            # -------------------------------------------------
            # RESPUESTA DEL BACKEND SIGEFIV
            # -------------------------------------------------

            elif tipo == "response_text":

                respuesta = str(
                    datos.get("text", "")
                ).strip()

                if not respuesta:
                    continue

                await websocket.send_json({
                    "type": "synthesizing",
                    "text": respuesta,
                })

                ruta_audio = None

                try:

                    ruta_audio = await asyncio.to_thread(
                        sintetizar,
                        respuesta,
                    )

                    if (
                        not ruta_audio.exists()
                        or ruta_audio.stat().st_size <= 44
                    ):
                        raise RuntimeError(
                            "No se generó un WAV válido."
                        )

                    audio_wav = await asyncio.to_thread(
                        ruta_audio.read_bytes
                    )

                    await websocket.send_json({
                        "type": "audio_start",
                        "format": "wav",
                        "size": len(audio_wav),
                    })

                    await websocket.send_bytes(
                        audio_wav
                    )

                    await websocket.send_json({
                        "type": "audio_end",
                    })

                except Exception as e:

                    print(
                        f"❌ Error generando/enviando audio: {e}"
                    )

                    await websocket.send_json({
                        "type": "error",
                        "message": "No se pudo generar el audio de ZOE.",
                    })

                finally:

                    if ruta_audio:

                        try:
                            ruta_audio.unlink(
                                missing_ok=True
                            )
                        except Exception:
                            pass

            # -------------------------------------------------
            # BARGE-IN
            # -------------------------------------------------

            elif tipo == "interrupt":

                interrupcion = True

                # El audio que pudiera estar acumulado
                # corresponde a la frase anterior.
                audio_buffer.clear()

                await websocket.send_json({
                    "type": "interrupted",
                })

            # -------------------------------------------------
            # CERRAR
            # -------------------------------------------------

            elif tipo == "close":

                await websocket.close()
                break

            # -------------------------------------------------
            # TIPO DESCONOCIDO
            # -------------------------------------------------

            else:

                await websocket.send_json({
                    "type": "error",
                    "message": f"Tipo no soportado: {tipo}",
                })

    except WebSocketDisconnect:

        print(
            f"📴 Cliente desconectado: {session_id}"
        )

    except Exception as e:

        print(
            f"❌ WebSocket error: {e}"
        )


# ============================================================
# INICIO
# ============================================================

if __name__ == "__main__":

    import uvicorn

    uvicorn.run(
        app,
        host=HOST,
        port=PORT,
    )
