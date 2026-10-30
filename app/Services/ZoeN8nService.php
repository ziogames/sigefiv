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
     * Retorna un array con el texto base y la información complementaria (emprendimientos, estadísticas), o null en error.
     */
    public function consultar(
        string $mensaje,
        int $usuarioId,
        ?string $nombreUsuario = null
    ): ?array {
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

            $datos = $response->json();

            // Si no se parseó automáticamente como JSON, probamos desde el body crudo
            if (!is_array($datos)) {
                $body = trim($response->body());
                if ($body !== '') {
                    $jsonBody = json_decode($body, true);
                    if (is_array($jsonBody)) {
                        $datos = $jsonBody;
                    } else {
                        // En caso de ser solo una cadena de texto plano
                        return [
                            'texto' => $body,
                            'extra' => null,
                            'raw' => $body,
                        ];
                    }
                }
            }

            if (is_array($datos)) {
                $texto = $datos['respuesta']
                    ?? $datos['mensaje']
                    ?? $datos['text']
                    ?? $datos['output']
                    ?? '';

                // Extraemos el listado de emprendimientos o estadísticas estructuradas
                $extra = $datos['estadistica']
                    ?? $datos['emprendimientos']
                    ?? $datos['data']
                    ?? null;

                return [
                    'texto' => trim((string) $texto),
                    'extra' => $extra,
                    'raw' => $datos,
                ];
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

    /**
     * Envía un evento a ZOE para generar un mensaje de la mascota.
     */
    public function mensajeMascota(
        string $evento,
        int $usuarioId,
        ?string $nombreUsuario = null,
        array $contexto = []
    ): ?array {
        try {
            $webhookMascota = (string) config(
                'services.n8n.zoe_webhook',
                'http://host.docker.internal:5678/webhook/zoe'
            );

            if (trim($webhookMascota) === '') {
                Log::error('ZOE MASCOTA: webhook de n8n no configurado.');

                return null;
            }

            $response = Http::timeout(30)
                ->connectTimeout(10)
                ->acceptJson()
                ->asJson()
                ->post($webhookMascota, [
                    'tipo' => 'mascota',
                    'evento' => $evento,
                    'usuario_id' => $usuarioId,
                    'nombre_usuario' => $nombreUsuario,
                    'contexto' => $contexto,
                ]);

            Log::info('ZOE MASCOTA respuesta de n8n', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::warning('n8n devolvió un error para ZOE MASCOTA.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $datos = $response->json();

            if (!is_array($datos)) {
                return null;
            }

            $mensaje = $datos['mensaje']
                ?? $datos['respuesta']
                ?? $datos['text']
                ?? $datos['output']
                ?? null;

            if (!is_string($mensaje) || trim($mensaje) === '') {
                return null;
            }

            return [
                'titulo' => is_string($datos['titulo'] ?? null)
                    ? $datos['titulo']
                    : 'ZOE',

                'mensaje' => trim($mensaje),

                'icono' => is_string($datos['icono'] ?? null)
                    ? $datos['icono']
                    : '🤖',

                'estado' => is_string($datos['estado'] ?? null)
                    ? $datos['estado']
                    : 'FELIZ',
            ];

        } catch (Throwable $e) {
            Log::error('Error conectando ZOE MASCOTA con n8n.', [
                'url' => $this->webhookUrl,
                'evento' => $evento,
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            return null;
        }
    }
}