<?php

namespace App\Services\chat;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatUserService
{
    /**
     * Agrega un usuario al Chat Vecinal si todavía no pertenece.
     */
    public function agregarUsuario(
        ChatConversation $chat,
        User $usuario
    ): void {

        $chat->usuarios()->syncWithoutDetaching([
            $usuario->id => [
                'rol' => 'miembro',
            ],
        ]);
    }

    /**
     * Determina si un usuario pertenece al Chat Vecinal.
     */
    public function usuarioPerteneceAlChat(
        ChatConversation $chat,
        User $usuario
    ): bool {

        return $chat
            ->usuarios()
            ->where('users.id', $usuario->id)
            ->exists();
    }

    /**
     * Obtiene los usuarios que pertenecen al Chat Vecinal.
     */
    public function obtenerUsuarios(
        ChatConversation $chat
    ): Collection {

        return $chat
            ->usuarios()
            ->orderBy('name')
            ->get();
    }

    /**
     * Cuenta los usuarios que pertenecen al Chat Vecinal.
     */
    public function contarUsuarios(
        ChatConversation $chat
    ): int {

        return $chat
            ->usuarios()
            ->count();
    }
}
