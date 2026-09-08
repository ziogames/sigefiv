<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FcmTokenController extends Controller
{
    /**
     * Registrar o actualizar el token FCM
     * del dispositivo del usuario autenticado.
     *
     * POST /api/fcm/token
     */
    public function registrar(Request $request): JsonResponse
    {
        try {

            $datos = $request->validate([
                'token' => [
                    'required',
                    'string',
                    'max:4096',
                ],

                'plataforma' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
            ]);

            $usuario = $request->user();

            /*
            |--------------------------------------------------------------------------
            | Registrar o recuperar el token
            |--------------------------------------------------------------------------
            */

            $fcmToken = FcmToken::updateOrCreate(
                [
                    'token' => $datos['token'],
                ],
                [
                    'user_id' => $usuario->id,
                    'plataforma' => $datos['plataforma'] ?? 'android',
                    'activo' => true,
                    'ultimo_acceso' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Token FCM registrado correctamente.',
                'fcm_token' => [
                    'id' => $fcmToken->id,
                    'plataforma' => $fcmToken->plataforma,
                    'activo' => $fcmToken->activo,
                    'ultimo_acceso' => $fcmToken->ultimo_acceso,
                ],
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar el token FCM.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desactivar un token FCM del usuario autenticado.
     *
     * POST /api/fcm/token/desactivar
     */
    public function desactivar(Request $request): JsonResponse
    {
        try {

            $datos = $request->validate([
                'token' => [
                    'required',
                    'string',
                    'max:4096',
                ],
            ]);

            $actualizado = FcmToken::where(
                'user_id',
                $request->user()->id
            )
                ->where(
                    'token',
                    $datos['token']
                )
                ->update([
                    'activo' => false,
                ]);

            return response()->json([
                'success' => true,
                'message' => $actualizado
                    ? 'Token FCM desactivado correctamente.'
                    : 'El token FCM no estaba registrado.',
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'No se pudo desactivar el token FCM.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}