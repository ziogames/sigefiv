<?php

namespace App\Services\chat;

use App\Models\ChatMessage;
use Illuminate\Support\Collection;

class ChatResponseService
{
    /**
     * Prepara un mensaje para enviarlo al frontend.
     *
     * Mantiene la misma estructura que utiliza actualmente
     * el ChatController.
     */
    public function mensaje(
        ChatMessage $mensaje
    ): array {

        $usuario =
            $mensaje->usuario;

        $mensajePadre =
            $mensaje->mensajePadre;

        /*
         * Cargamos las reacciones del mensaje.
         *
         * Cada usuario solamente puede tener una reacción
         * por mensaje.
         */
        $reacciones =
            $mensaje->reacciones
                ->groupBy('emoji')
                ->map(
                    function (
                        Collection $reaccionesEmoji,
                        string $emoji
                    ) {

                        $usuarioActualId =
                            auth()->id();

                        $yo =
                            $usuarioActualId !== null
                            && $reaccionesEmoji->contains(
                                function ($reaccion) use (
                                    $usuarioActualId
                                ) {
                                    return (int) $reaccion->user_id
                                        === (int) $usuarioActualId;
                                }
                            );

                        return [
                            'emoji' =>
                                $emoji,

                            'cantidad' =>
                                $reaccionesEmoji->count(),

                            'yo' =>
                                $yo,
                        ];
                    }
                )
                ->values()
                ->all();

        return [
            'id' =>
                $mensaje->id,

            'mensaje' =>
                $mensaje->mensaje,

            'tipo' =>
                $mensaje->tipo,

            'editado' =>
                $mensaje->editado,

            'created_at' =>
                $mensaje->created_at
                    ?->format('H:i'),

            'usuario' =>
                $usuario
                    ? [
                        'id' =>
                            $usuario->id,

                        'name' =>
                            $usuario->name,

                        'avatar' =>
                            $usuario->avatar,
                    ]
                    : null,

            // 💬 Mensaje al que se está respondiendo
            'reply_to' =>
                $mensajePadre
                    ? [
                        'id' =>
                            $mensajePadre->id,

                        'mensaje' =>
                            $mensajePadre->mensaje,

                        'usuario' =>
                            $mensajePadre->usuario
                                ? [
                                    'id' =>
                                        $mensajePadre->usuario->id,

                                    'name' =>
                                        $mensajePadre->usuario->name,
                                ]
                                : null,
                    ]
                    : null,

            /*
             * ❤️ Reacciones del mensaje
             */
            'reacciones' =>
                $reacciones,
        ];
    }

    /**
     * Prepara una colección de mensajes
     * para enviarla al frontend.
     */
    public function mensajes(
        Collection $mensajes
    ): Collection {

        /*
         * Cargamos las reacciones de todos los mensajes
         * antes de procesarlos.
         *
         * Esto evita realizar una consulta independiente
         * por cada mensaje.
         */
        $mensajes->loadMissing('reacciones');

        return $mensajes
            ->map(
                function (
                    ChatMessage $mensaje
                ) {

                    return $this->mensaje(
                        $mensaje
                    );

                }
            )
            ->values();
    }

    /**
     * Prepara la información del usuario
     * que acaba de enviar un mensaje.
     *
     * Se utiliza cuando store() devuelve
     * el mensaje recién creado.
     */
    public function usuario(
        $usuario
    ): array {

        return [
            'id' =>
                $usuario->id,

            'name' =>
                $usuario->name,

            'avatar' =>
                $usuario->avatar,
        ];
    }
}