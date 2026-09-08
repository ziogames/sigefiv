<?php

namespace App\Services\ConsultaInteligente;

class ConsultaInteligenteFechaService
{
    public function detectarFecha(string $texto): array
    {
        /*
        |--------------------------------------------------------------------------
        | NORMALIZACIÓN
        |--------------------------------------------------------------------------
        */

        $texto = mb_strtolower(trim($texto), 'UTF-8');

        /*
        |--------------------------------------------------------------------------
        | MESES
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        | Los meses se buscan como palabras completas.
        |
        | Esto evita errores como:
        |
        | "mayores" -> NO debe detectar "mayo"
        | "mayor"   -> NO debe detectar "mayo"
        |
        */

        $meses = [
            'enero'      => 1,
            'febrero'    => 2,
            'marzo'      => 3,
            'abril'      => 4,
            'mayo'       => 5,
            'junio'      => 6,
            'julio'      => 7,
            'agosto'     => 8,
            'septiembre' => 9,
            'setiembre'  => 9,
            'octubre'    => 10,
            'noviembre'  => 11,
            'diciembre'  => 12,
        ];

        $mes = null;
        $mesDesde = null;
        $mesHasta = null;

        /*
        |--------------------------------------------------------------------------
        | SEMESTRES
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'primer semestre') ||
            str_contains($texto, '1er semestre') ||
            str_contains($texto, '1 semestre')
        ) {
            $mesDesde = 1;
            $mesHasta = 6;
        }

        elseif (
            str_contains($texto, 'segundo semestre') ||
            str_contains($texto, '2do semestre') ||
            str_contains($texto, '2 semestre')
        ) {
            $mesDesde = 7;
            $mesHasta = 12;
        }

        /*
        |--------------------------------------------------------------------------
        | TRIMESTRES
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains($texto, 'primer trimestre') ||
            str_contains($texto, '1er trimestre') ||
            str_contains($texto, '1 trimestre')
        ) {
            $mesDesde = 1;
            $mesHasta = 3;
        }

        elseif (
            str_contains($texto, 'segundo trimestre') ||
            str_contains($texto, '2do trimestre') ||
            str_contains($texto, '2 trimestre')
        ) {
            $mesDesde = 4;
            $mesHasta = 6;
        }

        elseif (
            str_contains($texto, 'tercer trimestre') ||
            str_contains($texto, '3er trimestre') ||
            str_contains($texto, '3 trimestre')
        ) {
            $mesDesde = 7;
            $mesHasta = 9;
        }

        elseif (
            str_contains($texto, 'cuarto trimestre') ||
            str_contains($texto, '4to trimestre') ||
            str_contains($texto, '4 trimestre')
        ) {
            $mesDesde = 10;
            $mesHasta = 12;
        }

        /*
        |--------------------------------------------------------------------------
        | RANGO NATURAL
        |--------------------------------------------------------------------------
        */

        else {
            $mesEncontrados = [];

            foreach ($meses as $nombre => $numero) {

                /*
                |--------------------------------------------------------------------------
                | IMPORTANTE:
                | Usamos límites de palabra para que:
                |
                | "mayo"     -> detecte mayo
                | "mayores"  -> NO detecte mayo
                | "mayor"    -> NO detecte mayo
                |
                */

                $patron = '/(?<![\p{L}\p{N}_])'
                    . preg_quote($nombre, '/')
                    . '(?![\p{L}\p{N}_])/iu';

                if (preg_match($patron, $texto, $coincidencia, PREG_OFFSET_CAPTURE)) {

                    /*
                    |--------------------------------------------------------------------------
                    | PREG_OFFSET_CAPTURE devuelve el offset en bytes.
                    | Lo convertimos a posición aproximada para ordenar
                    | correctamente los meses encontrados.
                    |--------------------------------------------------------------------------
                    */

                    $posicion = $coincidencia[0][1];

                    $mesEncontrados[] = [
                        'mes' => $numero,
                        'posicion' => $posicion,
                    ];
                }
            }

            /*
            |--------------------------------------------------------------------------
            | ORDENAR POR POSICIÓN EN EL TEXTO
            |--------------------------------------------------------------------------
            */

            usort(
                $mesEncontrados,
                function ($a, $b) {
                    return $a['posicion'] <=> $b['posicion'];
                }
            );

            /*
            |--------------------------------------------------------------------------
            | RANGO ENTRE DOS MESES
            |--------------------------------------------------------------------------
            */

            if (count($mesEncontrados) >= 2) {

                $mesPrimero = $mesEncontrados[0]['mes'];
                $mesSegundo = $mesEncontrados[1]['mes'];

                $esRango =
                    str_contains($texto, ' a ') ||
                    str_contains($texto, ' hasta ') ||
                    str_contains($texto, ' entre ') ||
                    str_contains($texto, ' desde ') ||
                    str_contains($texto, ' al ');

                if ($esRango) {

                    $mesDesde = min(
                        $mesPrimero,
                        $mesSegundo
                    );

                    $mesHasta = max(
                        $mesPrimero,
                        $mesSegundo
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | UN SOLO MES
            |--------------------------------------------------------------------------
            */

            if (
                $mesDesde === null &&
                !empty($mesEncontrados)
            ) {
                $mes = $mesEncontrados[0]['mes'];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AÑO
        |--------------------------------------------------------------------------
        */

        $anio = null;
        $anioExplicito = false;

        if (
            preg_match(
                '/\b(20\d{2})\b/u',
                $texto,
                $coincidencia
            )
        ) {
            $anio = (int) $coincidencia[1];
            $anioExplicito = true;
        }

        /*
        |--------------------------------------------------------------------------
        | ESTE MES
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'este mes')
        ) {
            $fechaActual = now();

            $mes = $fechaActual->month;
            $anio = $fechaActual->year;
        }

        /*
        |--------------------------------------------------------------------------
        | MES PASADO
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains($texto, 'mes pasado') ||
            str_contains($texto, 'el mes pasado')
        ) {
            $fechaAnterior = now()->subMonth();

            $mes = $fechaAnterior->month;
            $anio = $fechaAnterior->year;
        }

        /*
        |--------------------------------------------------------------------------
        | ESTE AÑO
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains($texto, 'este año') ||
            str_contains($texto, 'este ano')
        ) {
            $anio = now()->year;
        }

        /*
        |--------------------------------------------------------------------------
        | AÑO ACTUAL POR DEFECTO
        |--------------------------------------------------------------------------
        |
        | Si el usuario indicó un mes pero no indicó año,
        | usamos el año actual.
        |
        */

        elseif (
            !$anioExplicito &&
            (
                $mes !== null ||
                $mesDesde !== null ||
                $mesHasta !== null
            )
        ) {
            $anio = now()->year;
        }

        /*
        |--------------------------------------------------------------------------
        | RESULTADO
        |--------------------------------------------------------------------------
        */

        return [
            'mes' => $mes,
            'mes_desde' => $mesDesde,
            'mes_hasta' => $mesHasta,
            'anio' => $anio,
        ];
    }
}