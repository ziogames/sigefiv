<?php

namespace App\Services\Sigi;

use App\Services\SigiAiService;

class SigiChistesService
{
    private SigiAiService $sigiAi;

    public function __construct(
        SigiAiService $sigiAi
    ) {
        $this->sigiAi = $sigiAi;
    }

    public function obtener(): array
    {
        $respuesta =
    $this->sigiAi->responder(
                'Cuéntame un chiste corto y divertido en español.',
                'Eres Sigi. Cuenta un chiste breve, familiar y apropiado '
                . 'para todo público. No expliques el chiste. Devuelve solamente '
                . 'el chiste, con un tono alegre.'
            );


        if ($respuesta !== null) {

            return [

                'success' => true,

                'tipo' => 'texto',

                'resultado' => null,

                'mensaje' => '😂 ' . $respuesta,

            ];
        }


        return [

            'success' => false,

            'tipo' => 'texto',

            'resultado' => null,

            'mensaje' =>
              'No pude conectarme con SIGI en este momento.',

        ];
    }
}