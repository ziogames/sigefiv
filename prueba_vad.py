import sounddevice as sd
import numpy as np
import time

SAMPLE_RATE = 16000
DURACION = 3

print("🎤 Habla durante 3 segundos...")

audio = sd.rec(
    int(DURACION * SAMPLE_RATE),
    samplerate=SAMPLE_RATE,
    channels=1,
    dtype="float32"
)

sd.wait()

audio = np.squeeze(audio)

nivel = np.max(np.abs(audio))
rms = np.sqrt(np.mean(audio ** 2))

print()
print("📊 Nivel máximo:", nivel)
print("📊 RMS:", rms)

if nivel > 0.01:
    print("✅ El micrófono está capturando audio.")
else:
    print("❌ No se está capturando audio.")