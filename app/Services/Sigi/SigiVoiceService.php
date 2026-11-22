<?php

namespace App\Services\Sigi;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SigiVoiceService
{
    /**
     * Genera audio a partir de un texto usando el motor TTS configurado.
     *
     * SIGEFIV no depende directamente de Kokoro, Piper u otro motor.
     * El servicio utilizado se configura mediante las variables SIGI_TTS_*.
     */
    public function sintetizar(string $texto): string
    {
        $texto = trim($texto);

        if ($texto === '') {
            throw new RuntimeException(
                'SIGI no puede generar audio a partir de un texto vacío.'
            );
        }

        $url = trim(
            (string) config('services.sigi_tts.url')
        );

        if ($url === '') {
            throw new RuntimeException(
                'No está configurada la URL del servicio TTS de SIGI.'
            );
        }

        $payload = [
            'model' => config(
                'services.sigi_tts.model',
                'tts-1'
            ),
            'input' => $texto,
            'voice' => config(
                'services.sigi_tts.voice',
                'ef_dora'
            ),
            'response_format' => config(
                'services.sigi_tts.response_format',
                'mp3'
            ),
        ];

        $request = Http::timeout(
            (int) config(
                'services.sigi_tts.timeout',
                60
            )
        )
            ->accept('audio/*')
            ->asJson();

        $apiKey = trim(
            (string) config('services.sigi_tts.api_key')
        );

        if ($apiKey !== '') {
            $request = $request->withToken($apiKey);
        }

        /** @var Response $response */
        $response = $request->post(
            $url,
            $payload
        );

        if (!$response->successful()) {
            throw new RuntimeException(
                'El servicio TTS de SIGI devolvió HTTP ' .
                $response->status() .
                ': ' .
                $response->body()
            );
        }

        $audio = $response->body();

        if ($audio === '') {
            throw new RuntimeException(
                'El servicio TTS de SIGI respondió sin contenido de audio.'
            );
        }

        return $audio;
    }

    /**
     * Indica si el servicio TTS está configurado.
     */
    public function configurado(): bool
    {
        return trim(
            (string) config('services.sigi_tts.url')
        ) !== '';
    }

    /**
     * Devuelve únicamente información segura para el frontend.
     *
     * La API key nunca se expone.
     */
    public function informacion(): array
    {
        return [
            'configurado' => $this->configurado(),
            'modelo' => config(
                'services.sigi_tts.model',
                'tts-1'
            ),
            'voz' => config(
                'services.sigi_tts.voice',
                'ef_dora'
            ),
            'formato' => config(
                'services.sigi_tts.response_format',
                'mp3'
            ),
        ];
    }
}
