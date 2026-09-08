<?php

namespace App\Services\chat;

use App\Models\ChatConversation;
use App\Models\ChatUserPresence;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatPresenceService
{
    /**
     * Registra o actualiza la presencia de un usuario.
     */
    public function actualizarPresencia(
        User $usuario
    ): ChatUserPresence {

        return ChatUserPresence::updateOrCreate(
            [
                'user_id' => $usuario->id,
            ],
            [
                'ultimo_visto_at' => now(),
            ]
        );
    }

    /**
     * Obtiene el registro de presencia de un usuario.
     */
    public function obtenerPresencia(
        User $usuario
    ): ?ChatUserPresence {

        return ChatUserPresence::where(
            'user_id',
            $usuario->id
        )->first();
    }

    /**
     * Determina si un usuario está actualmente conectado.
     */
    public function usuarioEstaEnLinea(
        User $usuario
    ): bool {

        $presencia =
            $this->obtenerPresencia($usuario);

        if (!$presencia) {
            return false;
        }

        return $presencia->estaEnLinea();
    }

    /**
     * Obtiene los usuarios del Chat Vecinal que están en línea.
     */
    public function obtenerUsuariosEnLinea(
        ChatConversation $chat
    ): Collection {

        $usuarios =
            $chat
                ->usuarios()
                ->orderBy('name')
                ->get();

        return $usuarios
            ->filter(function (User $usuario) {

                return $this->usuarioEstaEnLinea(
                    $usuario
                );

            })
            ->values();
    }

    /**
     * Cuenta los usuarios que están en línea.
     */
    public function contarUsuariosEnLinea(
        ChatConversation $chat
    ): int {

        return $this
            ->obtenerUsuariosEnLinea($chat)
            ->count();
    }

    /**
     * Obtiene los usuarios junto con su presencia.
     */
    public function obtenerUsuariosConPresencia(
        ChatConversation $chat
    ): Collection {

        $usuarios =
            $chat
                ->usuarios()
                ->orderBy('name')
                ->get();

        $userIds =
            $usuarios
                ->pluck('id')
                ->values();

        $presencias =
            ChatUserPresence::whereIn(
                'user_id',
                $userIds
            )
            ->get()
            ->keyBy('user_id');

        $usuarios->each(
            function (User $usuario) use ($presencias) {

                $presencia =
                    $presencias->get(
                        $usuario->id
                    );

                $usuario->setRelation(
                    'presenciaChat',
                    $presencia
                );
            }
        );

        return $usuarios;
    }

    /**
     * Obtiene las personas del Chat Vecinal
     * ordenadas por estado de conexión.
     */
    public function obtenerPersonasChat(
        ChatConversation $chat
    ): Collection {

        $usuarios =
            $this->obtenerUsuariosConPresencia(
                $chat
            );

        return $usuarios
            ->sortByDesc(
                function (User $usuario) {

                    $presencia =
                        $usuario->getRelation(
                            'presenciaChat'
                        );

                    if (!$presencia) {
                        return 0;
                    }

                    return $presencia->estaEnLinea()
                        ? 2
                        : 1;
                }
            )
            ->values();
    }

    /**
     * Obtiene el resumen de presencia.
     */
    public function obtenerResumenPresencia(
        ChatConversation $chat
    ): array {

        $personas =
            $this->obtenerPersonasChat(
                $chat
            );

        $enLinea =
            $personas->filter(
                function (User $usuario) {

                    $presencia =
                        $usuario->getRelation(
                            'presenciaChat'
                        );

                    return $presencia
                        && $presencia->estaEnLinea();
                }
            )->count();

        return [
            'total' =>
                $personas->count(),

            'en_linea' =>
                $enLinea,

            'fuera_de_linea' =>
                $personas->count() - $enLinea,
        ];
    }

    /**
     * Obtiene todos los datos necesarios para
     * actualizar la presencia del Chat Vecinal.
     *
     * El controlador no necesita conocer cómo
     * se construyen estos datos.
     */
    public function obtenerDatosPresencia(
        ChatConversation $chat,
        User $usuario
    ): array {

        $presencia =
            $this->actualizarPresencia(
                $usuario
            );

        $personas =
            $this->obtenerPersonasChat(
                $chat
            );

        $resumen =
            $this->obtenerResumenPresencia(
                $chat
            );

        $usuarios =
            $personas->map(
                function (User $persona) {

                    $presencia =
                        $persona->getRelation(
                            'presenciaChat'
                        );

                    return [
                        'id' =>
                            $persona->id,

                        'name' =>
                            $persona->name,

                        'avatar' =>
                            $persona->avatar,

                        'en_linea' =>
                            $presencia
                                && $presencia->estaEnLinea(),

                        'ultimo_visto_at' =>
                            $presencia
                                ?->ultimo_visto_at
                                ?->toIso8601String(),
                    ];
                }
            )->values();

        return [
            'en_linea' =>
                true,

            'ultimo_visto_at' =>
                $presencia->ultimo_visto_at
                    ?->toIso8601String(),

            'personas_en_linea' =>
                $resumen['en_linea'],

            'personas' =>
                $resumen['total'],

            'usuarios' =>
                $usuarios,
        ];
    }
}