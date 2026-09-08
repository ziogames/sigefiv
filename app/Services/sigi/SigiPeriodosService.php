<?php                                                                                                               

namespace App\Services\Sigi;

use App\Models\ZoePeriodoFinanciero;

class SigiPeriodosService
{
    public function consultarPeriodos(
    array $interpretacion,
    string $operacion
): array {

    /*
    |--------------------------------------------------------------------------
    | CONSULTA BASE
    |--------------------------------------------------------------------------
    */

    $consulta =
        ZoePeriodoFinanciero::query();


    /*
    |--------------------------------------------------------------------------
    | FECHA
    |--------------------------------------------------------------------------
    */

    $fecha =
        $interpretacion['fecha']
        ?? [];


    $mes =
        $fecha['mes']
        ?? null;


    $anio =
        $fecha['anio']
        ?? null;


    /*
    |--------------------------------------------------------------------------
    | TEXTO ORIGINAL
    |--------------------------------------------------------------------------
    */

    $texto =
        mb_strtolower(
            $interpretacion['texto']
            ?? ''
        );


    /*
    |--------------------------------------------------------------------------
    | DETECTAR MÚLTIPLES MESES
    |--------------------------------------------------------------------------
    |
    | Ejemplos:
    |
    | enero y febrero
    | enero, febrero y marzo
    | enero febrero marzo
    |
    |--------------------------------------------------------------------------
    */

    $nombreMeses = [

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


    $mesesDetectados = [];


    foreach (
        $nombreMeses
        as $nombreMes => $numeroMes
    ) {

        if (
            str_contains(
                $texto,
                $nombreMes
            )
        ) {

            $mesesDetectados[] =
                $numeroMes;

        }

    }


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR DUPLICADOS
    |--------------------------------------------------------------------------
    */

    $mesesDetectados =
        array_values(
            array_unique(
                $mesesDetectados
            )
        );


    /*
    |--------------------------------------------------------------------------
    | ORDENAR MESES
    |--------------------------------------------------------------------------
    */

    sort(
        $mesesDetectados
    );


    /*
    |--------------------------------------------------------------------------
    | SI HAY VARIOS MESES
    |--------------------------------------------------------------------------
    */

    $esMultiplesMeses =
        count(
            $mesesDetectados
        ) > 1;


    /*
    |--------------------------------------------------------------------------
    | SI EL TEXTO TIENE VARIOS MESES,
    | USAMOS ESOS MESES EN LUGAR DE $fecha['mes']
    |--------------------------------------------------------------------------
    */

    if ($esMultiplesMeses) {

        $mes =
            null;

    }


    /*
    |--------------------------------------------------------------------------
    | FILTRO DE AÑO
    |--------------------------------------------------------------------------
    */

    if ($anio) {

        $consulta->where(
            'anio',
            $anio
        );

    }


    /*
    |--------------------------------------------------------------------------
    | FILTRO DE MESES
    |--------------------------------------------------------------------------
    */

    if ($esMultiplesMeses) {

        $consulta->whereIn(
            'mes',
            $mesesDetectados
        );

    } elseif ($mes) {

        $consulta->where(
            'mes',
            $mes
        );

    }


    /*
    |--------------------------------------------------------------------------
    | SALDO EN CAJA
    |--------------------------------------------------------------------------
    */

    if (
        str_contains(
            $texto,
            'saldo final'
        ) ||
        str_contains(
            $texto,
            'saldo de cierre'
        ) ||
        str_contains(
            $texto,
            'cuánto tenemos en caja'
        ) ||
        str_contains(
            $texto,
            'cuanto tenemos en caja'
        ) ||
        str_contains(
            $texto,
            'cuánto hay en caja'
        ) ||
        str_contains(
            $texto,
            'cuanto hay en caja'
        ) ||
        str_contains(
            $texto,
            'cuánto tenemos disponible'
        ) ||
        str_contains(
            $texto,
            'cuanto tenemos disponible'
        ) ||
        str_contains(
            $texto,
            'cuánto hay disponible'
        ) ||
        str_contains(
            $texto,
            'cuanto hay disponible'
        ) ||
        str_contains(
            $texto,
            'cuánto dinero tenemos'
        ) ||
        str_contains(
            $texto,
            'cuanto dinero tenemos'
        ) ||
        str_contains(
            $texto,
            'saldo cierre'
        ) ||
        str_contains(
            $texto,
            'saldo en caja'
        ) ||
        str_contains(
            $texto,
            'saldo de caja'
        ) ||
        str_contains(
            $texto,
            'saldo caja'
        ) ||
        str_contains(
            $texto,
            'quedó en caja'
        ) ||
        str_contains(
            $texto,
            'quedo en caja'
        ) ||
        str_contains(
            $texto,
            'quedó disponible'
        ) ||
        str_contains(
            $texto,
            'quedo disponible'
        ) ||
        str_contains(
            $texto,
            'cuánto quedó'
        ) ||
        str_contains(
            $texto,
            'cuanto quedo'
        )
    ) {

        /*
        |--------------------------------------------------------------------------
        | SALDO FINAL SOLO TIENE SENTIDO PARA UN PERÍODO
        |--------------------------------------------------------------------------
        */

        $periodo =
            $consulta->first();


        if (!$periodo) {

            return [

                'success' => true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    0,

                'mensaje' =>
                    'No encontré el período solicitado.',

            ];

        }


        $resultado =
            (float)
            $periodo->saldo_final;


        return [

            'success' => true,

            'tipo' =>
                'numero',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'El saldo en caja de ' .
                $periodo->nombre_completo .
                ' es de S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                '.',

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | TOTAL INGRESOS
    |--------------------------------------------------------------------------
    */

    if (
        str_contains(
            $texto,
            'total de ingresos'
        ) ||
        str_contains(
            $texto,
            'total ingresos'
        ) ||
        str_contains(
            $texto,
            'suma total de los ingresos'
        ) ||
        str_contains(
            $texto,
            'suma de los ingresos'
        ) ||
        str_contains(
            $texto,
            'ingresos de'
        ) ||
        str_contains(
            $texto,
            'ingresos del'
        ) ||
        str_contains(
            $texto,
            'ingreso'
        )
    ) {

        /*
        |--------------------------------------------------------------------------
        | UN SOLO PERÍODO — INGRESO
        |--------------------------------------------------------------------------
        |
        | Para preguntas conversacionales como "¿y cuál es el ingreso?"
        | devolvemos únicamente el importe, no la fila completa del período.
        */

        if (!$esMultiplesMeses) {

            $periodo =
                $consulta->first();

            if (!$periodo) {

                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => 0,
                    'mensaje' =>
                        'No encontré el período solicitado.',
                ];

            }

            $resultado =
                (float)
                $periodo->total_ingresos;

            return [
                'success' => true,
                'tipo' => 'numero',
                'resultado' => $resultado,
                'mensaje' =>
                    'Los ingresos de ' .
                    $periodo->nombre_completo .
                    ' fueron de S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    '.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | VARIOS MESES
        |--------------------------------------------------------------------------
        */

        if ($esMultiplesMeses) {

            $periodos =
                $consulta
                    ->orderBy('mes')
                    ->get();


            if (
                $periodos->isEmpty()
            ) {

                return [

                    'success' => true,

                    'tipo' =>
                        'numero',

                    'resultado' =>
                        0,

                    'mensaje' =>
                        'No encontré períodos para los meses solicitados.',

                ];

            }


            /*
            |--------------------------------------------------------------------------
            | SUMA REAL DE LOS INGRESOS
            |--------------------------------------------------------------------------
            */

            $resultado =
                (float)
                $periodos->sum(
                    'total_ingresos'
                );


            /*
            |--------------------------------------------------------------------------
            | DETALLE DE CADA MES
            |--------------------------------------------------------------------------
            */

            $detalle = [];


            foreach (
                $periodos
                as $periodo
            ) {

                $detalle[] =
                    $periodo->nombre .
                    ' ' .
                    $periodo->anio .
                    ': S/ ' .
                    number_format(
                        (float)
                        $periodo->total_ingresos,
                        2,
                        '.',
                        ','
                    );

            }


            /*
            |--------------------------------------------------------------------------
            | TEXTO DE MESES
            |--------------------------------------------------------------------------
            */

            $mesesTexto = [];


            foreach (
                $periodos
                as $periodo
            ) {

                $mesesTexto[] =
                    strtolower(
                        $periodo->nombre
                    );

            }


            $mesesTexto =
                implode(
                    ' + ',
                    $mesesTexto
                );


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'La suma de los ingresos de ' .
                    $mesesTexto .
                    ' de ' .
                    $anio .
                    ' es de S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    '.',

                'detalle' =>
                    $detalle,

            ];

        }


        /*
        |--------------------------------------------------------------------------
        | UN SOLO PERÍODO
        |--------------------------------------------------------------------------
        */

        $periodo =
            $consulta->first();


        if (!$periodo) {

            return [

                'success' => true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    0,

                'mensaje' =>
                    'No encontré el período solicitado.',

            ];

        }


        $resultado =
            (float)
            $periodo->total_ingresos;


        return [

            'success' =>
                true,

            'tipo' =>
                'numero',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'El total de ingresos del período ' .
                $periodo->nombre_completo .
                ' es de S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                '.',

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | TOTAL EGRESOS
    |--------------------------------------------------------------------------
    */

    if (
        (
            str_contains(
                $texto,
                'total de egresos'
            ) ||
            str_contains(
                $texto,
                'total egresos'
            ) ||
            str_contains(
                $texto,
                'suma total de los egresos'
            ) ||
            str_contains(
                $texto,
                'suma de los egresos'
            ) ||
            str_contains(
                $texto,
                'egresos de'
            ) ||
            str_contains(
                $texto,
                'egresos del'
            ) ||
            str_contains(
                $texto,
                'egreso'
            ) ||
            str_contains(
                $texto,
                'gasto'
            ) ||
            str_contains(
                $texto,
                'gastos'
            )
        ) &&
        (
            str_contains(
                $texto,
                'periodo'
            ) ||
            str_contains(
                $texto,
                'período'
            )
        )
    ) {

        /*
        |--------------------------------------------------------------------------
        | UN SOLO PERÍODO — EGRESO
        |--------------------------------------------------------------------------
        */

        if (!$esMultiplesMeses) {

            $periodo =
                $consulta->first();

            if (!$periodo) {

                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => 0,
                    'mensaje' =>
                        'No encontré el período solicitado.',
                ];

            }

            $resultado =
                (float)
                $periodo->total_egresos;

            return [
                'success' => true,
                'tipo' => 'numero',
                'resultado' => $resultado,
                'mensaje' =>
                    'Los egresos de ' .
                    $periodo->nombre_completo .
                    ' fueron de S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    '.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | VARIOS MESES
        |--------------------------------------------------------------------------
        */

        if ($esMultiplesMeses) {

            $periodos =
                $consulta
                    ->orderBy('mes')
                    ->get();


            if (
                $periodos->isEmpty()
            ) {

                return [

                    'success' => true,

                    'tipo' =>
                        'numero',

                    'resultado' =>
                        0,

                    'mensaje' =>
                        'No encontré períodos para los meses solicitados.',

                ];

            }


            /*
            |--------------------------------------------------------------------------
            | SUMA REAL DE LOS EGRESOS
            |--------------------------------------------------------------------------
            */

            $resultado =
                (float)
                $periodos->sum(
                    'total_egresos'
                );


            /*
            |--------------------------------------------------------------------------
            | DETALLE
            |--------------------------------------------------------------------------
            */

            $detalle = [];


            foreach (
                $periodos
                as $periodo
            ) {

                $detalle[] =
                    $periodo->nombre .
                    ' ' .
                    $periodo->anio .
                    ': S/ ' .
                    number_format(
                        (float)
                        $periodo->total_egresos,
                        2,
                        '.',
                        ','
                    );

            }


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'La suma de los egresos de los meses solicitados es de S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    '.',

                'detalle' =>
                    $detalle,

            ];

        }


        /*
        |--------------------------------------------------------------------------
        | UN SOLO PERÍODO
        |--------------------------------------------------------------------------
        */

        $periodo =
            $consulta->first();


        if (!$periodo) {

            return [

                'success' => true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    0,

                'mensaje' =>
                    'No encontré el período solicitado.',

            ];

        }


        $resultado =
            (float)
            $periodo->total_egresos;


        return [

            'success' =>
                true,

            'tipo' =>
                'numero',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'El total de egresos del período ' .
                $periodo->nombre_completo .
                ' es de S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                '.',

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | PERÍODO ACTUAL
    |--------------------------------------------------------------------------
    |
    | Cuando el usuario pregunta por "el período actual", SIGI debe
    | devolver únicamente el período vigente, no el listado completo.
    |
    */

    if (
        str_contains($texto, 'periodo actual') ||
        str_contains($texto, 'período actual') ||
        str_contains($texto, 'periodo vigente') ||
        str_contains($texto, 'período vigente')
    ) {

        $periodoActual =
            ZoePeriodoFinanciero::query()
                ->orderByDesc('anio')
                ->orderByDesc('mes')
                ->first();

        if (!$periodoActual) {

            return [
                'success' => true,
                'tipo' => 'lista',
                'resultado' => [],
                'mensaje' => 'No encontré un período actual.',
            ];

        }

        return [
            'success' => true,
            'tipo' => 'lista',
            'resultado' => collect([$periodoActual]),
            'mensaje' => 'El período actual es ' .
                $periodoActual->nombre_completo .
                '.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | LISTADO GENERAL
    |--------------------------------------------------------------------------
    */

    $resultado =
        $consulta
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->limit(20)
            ->get();


    return [

        'success' =>
            true,

        'tipo' =>
            'lista',

        'resultado' =>
            $resultado,

        'mensaje' =>
            'Encontré ' .
            $resultado->count() .
            ' períodos.',

    ];

}
}