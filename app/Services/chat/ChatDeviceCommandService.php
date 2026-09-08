<?php

namespace App\Services\chat;

class ChatDeviceCommandService
{
    /**
     * Detecta órdenes relacionadas con las luces
     * de la loza deportiva.
     *
     * Este servicio únicamente interpreta la orden.
     * No ejecuta ninguna acción física.
     */
    public function detectarOrden(string $texto): array
    {
        $texto = $this->normalizarTexto($texto);

        /*
        |--------------------------------------------------------------------------
        | Verificar que realmente se esté hablando de las luces
        | de la loza deportiva.
        |--------------------------------------------------------------------------
        |
        | Esto evita que consultas financieras como:
        | "cuanto gastamos en luz"
        | sean interpretadas como órdenes de dispositivos.
        |
        */
        if (!$this->esOrdenDeLucesDeLaLoza($texto)) {
            return [
                'es_orden' => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Detectar acción
        |--------------------------------------------------------------------------
        */

        $accion = null;

        if (
            preg_match(
                '/\b(encender|enciende|enciendan|prender|prende|prendan)\b/u',
                $texto
            )
        ) {
            $accion = 'encender';
        }

        if (
            preg_match(
                '/\b(apagar|apaga|apaguen)\b/u',
                $texto
            )
        ) {
            $accion = 'apagar';
        }

        /*
        |--------------------------------------------------------------------------
        | Si no encontramos una acción, no es una orden válida.
        |--------------------------------------------------------------------------
        */

        if ($accion === null) {
            return [
                'es_orden' => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Detectar duración
        |--------------------------------------------------------------------------
        |
        | Ejemplos:
        |
        | por 5 minutos
        | por 10 minutos
        | por 16 minutos
        | por 20 minutos
        | por 45 minutos
        | por 60 minutos
        | por 90 minutos
        | por 120 minutos
        |
        | por 1 hora
        | por 2 horas
        | por 3 horas
        |
        | por 1 hora y 30 minutos
        |
        */

        $duracion = $this->extraerDuracion($texto);

        /*
        |--------------------------------------------------------------------------
        | Resultado estructurado
        |--------------------------------------------------------------------------
        */

        return [
            'es_orden' => true,
            'dispositivo' => 'luces_loza_deportiva',
            'accion' => $accion,
            'duracion_minutos' => $duracion,
        ];
    }

    /**
     * Determina si el texto habla específicamente
     * de las luces de la loza deportiva.
     */
    private function esOrdenDeLucesDeLaLoza(string $texto): bool
    {
        $tieneLuces =
            str_contains($texto, 'luces') ||
            str_contains($texto, 'luz') ||
            str_contains($texto, 'reflectores') ||
            str_contains($texto, 'reflector');

        $tieneLoza =
            str_contains($texto, 'loza deportiva') ||
            str_contains($texto, 'loza') ||
            str_contains($texto, 'cancha deportiva');

        return $tieneLuces && $tieneLoza;
    }

    /**
     * Extrae la duración indicada por el usuario
     * y la convierte siempre a minutos.
     */
    private function extraerDuracion(string $texto): ?int
    {
        /*
        |--------------------------------------------------------------------------
        | HORAS + MINUTOS
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        | "por 1 hora y 30 minutos"
        |
        */

        if (
            preg_match(
                '/\b(?:por|durante)\s+(\d+(?:[.,]\d+)?)\s*horas?\s*(?:y\s*)?(\d+)\s*minutos?\b/u',
                $texto,
                $coincidencias
            )
        ) {
            $horas = (float) str_replace(
                ',',
                '.',
                $coincidencias[1]
            );

            $minutos = (int) $coincidencias[2];

            return (int) round(
                ($horas * 60) + $minutos
            );
        }

        /*
        |--------------------------------------------------------------------------
        | HORAS
        |--------------------------------------------------------------------------
        |
        | Ejemplos:
        | "por 1 hora"
        | "por 2 horas"
        | "por 3 horas"
        | "durante 2 horas"
        |
        */

        if (
            preg_match(
                '/\b(?:por|durante)\s+(\d+(?:[.,]\d+)?)\s*horas?\b/u',
                $texto,
                $coincidencias
            )
        ) {
            $horas = (float) str_replace(
                ',',
                '.',
                $coincidencias[1]
            );

            return (int) round(
                $horas * 60
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MINUTOS
        |--------------------------------------------------------------------------
        |
        | No existe un límite fijo.
        |
        | Puede ser:
        | 5, 10, 16, 20, 45, 60, 75, 90, 120...
        |
        */

        if (
            preg_match(
                '/\b(?:por|durante)\s+(\d+)\s*minutos?\b/u',
                $texto,
                $coincidencias
            )
        ) {
            return (int) $coincidencias[1];
        }

        /*
        |--------------------------------------------------------------------------
        | Si no se indicó duración
        |--------------------------------------------------------------------------
        |
        | ZOE podrá preguntar posteriormente:
        | "¿Por cuánto tiempo deseas mantener encendidas
        | las luces de la loza deportiva?"
        |
        */

        return null;
    }

    /**
     * Normaliza el texto para facilitar la detección.
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(
            trim($texto),
            'UTF-8'
        );

        /*
        | Quitar acentos para que la detección sea más tolerante.
        */
        $texto = strtr(
            $texto,
            [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'ü' => 'u',
                'ñ' => 'n',
            ]
        );

        /*
        | Normalizar espacios.
        */
        $texto = preg_replace(
            '/\s+/u',
            ' ',
            $texto
        );

        return trim($texto);
    }
}