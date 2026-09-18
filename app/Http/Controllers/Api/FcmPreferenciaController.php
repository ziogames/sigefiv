<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FcmPreferenciaController extends Controller
{
    /**
     * Obtener las preferencias FCM del dispositivo actual.
     *
     * GET /api/fcm/preferencia
     */
    public function obtener(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'token' => [
                'required',
                'string',
                'max:4096',
            ],
        ]);

        $fcmToken = FcmToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $datos['token'])
            ->first();

        if (!$fcmToken) {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo no está registrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferencias obtenidas correctamente.',
            'data' => [
                'activo' => $fcmToken->activo,
                'ingresos' => $fcmToken->ingresos ?? true,
                'egresos' => $fcmToken->egresos ?? true,
                'zoe' => $fcmToken->zoe ?? true,
                'avisos' => $fcmToken->avisos ?? true,
                'plataforma' => $fcmToken->plataforma,
            ],
        ]);
    }

    /**
     * Actualizar las preferencias FCM del dispositivo.
     *
     * POST /api/fcm/preferencia
     */
    public function actualizar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'token' => [
                'required',
                'string',
                'max:4096',
            ],

            'activo' => [
                'required',
                'boolean',
            ],

            'ingresos' => [
                'sometimes',
                'boolean',
            ],

            'egresos' => [
                'sometimes',
                'boolean',
            ],

            'zoe' => [
                'sometimes',
                'boolean',
            ],

            'avisos' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $fcmToken = FcmToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $datos['token'])
            ->first();

        if (!$fcmToken) {
            return response()->json([
                'success' => false,
                'message' => 'El dispositivo no está registrado.',
            ], 404);
        }

        /*
         * Conservamos las preferencias individuales cuando
         * solamente se modifica el interruptor general.
         */
        $ingresos = array_key_exists('ingresos', $datos)
            ? $datos['ingresos']
            : ($fcmToken->ingresos ?? true);

        $egresos = array_key_exists('egresos', $datos)
            ? $datos['egresos']
            : ($fcmToken->egresos ?? true);

        $zoe = array_key_exists('zoe', $datos)
            ? $datos['zoe']
            : ($fcmToken->zoe ?? true);

        $avisos = array_key_exists('avisos', $datos)
            ? $datos['avisos']
            : ($fcmToken->avisos ?? true);

        $fcmToken->update([
            'activo' => $datos['activo'],
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'zoe' => $zoe,
            'avisos' => $avisos,
            'ultimo_acceso' => now(),
        ]);

        return response()->json([
            'success' => true,

            'message' => $datos['activo']
                ? 'Las notificaciones fueron activadas.'
                : 'Las notificaciones fueron desactivadas.',

            'data' => [
                'activo' => $fcmToken->activo,
                'ingresos' => $fcmToken->ingresos,
                'egresos' => $fcmToken->egresos,
                'zoe' => $fcmToken->zoe,
                'avisos' => $fcmToken->avisos,
                'plataforma' => $fcmToken->plataforma,
            ],
        ]);
    }
}