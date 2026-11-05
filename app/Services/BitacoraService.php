<?php

namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Support\Facades\Log;

class BitacoraService
{
    /**
     * Registrar una acción en la bitácora.
     */
    public static function registrar(
        string $modulo,
        string $accion,
        string $descripcion,
        ?int $userId = null
    ): void {
        try {
            Bitacora::create([
                'user_id'     => $userId ?? auth()->id() ?? auth('sanctum')->id(),
                'modulo'      => $modulo,
                'accion'      => $accion,
                'descripcion' => $descripcion,
                'ip'          => request()->ip() ?? '127.0.0.1',
                'user_agent'  => request()->userAgent() ?? 'Sistema',
            ]);
        } catch (\Throwable $e) {
            Log::error("Error al registrar en bitácora: " . $e->getMessage());
        }
    }
}