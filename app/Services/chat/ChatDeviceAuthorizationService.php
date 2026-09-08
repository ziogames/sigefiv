<?php

namespace App\Services\chat;

use App\Models\User;

class ChatDeviceAuthorizationService
{
    /**
     * Determina si un usuario está autorizado
     * para enviar órdenes a los dispositivos.
     *
     * La autorización utiliza el rol real de SIGEFIV.
     *
     * La comparación del nombre del rol se realiza
     * sin distinguir mayúsculas y minúsculas para evitar
     * problemas de capitalización o configuración del guard.
     */
    public function puedeControlarDispositivos(
        User $usuario
    ): bool {
        return $usuario
            ->roles()
            ->whereRaw(
                'LOWER(name) = ?',
                ['secretario']
            )
            ->exists();
    }
}