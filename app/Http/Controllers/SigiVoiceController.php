<?php

namespace App\Http\Controllers;

use App\Services\Sigi\SigiVoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SigiVoiceController extends Controller
{
    /**
     * Generar audio de SIGI a partir de un texto.
     *
     * La API key del motor TTS nunca llega al navegador.
     * Laravel se comunica con el servicio TTS y devuelve únicamente
     * el audio generado.
     */
    public function speak(
        Request $request,
        SigiVoiceService $voiceService
    ): Response|JsonResponse {
        $validated = $request->validate([
            'text' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        try {
            $audio = $voiceService->sintetizar(
                $validated['text']
            );

            return response(
                $audio,
                200,
                [
                    'Content-Type' =>
                        $this->contentType(
                            (string) config(
                                'services.sigi_tts.response_format',
                                'mp3'
                            )
                        ),

                    'Content-Length' =>
                        strlen($audio),

                    'Cache-Control' =>
                        'no-store, private',

                    'X-Content-Type-Options' =>
                        'nosniff',
                ]
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo generar la voz de SIGI.',
            ], 503);
        }
    }

    /**
     * Convierte el formato configurado en un MIME type.
     */
    private function contentType(
        string $format
    ): string {
        return match (strtolower($format)) {
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'opus' => 'audio/ogg',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'webm' => 'audio/webm',
            default => 'audio/mpeg',
        };
    }
}
