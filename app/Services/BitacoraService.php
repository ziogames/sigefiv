<?php

namespace App\Services;

use App\Models\Bitacora;

class BitacoraService
{
    /**
     * Registrar una acción en la bitácora.
     */
    public static function registrar(
        string $modulo,
        string $accion,
        string $descripcion
    ): void {

        Bitacora::create([

            'user_id' => auth()->id(),

            'modulo' => $modulo,

            'accion' => $accion,

            'descripcion' => $descripcion,

            'ip' => request()->ip(),

            'user_agent' => request()->userAgent(),

        ]);

    }
}