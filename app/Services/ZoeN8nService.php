<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ZoeN8nService
{
    /**
     * URL del webhook de ZOE en n8n.
     */
    protected string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = (string) config(
            'services.n8n.zoe_webhook_url',
            'http://host.docker.internal:5678/webhook/zoe-chat-vecinal'
        );
    }

    /**
     * Envía una consulta a ZOE mediante n8n.
     */
    public function consultar(
        string $mensaje,
        int $usuarioId,
        ?string $nombreUsuario = null
    ): ?string {
        try {

            if (trim($this->webhookUrl) === '') {
                Log::error('ZOE: webhook de n8n no configurado.');

                return null;
            }

            $response = Http::timeout(120)
                ->connectTimeout(10)
                ->acceptJson()
                ->asJson()
                ->post($this->webhookUrl, [
                    'mensaje' => $mensaje,
                    'usuario_id' => $usuarioId,
                    'nombre_usuario' => $nombreUsuario,
                ]);

            Log::info('ZOE respuesta de n8n', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::warning('n8n devolvió un error para ZOE.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            /*
             * Intentamos obtener JSON.
             */
            $datos = $response->json();

            /*
             * Caso normal:
             *
             * {
             *     "respuesta": "..."
             * }
             */
            if (is_array($datos)) {

                $texto =
                    $datos['respuesta']
                    ?? $datos['mensaje']
                    ?? $datos['text']
                    ?? $datos['output']
                    ?? null;

                if (is_string($texto) && trim($texto) !== '') {
                    return trim($texto);
                }
            }

            /*
             * Algunos flujos pueden devolver JSON
             * como texto dentro del body.
             */
            $body = trim($response->body());

            if ($body !== '') {

                $jsonBody = json_decode($body, true);

                if (is_array($jsonBody)) {

                    $texto =
                        $jsonBody['respuesta']
                        ?? $jsonBody['mensaje']
                        ?? $jsonBody['text']
                        ?? $jsonBody['output']
                        ?? null;

                    if (
                        is_string($texto) &&
                        trim($texto) !== ''
                    ) {
                        return trim($texto);
                    }
                }

                /*
                 * Si n8n devolvió texto plano,
                 * también lo aceptamos.
                 */
                if (
                    !str_starts_with($body, '<') &&
                    !str_starts_with($body, '{')
                ) {
                    return $body;
                }
            }

            Log::warning('ZOE: n8n respondió pero no se encontró texto.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $datos,
            ]);

            return null;

        } catch (Throwable $e) {

            Log::error('Error conectando ZOE con n8n.', [
                'url' => $this->webhookUrl,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            return null;
        }
    }
}