<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatDeviceController extends Controller
{
    /**
     * Devuelve el estado actual de un dispositivo.
     *
     * Esta información será consultada posteriormente
     * por el ESP32-S3 mediante Wi-Fi.
     */
    public function estado(
        Request $request,
        ChatDevice $device
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'device' => [
                'id' => $device->id,
                'nombre' => $device->nombre,
                'tipo' => $device->tipo,
                'ubicacion' => $device->ubicacion,
                'estado' => $device->estado,
                'apagado_programado' =>
                    $device->apagado_programado?->toIso8601String(),
            ],
        ]);
    }
    public function estadoPorToken(
    Request $request,
    ChatDevice $device
): JsonResponse {
    $token =
        $request->header('X-Device-Token');

    if (
        !$token ||
        !$device->api_token ||
        !hash_equals(
            $device->api_token,
            $token
        )
    ) {
        return response()->json([
            'success' => false,
            'message' => 'Credencial de dispositivo inválida.',
        ], 401);
    }

    return response()->json([
        'success' => true,
        'device' => [
            'id' => $device->id,
            'nombre' => $device->nombre,
            'tipo' => $device->tipo,
            'ubicacion' => $device->ubicacion,
            'estado' => $device->estado,
            'apagado_programado' =>
                $device->apagado_programado?->toIso8601String(),
        ],
    ]);
}
public function controlarPorToken(
    Request $request,
    ChatDevice $device
): JsonResponse {
    $token =
        $request->header('X-Device-Token');

    if (
        !$token ||
        !$device->api_token ||
        !hash_equals(
            $device->api_token,
            $token
        )
    ) {
        return response()->json([
            'success' => false,
            'message' =>
                'Credencial de dispositivo inválida.',
        ], 401);
    }

    $validated = $request->validate([
        'accion' => [
            'required',
            'string',
            'in:encender,apagar',
        ],
    ]);

    if ($validated['accion'] === 'encender') {
        $device->update([
            'estado' => 'encendido',
        ]);
    }

    if ($validated['accion'] === 'apagar') {
        $device->update([
            'estado' => 'apagado',
            'apagado_programado' => null,
        ]);
    }

    return response()->json([
        'success' => true,
        'device' => [
            'id' => $device->id,
            'nombre' => $device->nombre,
            'estado' => $device->fresh()->estado,
            'apagado_programado' =>
                $device->fresh()
                    ->apagado_programado
                    ?->toIso8601String(),
        ],
    ]);
}

}