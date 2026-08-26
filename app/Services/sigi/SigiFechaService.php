<?php

namespace App\Services\Sigi;

use Carbon\Carbon;

class SigiFechaService
{
    /**
     * Detecta la información temporal contenida en una consulta.
     *
     * El año actual se utiliza automáticamente cuando el usuario
     * no especifica otro año.
     */
    public function detectar(string $texto): array
    {
        $texto =
            $this->normalizar($texto);

        $meses = $this->meses();

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

        } elseif (
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

        } elseif (
            str_contains($texto, 'segundo trimestre') ||
            str_contains($texto, '2do trimestre') ||
            str_contains($texto, '2 trimestre')
        ) {

            $mesDesde = 4;

            $mesHasta = 6;

        } elseif (
            str_contains($texto, 'tercer trimestre') ||
            str_contains($texto, '3er trimestre') ||
            str_contains($texto, '3 trimestre')
        ) {

            $mesDesde = 7;

            $mesHasta = 9;

        } elseif (
            str_contains($texto, 'cuarto trimestre') ||
            str_contains($texto, '4to trimestre') ||
            str_contains($texto, '4 trimestre')
        ) {

            $mesDesde = 10;

            $mesHasta = 12;

        } else {

            /*
            |--------------------------------------------------------------------------
            | MESES
            |--------------------------------------------------------------------------
            */

            $mesEncontrados = [];

            foreach (
                $meses as $nombre => $numero
            ) {

                $posicion =
                    mb_strpos(
                        $texto,
                        $nombre
                    );

                if (
                    $posicion !== false
                ) {

                    $mesEncontrados[] = [
                        'mes' =>
                            $numero,

                        'posicion' =>
                            $posicion,
                    ];
                }
            }

            usort(
                $mesEncontrados,
                function (
                    array $a,
                    array $b
                ) {

                    return
                        $a['posicion']
                        <=>
                        $b['posicion'];
                }
            );

            /*
            |--------------------------------------------------------------------------
            | RANGO DE MESES
            |--------------------------------------------------------------------------
            */

            if (
                count($mesEncontrados) >= 2
            ) {

                $mesPrimero =
                    $mesEncontrados[0]['mes'];

                $mesSegundo =
                    $mesEncontrados[1]['mes'];

                $esRango =
                    str_contains(
                        $texto,
                        ' a '
                    ) ||
                    str_contains(
                        $texto,
                        ' hasta '
                    ) ||
                    str_contains(
                        $texto,
                        ' entre '
                    ) ||
                    str_contains(
                        $texto,
                        ' desde '
                    ) ||
                    str_contains(
                        $texto,
                        ' al '
                    );

                if ($esRango) {

                    $mesDesde =
                        min(
                            $mesPrimero,
                            $mesSegundo
                        );

                    $mesHasta =
                        max(
                            $mesPrimero,
                            $mesSegundo
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | MES ÚNICO
            |--------------------------------------------------------------------------
            */

            if (
                $mesDesde === null &&
                !empty($mesEncontrados)
            ) {

                $mes =
                    $mesEncontrados[0]['mes'];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | AÑO EXPLÍCITO
        |--------------------------------------------------------------------------
        */

        $anio = null;

        $anioExplicito = false;

        if (
            preg_match(
                '/\b(20\d{2})\b/',
                $texto,
                $coincidencia
            )
        ) {

            $anio =
                (int) $coincidencia[1];

            $anioExplicito = true;
        }


        /*
        |--------------------------------------------------------------------------
        | ESTE MES
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'este mes'
            )
        ) {

            $fechaActual =
                Carbon::now();

            $mes =
                $fechaActual->month;

            $anio =
                $fechaActual->year;

        }


        /*
        |--------------------------------------------------------------------------
        | MES PASADO
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains(
                $texto,
                'mes pasado'
            ) ||
            str_contains(
                $texto,
                'el mes pasado'
            )
        ) {

            $fechaAnterior =
                Carbon::now()->subMonth();

            $mes =
                $fechaAnterior->month;

            $anio =
                $fechaAnterior->year;

        }


        /*
        |--------------------------------------------------------------------------
        | ESTE AÑO
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains(
                $texto,
                'este año'
            ) ||
            str_contains(
                $texto,
                'este ano'
            )
        ) {

            $anio =
                Carbon::now()->year;
        }


        /*
        |--------------------------------------------------------------------------
        | AÑO ACTUAL POR DEFECTO
        |--------------------------------------------------------------------------
        |
        | Si existe un mes o rango y el usuario no escribió
        | explícitamente un año, utilizamos el año actual.
        |--------------------------------------------------------------------------
        */

        elseif (
            !$anioExplicito &&
            (
                $mes !== null ||
                $mesDesde !== null ||
                $mesHasta !== null
            )
        ) {

            $anio =
                Carbon::now()->year;
        }


        return [

            'mes' =>
                $mes,

            'mes_desde' =>
                $mesDesde,

            'mes_hasta' =>
                $mesHasta,

            'anio' =>
                $anio,
        ];
    }


    /**
     * Devuelve los nombres de los meses.
     */
    public function meses(): array
    {
        return [

            'enero' =>
                1,

            'febrero' =>
                2,

            'marzo' =>
                3,

            'abril' =>
                4,

            'mayo' =>
                5,

            'junio' =>
                6,

            'julio' =>
                7,

            'agosto' =>
                8,

            'septiembre' =>
                9,

            'setiembre' =>
                9,

            'octubre' =>
                10,

            'noviembre' =>
                11,

            'diciembre' =>
                12,
        ];
    }


    /**
     * Devuelve el año actual.
     */
    public function anioActual(): int
    {
        return
            Carbon::now()->year;
    }


    /**
     * Devuelve el mes actual.
     */
    public function mesActual(): int
    {
        return
            Carbon::now()->month;
    }


    /**
     * Devuelve la fecha actual.
     */
    public function ahora(): Carbon
    {
        return
            Carbon::now();
    }


    /**
     * Normaliza el texto antes de analizarlo.
     */
    private function normalizar(
        string $texto
    ): string {

        $texto =
            mb_strtolower(
                trim($texto),
                'UTF-8'
            );

        /*
         * Normalizamos algunas variantes de acentuación
         * para que las expresiones temporales puedan
         * detectarse de manera consistente.
         */

        $texto =
            str_replace(
                [
                    'á',
                    'é',
                    'í',
                    'ó',
                    'ú',
                    'ü',
                ],
                [
                    'a',
                    'e',
                    'i',
                    'o',
                    'u',
                    'u',
                ],
                $texto
            );

        /*
         * Compactamos espacios.
         */

        $texto =
            preg_replace(
                '/\s+/u',
                ' ',
                $texto
            );

        return
            trim($texto);
    }
}