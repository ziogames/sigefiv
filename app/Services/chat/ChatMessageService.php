<?php

namespace App\Services\chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;

class ChatMessageService
{
    /**
     * Crea un mensaje enviado por un usuario
     * dentro del Chat Vecinal.
     *
     * El contenido recibido puede ser:
     *
     * - texto normal
     * - JSON con información de un archivo adjunto
     */
    public function crearMensaje(
        ChatConversation $chat,
        User $usuario,
        string $contenido,
        ?int $replyToId = null // Parámetro que viene del controlador
    ): ChatMessage {

        $mensaje =
            ChatMessage::create([
                'conversation_id' =>
                    $chat->id,

                'user_id' =>
                    $usuario->id,

                'tipo' =>
                    'usuario',

                'mensaje' =>
                    $contenido,

                'mensaje_padre_id' =>
                    $replyToId, // 👈 Usamos la columna real de tu modelo

                'editado' =>
                    false,
            ]);

        /*
         |--------------------------------------------------------------------------
         | Cargar relaciones (usuario y mensaje padre con su respectivo usuario)
         |--------------------------------------------------------------------------
         */

        $mensaje->load(['usuario', 'mensajePadre.usuario']); // 👈 Usamos la relación real de tu modelo

        return $mensaje;
    }
}