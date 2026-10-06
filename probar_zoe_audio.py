import asyncio
import json
import sounddevice as sd
import websockets
import pygame

URL = "ws://127.0.0.1:8000/ws/zoe"
RATE = 16000
CHANNELS = 1
BLOCK = 1024

async def main():
    print("=" * 60)
    print("🎤 PRUEBA AUDIO REAL ZOE")
    print("=" * 60)

    async with websockets.connect(URL, max_size=20 * 1024 * 1024) as ws:
        session = json.loads(await ws.recv())
        print(f"✅ Conectado. Sesión: {session['session_id']}")
        print("\n🎤 Habla después de pulsar ENTER.")
        input()

        print("🔴 GRABANDO... pulsa ENTER para terminar.")
        await ws.send(json.dumps({
            "type": "start_audio",
            "sample_rate": RATE,
            "channels": CHANNELS,
            "format": "pcm_s16le"
        }))

        chunks = []
        loop = asyncio.get_running_loop()
        stop = asyncio.Event()

        def callback(indata, frames, time, status):
            if status:
                print(status)
            chunks.append(indata.copy().tobytes())

        with sd.InputStream(
            samplerate=RATE,
            channels=CHANNELS,
            dtype="int16",
            blocksize=BLOCK,
            callback=callback
        ):
            await loop.run_in_executor(None, input)

        print("⏹️ Enviando audio...")
        for chunk in chunks:
            await ws.send(chunk)

        await ws.send(json.dumps({"type": "end_audio"}))

        audio_data = None

        while True:
            msg = await ws.recv()

            if isinstance(msg, bytes):
                audio_data = msg
                print(f"🔊 Audio recibido: {len(msg):,} bytes")
                continue

            data = json.loads(msg)
            tipo = data.get("type")

            if tipo == "transcribing":
                print("🧠 Transcribiendo...")
            elif tipo == "transcription":
                print(f"👤 Tú: {data.get('text', '')}")
            elif tipo == "thinking":
                print("🤖 ZOE está pensando...")
            elif tipo == "synthesizing":
                print(f"🔊 Generando voz: {data.get('text', '')}")
            elif tipo == "audio_start":
                print("🔊 Reproduciendo respuesta...")
            elif tipo == "audio_end":
                break
            elif tipo == "error":
                print("❌", data)
                return

        if audio_data:
            path = "respuesta_zoe.wav"
            with open(path, "wb") as f:
                f.write(audio_data)

            pygame.mixer.init()
            pygame.mixer.music.load(path)
            pygame.mixer.music.play()

            while pygame.mixer.music.get_busy():
                await asyncio.sleep(0.05)

            pygame.mixer.quit()

        print("\n✅ PRUEBA DE AUDIO COMPLETADA")

if __name__ == "__main__":
    asyncio.run(main())
