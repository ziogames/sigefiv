<?php

namespace App\Services\chat;

use App\Models\ChatDevice;
use App\Models\User;
use Carbon\Carbon;

class ChatDeviceService
{
    /**
     * Obtiene un dispositivo por su nombre.
     */
    public function obtenerPorNombre(
        string $nombre
    ): ?ChatDevice {
        return ChatDevice::where(
            'nombre',
            $nombre
        )->first();
    }

    /**
     * Enciende un dispositivo durante una cantidad
     * determinada de minutos.
     */
    public function encenderPorMinutos(
        ChatDevice $dispositivo,
        int $minutos,
        User $usuario,
        string $orden
    ): ChatDevice {
        $apagadoProgramado = now()->addMinutes($minutos);

        $dispositivo->update([
            'estado' => 'encendido',
            'apagado_programado' => $apagadoProgramado,
            'usuario_ultima_orden_id' => $usuario->id,
            'ultima_orden' => $orden,
        ]);

        return $dispositivo->fresh();
    }

    /**
     * Enciende un dispositivo hasta una hora
     * determinada.
     */
    public function encenderHasta(
        ChatDevice $dispositivo,
        Carbon $apagadoProgramado,
        User $usuario,
        string $orden
    ): ChatDevice {
        $dispositivo->update([
            'estado' => 'encendido',
            'apagado_programado' => $apagadoProgramado,
            'usuario_ultima_orden_id' => $usuario->id,
            'ultima_orden' => $orden,
        ]);

        return $dispositivo->fresh();
    }

    /**
     * Apaga inmediatamente un dispositivo.
     */
    public function apagar(
        ChatDevice $dispositivo,
        User $usuario,
        string $orden
    ): ChatDevice {
        $dispositivo->update([
            'estado' => 'apagado',
            'apagado_programado' => null,
            'usuario_ultima_orden_id' => $usuario->id,
            'ultima_orden' => $orden,
        ]);

        return $dispositivo->fresh();
    }

    /**
     * Agrega minutos al tiempo restante de un dispositivo.
     */
    public function agregarMinutos(
        ChatDevice $dispositivo,
        int $minutos,
        User $usuario,
        string $orden
    ): ChatDevice {
        $ahora = now();

        if (
            $dispositivo->estado !== 'encendido' ||
            !$dispositivo->apagado_programado
        ) {
            $apagadoProgramado = $ahora->copy()->addMinutes($minutos);
        } else {
            $apagadoProgramado =
                Carbon::parse(
                    $dispositivo->apagado_programado
                )->addMinutes($minutos);
        }

        $dispositivo->update([
            'estado' => 'encendido',
            'apagado_programado' => $apagadoProgramado,
            'usuario_ultima_orden_id' => $usuario->id,
            'ultima_orden' => $orden,
        ]);

        return $dispositivo->fresh();
    }

    /**
     * Reemplaza el tiempo restante por una nueva duración.
     */
    public function reemplazarDuracion(
        ChatDevice $dispositivo,
        int $minutos,
        User $usuario,
        string $orden
    ): ChatDevice {
        $apagadoProgramado = now()->addMinutes($minutos);

        $dispositivo->update([
            'estado' => 'encendido',
            'apagado_programado' => $apagadoProgramado,
            'usuario_ultima_orden_id' => $usuario->id,
            'ultima_orden' => $orden,
        ]);

        return $dispositivo->fresh();
    }

    /**
     * Comprueba si el dispositivo tiene programado
     * un apagado que ya llegó.
     */
    public function necesitaApagado(
        ChatDevice $dispositivo
    ): bool {
        if (
            $dispositivo->estado !== 'encendido' ||
            !$dispositivo->apagado_programado
        ) {
            return false;
        }

        return now()->greaterThanOrEqualTo(
            Carbon::parse(
                $dispositivo->apagado_programado
            )
        );
    }

    /**
     * Apaga el dispositivo si ya venció su programación.
     */
    public function apagarSiCorresponde(
        ChatDevice $dispositivo
    ): bool {
        if (!$this->necesitaApagado($dispositivo)) {
            return false;
        }

        $dispositivo->update([
            'estado' => 'apagado',
            'apagado_programado' => null,
        ]);

        return true;
    }
}