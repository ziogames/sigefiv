<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\ZoeN8nService;
use App\Services\FcmService;
use Illuminate\Support\Str;
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

            $seudonimo = trim((string) ($usuario?->seudonimo ?? ''));
            $nombreReal = trim((string) ($usuario?->name ?? ''));
            $nombreZoe = $seudonimo !== ''
                ? $seudonimo
                : (explode(' ', $nombreReal)[0] ?: 'vecino');

            $response = Http::timeout(120)
                ->post(config('services.n8n.zoe_webhook_url'), [
                    'mensaje' => $request->mensaje,
                    'usuario_id' => $usuario?->id,
                    'nombre_usuario' => $nombreZoe,
                    'seudonimo' => $usuario?->seudonimo,
                ]);

            Log::info('ZOE respuesta n8n', [
                'status' => $response->status(),
                'usuario_id' => $usuario?->id,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener respuesta de ZOE.',
                ], 502);
            }

            $respuesta = $response->json('respuesta')
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
            ], 500);
        }
    }

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

            $seudonimo = trim((string) ($usuario?->seudonimo ?? ''));
            $nombreReal = trim((string) ($usuario?->name ?? ''));

            $nombreZoe = $seudonimo !== ''
                ? $seudonimo
                : (explode(' ', $nombreReal)[0] ?: 'vecino');

            $resultado = $zoeN8nService->mensajeMascota(
                evento: $request->evento,
                usuarioId: $usuario?->id ?? 0,
                nombreUsuario: $nombreZoe,
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
            Log::error('Error generando mensaje de ZOE MASCOTA', [
                'error' => $e->getMessage(),
            ]);

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

            $conversaciones = DB::table('zoe_mensajes')
                ->where('user_id', $usuario->id)
                ->select('conversacion_id', DB::raw('MAX(created_at) as ultima_fecha'))
                ->groupBy('conversacion_id')
                ->orderBy('ultima_fecha', 'desc')
                ->get()
                ->map(function ($item) use ($usuario) {

                    $primerMensaje = DB::table('zoe_mensajes')
                        ->where('user_id', $usuario->id)
                        ->where('conversacion_id', $item->conversacion_id)
                        ->where('rol', 'user')
                        ->where('mensaje', 'NOT LIKE', 'evento_%')
                        ->orderBy('created_at', 'asc')
                        ->value('mensaje');

                    if (!$primerMensaje) {
                        $primerMensaje = DB::table('zoe_mensajes')
                            ->where('user_id', $usuario->id)
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
            ], 500);
        }
    }

    public function verConversacion(Request $request, $conversacionId)
    {
        try {
            $usuarioId = $request->user()?->id;

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado.',
                ], 401);
            }

            $mensajesDb = DB::table('zoe_mensajes')
                ->where('conversacion_id', $conversacionId)
                ->where('user_id', $usuarioId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($mensajesDb->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversación no encontrada o sin acceso.',
                ], 404);
            }

            $feedbackUsuario = DB::table('zoe_feedback')
                ->where('usuario_id', $usuarioId)
                ->whereIn('voto', ['positivo', 'negativo'])
                ->pluck('voto', 'mensaje_hash');

            $mensajesFormateados = $mensajesDb->map(function ($msg) use ($feedbackUsuario) {
                $fecha = \Carbon\Carbon::parse($msg->created_at);
                $esUsuario = $msg->rol === 'user';
                $calificacion = null;

                if (!$esUsuario) {
                    $textoLimpio = preg_replace('/\[ASAMBLEA_ID:\d+\]/', '', str_replace('[REPORTE_DIRECTIVA] ', '', $msg->mensaje));
                    $textoLimpio = trim(str_replace(['**', '__'], '', $textoLimpio));

                    $hash = hash('sha256', $textoLimpio);
                    $voto = $feedbackUsuario[$hash] ?? null;

                    $calificacion = match ($voto) {
                        'positivo' => true,
                        'negativo' => false,
                        default    => null,
                    };
                }

                return [
                    'texto'        => $msg->mensaje,
                    'esUsuario'    => $esUsuario,
                    'hora'         => $fecha->format('h:i a'),
                    'calificacion' => $calificacion,
                ];
            });

            return response()->json([
                'success' => true,
                'mensajes' => $mensajesFormateados
            ]);

        } catch (\Throwable $e) {
            Log::error('Error al cargar conversación de ZOE', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la conversación.',
            ], 500);
        }
    }

    public function enviarMensajeConArchivo(Request $request)
    {
        $request->validate([
            'mensaje' => 'nullable|string|max:2000',
            'archivo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:15360',
        ]);

        $rutaRelativa = null;

        if ($request->hasFile('archivo')) {
            $archivo = $request->file('archivo');
            $path = $archivo->store('chat_zoe', 'public');
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

    public function calificarMensaje(Request $request)
    {
        $validated = $request->validate([
            'mensaje_texto' => 'required|string|max:5000',
            'voto'          => 'required|in:positivo,negativo,ninguno',
        ]);

        $usuarioId = $request->user()->id;

        DB::table('zoe_feedback')->updateOrInsert(
            [
                'usuario_id'   => $usuarioId,
                'mensaje_hash' => hash('sha256', trim($validated['mensaje_texto'])),
            ],
            [
                'mensaje_texto' => trim($validated['mensaje_texto']),
                'voto'          => $validated['voto'],
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Calificación registrada',
        ]);
    }
}