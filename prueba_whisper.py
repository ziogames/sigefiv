from faster_whisper import WhisperModel
import sounddevice as sd
import numpy as np

DURACION = 5
SAMPLE_RATE = 16000

print("Cargando Whisper...")

model = WhisperModel(
    "small",
    device="cuda",
    compute_type="float16"
)

print("Habla durante 5 segundos...")

audio = sd.rec(
    int(DURACION * SAMPLE_RATE),
    samplerate=SAMPLE_RATE,
    channels=1,
    dtype="float32"
)

sd.wait()

audio = np.squeeze(audio)

segments, info = model.transcribe(
    audio,
    language="es",
    vad_filter=True
)

texto = " ".join(segment.text.strip() for segment in segments)

print("\n🎤 Dijiste:")
print(texto)