<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\User;
use App\Services\chat\ChatPresenceService;
use App\Services\chat\ChatUserService;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    public function __construct(
        protected ChatPresenceService $chatPresenceService,
        protected ChatUserService $chatUserService
    ) {
    }

    /**
     * Obtiene o crea el Chat Vecinal público.
     */
    public function obtenerChatVecinal(): ChatConversation
    {
        return ChatConversation::firstOrCreate(
            [
                'tipo' => 'publico',
                'nombre' => 'Chat Vecinal',
            ],
            [
                'descripcion' =>
                    'Chat público de los usuarios de SIGEFIV.',

                'activo' => true,
            ]
        );
    }

    /**
     * Agrega un usuario al Chat Vecinal si todavía no pertenece.
     *
     * Se mantiene este método como punto de compatibilidad
     * mientras la lógica de usuarios vive en ChatUserService.
     */
    public function agregarUsuario(User $usuario): void
    {
        $this->chatUserService->agregarUsuario(
            $this->obtenerChatVecinal(),
            $usuario
        );
    }

    /**
     * Determina si un usuario pertenece al Chat Vecinal.
     */
    public function usuarioPerteneceAlChat(
        User $usuario
    ): bool {

        return $this->chatUserService->usuarioPerteneceAlChat(
            $this->obtenerChatVecinal(),
            $usuario
        );
    }

    /**
     * Obtiene los usuarios que pertenecen al Chat Vecinal.
     */
    public function obtenerUsuarios(): Collection
    {
        return $this->chatUserService->obtenerUsuarios(
            $this->obtenerChatVecinal()
        );
    }

    /**
     * Cuenta los usuarios que pertenecen al Chat Vecinal.
     */
    public function contarUsuarios(): int
    {
        return $this->chatUserService->contarUsuarios(
            $this->obtenerChatVecinal()
        );
    }

    /**
     * Registra o actualiza la presencia de un usuario.
     */
    public function actualizarPresencia(User $usuario)
    {
        return $this->chatPresenceService
            ->actualizarPresencia($usuario);
    }

    /**
     * Obtiene el registro de presencia de un usuario.
     */
    public function obtenerPresencia(User $usuario)
    {
        return $this->chatPresenceService
            ->obtenerPresencia($usuario);
    }

    /**
     * Determina si un usuario está actualmente conectado.
     */
    public function usuarioEstaEnLinea(User $usuario): bool
    {
        return $this->chatPresenceService
            ->usuarioEstaEnLinea($usuario);
    }

    /**
     * Obtiene los usuarios del Chat Vecinal que están en línea.
     */
    public function obtenerUsuariosEnLinea(): Collection
    {
        return $this->chatPresenceService
            ->obtenerUsuariosEnLinea(
                $this->obtenerChatVecinal()
            );
    }

    /**
     * Cuenta los usuarios del Chat Vecinal que están en línea.
     */
    public function contarUsuariosEnLinea(): int
    {
        return $this->chatPresenceService
            ->contarUsuariosEnLinea(
                $this->obtenerChatVecinal()
            );
    }

    /**
     * Obtiene todos los usuarios del Chat Vecinal
     * junto con su información de presencia.
     */
    public function obtenerUsuariosConPresencia(): Collection
    {
        return $this->chatPresenceService
            ->obtenerUsuariosConPresencia(
                $this->obtenerChatVecinal()
            );
    }

    /**
     * Obtiene la información necesaria para mostrar
     * el listado de personas del Chat Vecinal.
     */
    public function obtenerPersonasChat(): Collection
    {
        return $this->chatPresenceService
            ->obtenerPersonasChat(
                $this->obtenerChatVecinal()
            );
    }

    /**
     * Cuenta las personas que están actualmente
     * en línea en el Chat Vecinal.
     */
    public function obtenerResumenPresencia(): array
    {
        return $this->chatPresenceService
            ->obtenerResumenPresencia(
                $this->obtenerChatVecinal()
            );
    }
}
