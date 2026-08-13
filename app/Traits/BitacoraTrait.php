<?php

namespace App\Traits;

use App\Services\BitacoraService;

trait BitacoraTrait
{
    protected function registrarBitacora(
        string $modulo,
        string $accion,
        string $descripcion
    ): void {

        BitacoraService::registrar(
            $modulo,
            $accion,
            $descripcion
        );

    }
}