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
            | Buscar si el token ya existe
            |--------------------------------------------------------------------------
            */

            $fcmToken = FcmToken::query()
                ->where('token', $datos['token'])
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Token existente
            |--------------------------------------------------------------------------
            |
            | NO modificamos:
            |
            | - activo
            | - ingresos
            | - egresos
            | - zoe
            | - avisos
            |
            | De esta manera las preferencias del usuario se conservan.
            |
            */

            if ($fcmToken) {

                $fcmToken->update([
                    'user_id' => $usuario->id,
                    'plataforma' =>
                        $datos['plataforma'] ?? $fcmToken->plataforma,
                    'ultimo_acceso' => now(),
                ]);

            } else {

                /*
                |--------------------------------------------------------------------------
                | Token nuevo
                |--------------------------------------------------------------------------
                |
                | Un dispositivo nuevo comienza:
                |
                | activo     = true
                | ingresos   = true
                | egresos    = true
                | zoe        = true
                | avisos     = true
                |
                */

                $fcmToken = FcmToken::create([
                    'user_id' => $usuario->id,
                    'token' => $datos['token'],
                    'plataforma' =>
                        $datos['plataforma'] ?? 'android',
                    'activo' => true,
                    'ingresos' => true,
                    'egresos' => true,
                    'zoe' => true,
                    'avisos' => true,
                    'ultimo_acceso' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token FCM registrado correctamente.',
                'fcm_token' => [
                    'id' => $fcmToken->id,
                    'plataforma' => $fcmToken->plataforma,
                    'activo' => $fcmToken->activo,
                    'ingresos' => $fcmToken->ingresos,
                    'egresos' => $fcmToken->egresos,
                    'zoe' => $fcmToken->zoe,
                    'avisos' => $fcmToken->avisos,
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