<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ZoeN8nService;
use App\Services\FcmService;


class ZoeController extends Controller
{
    public function consultar(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string|max:1000',
        ]);

        try {
            $usuario = $request->user();

            $response = Http::timeout(120)
                ->post(config('services.n8n.zoe_webhook'), [
                    'mensaje' => $request->mensaje,

                    // Nombre del usuario autenticado
                    'nombre_usuario' => $usuario?->name ?? 'vecino',
                ]);

            Log::info('ZOE respuesta n8n', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener respuesta de ZOE.',
                    'error' => $response->body(),
                ], 502);
            }

          $respuesta =
    $response->json('respuesta')
    ?? $response->json('mensaje')
    ?? $response->json('text');

$estadistica = $response->json('estadistica');

return response()->json([
    'success' => true,
    'respuesta' => $respuesta,
    'estadistica' => $estadistica,
]);

        } catch (\Throwable $e) {
            Log::error('Error al comunicarse con ZOE', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al comunicarse con ZOE.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
     /**
     * Genera un mensaje inteligente para la mascota ZOE
     * y lo distribuye mediante FCM.
     */
    public function mensajeMascota(
        Request $request,
        ZoeN8nService $zoeN8nService,
        FcmService $fcmService
    ) {
        $request->validate([
            'evento' => 'required|string|max:100',
            'contexto' => 'nullable|array',
        ]);

        try {

            $usuario = $request->user();

            $resultado = $zoeN8nService->mensajeMascota(
                evento: $request->evento,
                usuarioId: $usuario?->id ?? 0,
                nombreUsuario: $usuario?->name,
                contexto: $request->contexto ?? []
            );

            if ($resultado === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el mensaje de ZOE.',
                ], 502);
            }

            $enviados = $fcmService->enviarMensajeZoe(
                titulo: $resultado['titulo'],
                mensaje: $resultado['mensaje'],
                icono: $resultado['icono'],
                estado: $resultado['estado']
            );

            return response()->json([
                'success' => true,
                'titulo' => $resultado['titulo'],
                'mensaje' => $resultado['mensaje'],
                'icono' => $resultado['icono'],
                'estado' => $resultado['estado'],
                'dispositivos_enviados' => $enviados,
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'Error generando mensaje de ZOE MASCOTA',
                [
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el mensaje de ZOE.',
            ], 500);
        }
    }
}