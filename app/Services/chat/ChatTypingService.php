<?php

namespace App\Services\chat;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class ChatTypingService
{
    /**
     * Actualiza el estado temporal de escritura de un usuario.
     *
     * El estado dura unos segundos y debe ser renovado
     * periódicamente por el JavaScript mientras el usuario escribe.
     */
    public function actualizarEstado(
        ChatConversation $chat,
        User $usuario,
        bool $escribiendo
    ): void {

        $estados = Cache::get(
            'chat.typing',
            []
        );

        $ahora =
            now()->timestamp;

        /*
        |--------------------------------------------------------------------------
        | Limpiar estados vencidos
        |--------------------------------------------------------------------------
        */

        foreach ($estados as $usuarioId => $venceEn) {

            if ((int) $venceEn <= $ahora) {

                unset(
                    $estados[$usuarioId]
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Actualizar estado del usuario
        |--------------------------------------------------------------------------
        */

        if ($escribiendo) {

            // El estado dura 4 segundos.
            $estados[
                (string) $usuario->id
            ] =
                $ahora + 4;

        } else {

            unset(
                $estados[
                    (string) $usuario->id
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Guardar estados
        |--------------------------------------------------------------------------
        */

        Cache::put(
            'chat.typing',
            $estados,
            now()->addSeconds(10)
        );
    }

    /**
     * Obtiene los usuarios que están escribiendo actualmente.
     */
    public function obtenerUsuariosEscribiendo(
        ChatConversation $chat,
        iterable $usuarios
    ): array {

        $estados =
            Cache::get(
                'chat.typing',
                []
            );

        $ahora =
            now()->timestamp;

        /*
        |--------------------------------------------------------------------------
        | Eliminar estados vencidos
        |--------------------------------------------------------------------------
        */

        foreach ($estados as $usuarioId => $venceEn) {

            if ((int) $venceEn <= $ahora) {

                unset(
                    $estados[$usuarioId]
                );
            }
        }

        if (!$estados) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | Convertir IDs a enteros
        |--------------------------------------------------------------------------
        */

        $ids =
            array_map(
                'intval',
                array_keys($estados)
            );

        /*
        |--------------------------------------------------------------------------
        | Preparar usuarios que están escribiendo
        |--------------------------------------------------------------------------
        */

        $resultado = [];

        foreach ($usuarios as $usuario) {

            if (!$usuario instanceof User) {
                continue;
            }

            if (!in_array(
                $usuario->id,
                $ids,
                true
            )) {
                continue;
            }

            $venceEn =
                (int) (
                    $estados[
                        (string) $usuario->id
                    ] ?? 0
                );

            if ($venceEn <= $ahora) {
                continue;
            }

            $resultado[] = [
                'id' =>
                    $usuario->id,

                'name' =>
                    $usuario->seudonimo
                        ?: $usuario->name,
            ];
        }

        return $resultado;
    }
}