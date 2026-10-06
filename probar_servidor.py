import requests
import sounddevice as sd
import wave
import numpy as np
import pygame

SAMPLE_RATE = 16000
DURACION = 5
ARCHIVO = "prueba.wav"
RESPUESTA = "respuesta.wav"

print("🎤 Habla durante 5 segundos...")

audio = sd.rec(
    int(DURACION * SAMPLE_RATE),
    samplerate=SAMPLE_RATE,
    channels=1,
    dtype="int16"
)

sd.wait()

with wave.open(ARCHIVO, "wb") as wf:
    wf.setnchannels(1)
    wf.setsampwidth(2)
    wf.setframerate(SAMPLE_RATE)
    wf.writeframes(audio.tobytes())

print("📤 Enviando audio a ZOE...")

with open(ARCHIVO, "rb") as f:
    respuesta = requests.post(
        "http://127.0.0.1:8001/zoe/voz",
        files={"audio": ("prueba.wav", f, "audio/wav")}
    )

if respuesta.status_code != 200:
    print("❌ Error:", respuesta.status_code)
    print(respuesta.text)
    raise SystemExit

with open(RESPUESTA, "wb") as f:
    f.write(respuesta.content)

print("🔊 ZOE respondió. Reproduciendo...")

pygame.mixer.init()
pygame.mixer.music.load(RESPUESTA)
pygame.mixer.music.play()

while pygame.mixer.music.get_busy():
    pass

pygame.mixer.quit()

print("✅ Prueba terminada.")