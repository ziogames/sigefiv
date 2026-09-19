<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZoeN8nService
{
    /**
     * URL del Webhook de n8n.
     */
    protected string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config(
            'services.n8n.zoe_webhook_url',
            'http://172.17.0.4:5678/webhook/zoe-chat-vecinal'
        );
    }

    /**
     * Envía un mensaje a n8n y obtiene la respuesta de ZOE.
     */
   public function consultar(
    string $mensaje,
    int $usuarioId,
    ?string $nombreUsuario = null
): ?string {
        try {
            $respuesta = Http::timeout(30)
                ->connectTimeout(5)
                ->acceptJson()
               ->post($this->webhookUrl, [
                        'mensaje' => $mensaje,
                        'usuario_id' => $usuarioId,
                        'nombre_usuario' => $nombreUsuario,
]);

            if (!$respuesta->successful()) {
                Log::warning('n8n devolvió un error.', [
                    'status' => $respuesta->status(),
                    'respuesta' => $respuesta->body(),
                ]);

                return null;
            }

            $datos = $respuesta->json();

            $texto = $datos['respuesta'] ?? null;

            if (!is_string($texto) || trim($texto) === '') {
                Log::warning('n8n no devolvió una respuesta válida.', [
                    'datos' => $datos,
                ]);

                return null;
            }

            return trim($texto);

        } catch (Throwable $e) {
            Log::error('Error conectando con n8n.', [
                'mensaje' => $e->getMessage(),
            ]);

            return null;
        }
    }
}