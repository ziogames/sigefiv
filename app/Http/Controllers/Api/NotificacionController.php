<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    /**
     * Obtener las notificaciones del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $notificaciones = Notificacion::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notificaciones->items(),
            'total' => $notificaciones->total(),
            'current_page' => $notificaciones->currentPage(),
            'last_page' => $notificaciones->lastPage(),
        ]);
    }

    /**
     * Obtener solamente las notificaciones no leídas.
     */
    public function noLeidas(Request $request): JsonResponse
    {
        $notificaciones = Notificacion::query()
            ->where('user_id', $request->user()->id)
            ->where('leida', false)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notificaciones,
            'total' => $notificaciones->count(),
            'current_page' => 1,
            'last_page' => 1,
        ]);
    }

    /**
     * Marcar una notificación como leída.
     */
    public function marcarLeida(
        Request $request,
        int $id
    ): JsonResponse {
        $notificacion = Notificacion::query()
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$notificacion) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada.',
            ], 404);
        }

        if (!$notificacion->leida) {
            $notificacion->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída.',
            'data' => $notificacion->fresh(),
        ]);
    }

    /**
     * Marcar todas las notificaciones del usuario como leídas.
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        Notificacion::query()
            ->where('user_id', $request->user()->id)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fecha_lectura' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones fueron marcadas como leídas.',
        ]);
    }

    public function enviar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'titulo' => [
                'required',
                'string',
                'max:255',
            ],

            'mensaje' => [
                'required',
                'string',
                'max:5000',
            ],

            'tipo' => [
                'required',
                'string',
                'max:50',
            ],

            'destinatario' => [
                'required',
                'string',
                'in:todos,directiva,usuarios',
            ],

            'usuario_ids' => [
                'nullable',
                'array',
                'required_if:destinatario,usuarios',
            ],

            'usuario_ids.*' => [
                'integer',
                'exists:users,id',
            ],
        ]);

        $data = [];

        $notificacionService =
            app(\App\Services\NotificacionService::class);

        if ($datos['destinatario'] === 'todos') {

            $enviados = $notificacionService
                ->enviarATodos(
                    titulo: $datos['titulo'],
                    mensaje: $datos['mensaje'],
                    tipo: $datos['tipo'],
                    data: $data,
                    usuarioExcluido: $request->user()->id
                );

        } elseif ($datos['destinatario'] === 'directiva') {

            $enviados = $notificacionService
                ->enviarADirectiva(
                    titulo: $datos['titulo'],
                    mensaje: $datos['mensaje'],
                    tipo: $datos['tipo'],
                    data: $data
                );

        } else {

            $enviados = $notificacionService
                ->enviarAUsuarios(
                    usuarioIds: $datos['usuario_ids'],
                    titulo: $datos['titulo'],
                    mensaje: $datos['mensaje'],
                    tipo: $datos['tipo'],
                    data: $data
                );
        }

        return response()->json([
            'success' => true,
            'message' => 'Notificación enviada correctamente.',
            'data' => [
                'destinatario' => $datos['destinatario'],
                'usuario_ids' => $datos['usuario_ids'] ?? null,
                'enviados' => $enviados,
            ],
        ]);
    }
}