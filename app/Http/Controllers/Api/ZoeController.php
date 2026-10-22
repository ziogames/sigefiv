<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ZoeN8nService;
use App\Services\FcmService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\ZoeIncidencia;


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
    ->post(config('services.n8n.zoe_webhook_url'), [
        'mensaje' => $request->mensaje,

        // Usuario autenticado
        'usuario_id' => $usuario?->id,

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

public function historial(Request $request)
    {
        try {
            $usuario = $request->user();

            if (!$usuario) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado.',
                ], 401);
            }

            // Agrupamos directamente por conversacion_id de la tabla zoe_mensajes
            $conversaciones = \DB::table('zoe_mensajes')
                ->select('conversacion_id', \DB::raw('MAX(created_at) as ultima_fecha'))
                ->groupBy('conversacion_id')
                ->orderBy('ultima_fecha', 'desc')
                ->get()
                ->map(function ($item) {
                    
                    // 1. Buscamos el primer mensaje del usuario que NO sea un evento automático
                    $primerMensaje = \DB::table('zoe_mensajes')
                        ->where('conversacion_id', $item->conversacion_id)
                        ->where('rol', 'user')
                        ->where('mensaje', 'NOT LIKE', 'evento_%')
                        ->orderBy('created_at', 'asc')
                        ->value('mensaje');

                    // 2. Si no hay mensaje de usuario, buscamos el primer mensaje del asistente
                    if (!$primerMensaje) {
                        $primerMensaje = \DB::table('zoe_mensajes')
                            ->where('conversacion_id', $item->conversacion_id)
                            ->where('rol', 'assistant')
                            ->orderBy('created_at', 'asc')
                            ->value('mensaje');
                    }

                    $fecha = \Carbon\Carbon::parse($item->ultima_fecha);
                    
                    $grupo = 'ANTERIORES';
                    if ($fecha->isToday()) {
                        $grupo = 'HOY';
                    } elseif ($fecha->isYesterday()) {
                        $grupo = 'AYER';
                    }

                    // 3. Título limpio para la tarjeta
                    $tituloLimpio = $primerMensaje;
                    if (!$tituloLimpio || str_starts_with($tituloLimpio, 'evento_')) {
                        $tituloLimpio = 'Conversación de ZOE';
                    }

                    return [
                        'id' => (string) $item->conversacion_id,
                        'titulo' => Str::limit($tituloLimpio, 40),
                        'hora' => $fecha->format('h:i a'),
                        'grupo' => $grupo
                    ];
                });

            return response()->json([
                'success' => true,
                'historial' => $conversaciones,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error al obtener el historial de ZOE', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function verConversacion(Request $request, $conversacionId)
    {
        try {
            $mensajesDb = \DB::table('zoe_mensajes')
                ->where('conversacion_id', $conversacionId)
                ->orderBy('created_at', 'asc')
                ->get();

            $mensajesFormateados = $mensajesDb->map(function ($msg) {
                $fecha = \Carbon\Carbon::parse($msg->created_at);
                return [
                    'texto' => $msg->mensaje,
                    'esUsuario' => $msg->rol === 'user',
                    'hora' => $fecha->format('h:i a')
                ];
            });

            return response()->json([
                'success' => true,
                'mensajes' => $mensajesFormateados
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la conversación.',
            ], 500);
        }
    }
public function enviarMensajeConArchivo(Request $request)
    {
        $request->validate([
            'mensaje' => 'nullable|string',
            'archivo' => 'nullable|file|max:15360',
        ]);

        $rutaRelativa = null;

        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            
            // Guardamos directamente en storage/app/public/chat_zoe
            $path = $archivo->store('chat_zoe', 'public');
            
            // Ruta relativa para la BD (ej. storage/chat_zoe/xxx.jpg o chat_zoe/xxx.jpg)
            $rutaRelativa = $path; 
        }

        $incidencia = ZoeIncidencia::create([
            'user_id' => $request->user()?->id,
            'mensaje' => $request->input('mensaje', ''),
            'imagen_path' => $rutaRelativa,
            'estado' => 'pendiente'
        ]);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'message' => 'Incidencia registrada correctamente en SIGEFIV.',
            'respuesta' => 'Incidencia registrada correctamente en SIGEFIV.',
            'incidencia_id' => $incidencia->id,
            'archivo_url' => $rutaRelativa ? asset('storage/' . $rutaRelativa) : null
        ], 200);
    }
}
