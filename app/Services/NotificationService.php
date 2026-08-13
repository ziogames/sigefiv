<?php

namespace App\Services;

use App\Models\Bitacora;

class NotificationService
{
    /**
     * Últimas notificaciones para el navbar.
     */
    public static function ultimas(int $limite = 5)
    {
        return Bitacora::with('user')
            ->latest()
            ->take($limite)
            ->get()
            ->map(function ($item) {

                return [

                    'titulo'  => $item->modulo,

                    'mensaje' => $item->descripcion,

                    'icono'   => $item->icono,

                    'color'   => $item->color,

                    'usuario' => optional($item->user)->name,

                    'tiempo'  => $item->created_at->diffForHumans(),

                ];

            });

    }

    /**
     * Cantidad de notificaciones.
     */
    public static function cantidad(int $limite = 5): int
    {
        return Bitacora::latest()
            ->take($limite)
            ->count();
    }
}