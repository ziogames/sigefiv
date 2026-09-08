<?php

namespace App\Services\chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\SigiEventoService;
use App\Services\SigiIntervencionService;
use App\Services\SigiService;

class ChatSigiInterventionService
{
    public function __construct(
        protected SigiService $sigiService,
        protected SigiEventoService $sigiEventoService,
        protected SigiIntervencionService $sigiIntervencionService
    ) {
    }

    /**
     * Analiza un mensaje y determina si SIGI
     * debe intervenir automáticamente.
     *
     * Este servicio NO procesa las consultas explícitas
     * dirigidas a @sigi.
     *
     * Su responsabilidad es únicamente el flujo
     * de intervención contextual automática.
     */
    public function procesar(
        ChatConversation $chat,
        ChatMessage $mensaje
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Analizar mensaje
        |--------------------------------------------------------------------------
        */

        $analisisSigi =
            $this->sigiService->analizarMensaje(
                $mensaje
            );

        /*
        |--------------------------------------------------------------------------
        | Procesar evento
        |--------------------------------------------------------------------------
        */

        $resultadoEvento =
            $this->sigiEventoService->procesarMensaje(
                $mensaje,
                $analisisSigi
            );

        /*
        |--------------------------------------------------------------------------
        | Decisión de SIGI
        |--------------------------------------------------------------------------
        */

        $decisionSigi = null;

        if (
            $resultadoEvento['evento']
        ) {

            $decisionSigi =
                $this->sigiIntervencionService->evaluar(
                    $resultadoEvento['evento'],
                    $analisisSigi
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Respuesta contextual
        |--------------------------------------------------------------------------
        */

        $mensajeSigi = null;

        if (
            $resultadoEvento['evento'] &&
            ($decisionSigi['debe_intervenir'] ?? false)
        ) {

            /*
            |--------------------------------------------------------------------------
            | Construir respuesta
            |--------------------------------------------------------------------------
            */

            $respuesta =
               $this->sigiIntervencionService
                    ->construirRespuesta(
                        $resultadoEvento['evento'],
                        $analisisSigi
                    );

            /*
            |--------------------------------------------------------------------------
            | Crear mensaje de SIGI
            |--------------------------------------------------------------------------
            */

            $mensajeSigi =
                ChatMessage::create([
                    'conversation_id' =>
                        $chat->id,

                    'user_id' =>
                        null,

                    'tipo' =>
                        'sigi',

                    'mensaje' =>
                        $respuesta['texto'],

                    'editado' =>
                        false,
                ]);

            /*
            |--------------------------------------------------------------------------
            | Registrar intervención
            |--------------------------------------------------------------------------
            */

            $this->sigiIntervencionService
                ->registrarIntervencion(
                    $resultadoEvento['evento']
                );
        }

        return [
            'analisis' =>
                $analisisSigi,

            'evento' =>
                $resultadoEvento,

            'decision' =>
                $decisionSigi,

            'mensaje_sigi' =>
                $mensajeSigi,
        ];
    }
}