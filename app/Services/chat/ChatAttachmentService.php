<?php

namespace App\Services\chat;

use Illuminate\Http\UploadedFile;

class ChatAttachmentService
{
    /**
     * Procesa y almacena un archivo adjunto del Chat Vecinal.
     *
     * Devuelve los metadatos que se guardan en el campo
     * "mensaje" de ChatMessage.
     */
    public function procesar(
        UploadedFile $archivo,
        string $texto = ''
    ): string {

        /*
        |--------------------------------------------------------------------------
        | Almacenar archivo
        |--------------------------------------------------------------------------
        */

        $ruta =
            $archivo->store(
                'chat',
                'public'
            );

        /*
        |--------------------------------------------------------------------------
        | Construir metadatos
        |--------------------------------------------------------------------------
        */

        return json_encode(
            [
                'tipo' =>
                    'archivo',

                'nombre' =>
                    $archivo
                        ->getClientOriginalName(),

                'mime' =>
                    $archivo
                        ->getMimeType(),

                'tamano' =>
                    $archivo
                        ->getSize(),

                'ruta' =>
                    $ruta,

                'url' =>
                    asset(
                        'storage/' . $ruta
                    ),

                'texto' =>
                    $texto,
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
    }
}
