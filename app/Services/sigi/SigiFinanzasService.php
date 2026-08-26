<?php

namespace App\Services\Sigi;

use App\Models\Movimiento;
use Illuminate\Support\Facades\DB;

class SigiFinanzasService
{

    public function consultarMovimientos(
        array $interpretacion,
        string $operacion
    ): array {

        $consulta = Movimiento::query()
            ->with('categoria');


        /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        */

        $fecha =
            $interpretacion['fecha'] ?? [];


        $mes =
            $fecha['mes'] ?? null;


        $meses =
            $fecha['meses'] ?? [];


        if (!is_array($meses)) {
            $meses = [];
        }


        $meses = array_values(
            array_unique(
                array_map(
                    'intval',
                    array_filter(
                        $meses,
                        fn ($valor) => (int) $valor >= 1 && (int) $valor <= 12
                    )
                )
            )
        );

        sort($meses);


        /*
        |--------------------------------------------------------------------------
        | RESPALDO: DETECTAR VARIOS MESES DESDE EL TEXTO ORIGINAL
        |--------------------------------------------------------------------------
        |
        | Esto permite que SIGI funcione aunque el intérprete todavía no
        | haya enviado la propiedad "meses".
        |
        */

        $textoFecha =
            mb_strtolower(
                $interpretacion['texto'] ?? ''
            );

        $nombresMesesConsulta = [
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

        if (count($meses) <= 1) {

            $mesesDetectados = [];

            foreach (
                $nombresMesesConsulta
                as $nombreMesConsulta => $numeroMesConsulta
            ) {

                if (
                    str_contains(
                        $textoFecha,
                        $nombreMesConsulta
                    )
                ) {

                    $mesesDetectados[] =
                        $numeroMesConsulta;

                }

            }

            if (count($mesesDetectados) > 1) {

                $meses =
                    array_values(
                        array_unique(
                            $mesesDetectados
                        )
                    );

                sort($meses);

            }

        }


        $mesDesde =
            $fecha['mes_desde'] ?? null;


        $mesHasta =
            $fecha['mes_hasta'] ?? null;


        $anio =
            $fecha['anio'] ?? null;

/*
|--------------------------------------------------------------------------
| RANGO DE MESES
|--------------------------------------------------------------------------
|
| El rango tiene PRIORIDAD sobre los meses individuales.
|
| Ejemplo:
|
| enero hasta mayo de 2025
|
| debe consultar:
|
| 2025-01-01
| hasta
| 2025-05-31
|
*/

if (
    $mesDesde !== null &&
    $mesHasta !== null &&
    $anio !== null
) {

    $fechaInicio =
        sprintf(
            '%04d-%02d-01',
            $anio,
            $mesDesde
        );


   $fechaFin =
    date(
        'Y-m-t',
        strtotime(
            sprintf(
                '%04d-%02d-01',
                $anio,
                $mesHasta
            )
        )
    );



    $consulta->whereBetween(
        'fecha',
        [
            $fechaInicio,
            $fechaFin,
        ]
    );

}


/*
|--------------------------------------------------------------------------
| MESES ESPECÍFICOS
|--------------------------------------------------------------------------
|
| Ejemplo:
|
| enero y febrero de 2025
|
| debe consultar:
|
| mes IN (1, 2)
|
*/

elseif (
    count($meses) > 1 &&
    $anio !== null
) {

    $consulta->whereYear(
        'fecha',
        $anio
    );


    $consulta->whereIn(
        \Illuminate\Support\Facades\DB::raw(
            'EXTRACT(MONTH FROM fecha)'
        ),
        $meses
    );

}


/*
|--------------------------------------------------------------------------
| MES INDIVIDUAL
|--------------------------------------------------------------------------
*/

elseif (
    $mes !== null &&
    $anio !== null
) {

    $consulta->whereYear(
        'fecha',
        $anio
    );


    $consulta->whereMonth(
        'fecha',
        $mes
    );

}


/*
|--------------------------------------------------------------------------
| MES SIN AÑO
|--------------------------------------------------------------------------
*/

elseif ($mes !== null) {

    $consulta->whereMonth(
        'fecha',
        $mes
    );

}
        /*
        |--------------------------------------------------------------------------
        | MES INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        elseif (
            $mes !== null &&
            $anio !== null
        ) {

            $consulta->whereYear(
                'fecha',
                $anio
            );


            $consulta->whereMonth(
                'fecha',
                $mes
            );

        }


        /*
        |--------------------------------------------------------------------------
        | MES SIN AÑO
        |--------------------------------------------------------------------------
        */

        elseif ($mes !== null) {

            $consulta->whereMonth(
                'fecha',
                $mes
            );

        }


        /*
        |--------------------------------------------------------------------------
        | AÑO SIN MES
        |--------------------------------------------------------------------------
        */

        elseif ($anio !== null) {

            $consulta->whereYear(
                'fecha',
                $anio
            );

        }


        /*
        |--------------------------------------------------------------------------
        | TEXTO ORIGINAL
        |--------------------------------------------------------------------------
        */

        $texto =
            mb_strtolower(
                $interpretacion['texto'] ?? ''
            );


     /*
|--------------------------------------------------------------------------
| TIPO DE MOVIMIENTO
|--------------------------------------------------------------------------
|
| El intérprete ya determinó si la consulta corresponde
| a un ingreso o a un egreso.
|
*/

$tipoMovimiento =
    $interpretacion['tipo_movimiento'] ?? null;


if (
    $tipoMovimiento === 'Ingreso' ||
    $tipoMovimiento === 'Egreso'
) {

    $consulta->where(
        'tipo',
        $tipoMovimiento
    );

}

        


        /*
        |--------------------------------------------------------------------------
        | CATEGORÍA
        |--------------------------------------------------------------------------
        */

        $categoria =
            $interpretacion['categoria'] ?? null;


        if (
            $categoria &&
            isset($categoria['id'])
        ) {

            $consulta->where(
                'categoria_id',
                $categoria['id']
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CONCEPTO
        |--------------------------------------------------------------------------
        */

        $concepto =
            $interpretacion['concepto'] ?? null;


        if ($concepto) {

            $consulta->where(
                'concepto',
                'like',
                '%' . $concepto . '%'
            );

        }

        /*
|--------------------------------------------------------------------------
| MES CON MAYORES EGRESOS
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MESES DE UN CONCEPTO
|--------------------------------------------------------------------------
*/

if ($operacion === 'meses_concepto') {

    $resultados =
        $consulta
            ->selectRaw(
                'strftime("%Y", fecha) as anio,
                 strftime("%m", fecha) as mes,
                 COUNT(*) as cantidad,
                 SUM(monto) as total'
            )
            ->groupByRaw(
                'strftime("%Y", fecha),
                 strftime("%m", fecha)'
            )
            ->orderByRaw(
                'strftime("%Y", fecha),
                 strftime("%m", fecha)'
            )
            ->get();


    if ($resultados->isEmpty()) {

        return [

            'success' => true,

            'tipo' => 'texto',

            'resultado' => [],

            'mensaje' =>
                'No encontré pagos que coincidan con el concepto consultado.',

        ];
    }


    $nombreMeses = [

        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
        '10' => 'Octubre',
        '11' => 'Noviembre',
        '12' => 'Diciembre',

    ];


    $conceptoTexto =
        $interpretacion['concepto']
        ?? 'concepto solicitado';


    $html =
        '<div class="consulta-meses-concepto">' .
        '<strong>Encontré pagos de ' .
        htmlspecialchars(
            $conceptoTexto,
            ENT_QUOTES,
            'UTF-8'
        ) .
        ' en:</strong><br><br>';


    $datos = [];


    foreach ($resultados as $fila) {

        $mesCodigo =
            str_pad(
                (string) $fila->mes,
                2,
                '0',
                STR_PAD_LEFT
            );


        $nombreMes =
            $nombreMeses[$mesCodigo]
            ?? 'Mes desconocido';


        $anioResultado =
            (int) $fila->anio;


        $cantidad =
            (int) $fila->cantidad;


        $total =
            (float) $fila->total;


        $datos[] = [

            'mes' =>
                $nombreMes,

            'anio' =>
                $anioResultado,

            'cantidad' =>
                $cantidad,

            'total' =>
                $total,

        ];


        $html .=
            '<div style="margin-bottom:8px;">' .
            '📅 <strong>' .
            $nombreMes .
            ' ' .
            $anioResultado .
            '</strong>' .
            ' — S/ ' .
            number_format(
                $total,
                2,
                '.',
                ','
            ) .
            ' (' .
            $cantidad .
            (
                $cantidad === 1
                    ? ' pago'
                    : ' pagos'
            ) .
            ')' .
            '</div>';

    }


    $html .=
        '</div>';


    return [

        'success' => true,

        'tipo' => 'texto',

        'resultado' =>
            $datos,

        'mensaje' =>
            $html,

    ];

}


if ($operacion === 'max_mes') {

    $anio =
        $interpretacion['fecha']['anio']
        ?? null;


    if (!$anio) {

        return [

            'success' => false,

            'tipo' => 'texto',

            'resultado' => null,

            'mensaje' =>
                'Necesito que indiques el año que deseas consultar.',

        ];

    }


    /*
    |--------------------------------------------------------------------------
    | Obtener egresos agrupados por mes
    |--------------------------------------------------------------------------
    */

    $resultados =
        Movimiento::query()
            ->where('tipo', 'Egreso')
            ->whereYear('fecha', $anio)
            ->selectRaw(
                'strftime("%m", fecha) as mes,
                 SUM(monto) as total'
            )
            ->groupByRaw(
                'strftime("%m", fecha)'
            )
            ->orderByDesc('total')
            ->first();


    /*
    |--------------------------------------------------------------------------
    | No hay resultados
    |--------------------------------------------------------------------------
    */

    if (!$resultados) {

        return [

            'success' => true,

            'tipo' => 'numero',

            'resultado' => 0,

            'mensaje' =>
                'No encontré egresos registrados durante ' .
                $anio .
                '.',

        ];

    }


    $mes =
        (int) $resultados->mes;


    $total =
        (float) $resultados->total;


    $nombreMeses = [

        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',

    ];


    $nombreMes =
        $nombreMeses[$mes]
        ?? 'mes desconocido';


    return [

        'success' => true,

        'tipo' => 'numero',

        'resultado' => $total,

        'mensaje' =>
            'El mes con mayores egresos en ' .
            $anio .
            ' fue ' .
            $nombreMes .
            ', con un total de S/ ' .
            number_format(
                $total,
                2,
                '.',
                ','
            ) .
            '.',

    ];
}
/*
|--------------------------------------------------------------------------
| MES CON MENORES EGRESOS
|--------------------------------------------------------------------------
*/

if ($operacion === 'min_mes') {

    $anio =
        $interpretacion['fecha']['anio']
        ?? null;


    if (!$anio) {

        return [

            'success' => false,

            'tipo' => 'texto',

            'resultado' => null,

            'mensaje' =>
                'Necesito que indiques el año que deseas consultar.',

        ];

    }


    $resultado =
        Movimiento::query()
            ->where('tipo', 'Egreso')
            ->whereYear('fecha', $anio)
            ->selectRaw(
                'strftime("%m", fecha) as mes,
                 SUM(monto) as total'
            )
            ->groupByRaw(
                'strftime("%m", fecha)'
            )
            ->orderBy('total')
            ->first();


    if (!$resultado) {

        return [

            'success' => true,

            'tipo' => 'numero',

            'resultado' => 0,

            'mensaje' =>
                'No encontré egresos registrados durante ' .
                $anio .
                '.',

        ];

    }


    $mes =
        (int) $resultado->mes;


    $total =
        (float) $resultado->total;


    $nombreMeses = [

        1 => 'enero',
        2 => 'febrero',
        3 => 'marzo',
        4 => 'abril',
        5 => 'mayo',
        6 => 'junio',
        7 => 'julio',
        8 => 'agosto',
        9 => 'septiembre',
        10 => 'octubre',
        11 => 'noviembre',
        12 => 'diciembre',

    ];


    $nombreMes =
        $nombreMeses[$mes]
        ?? 'mes desconocido';


    return [

        'success' => true,

        'tipo' => 'numero',

        'resultado' => $total,

        'mensaje' =>
            'El mes con menores egresos en ' .
            $anio .
            ' fue ' .
            $nombreMes .
            ', con un total de S/ ' .
            number_format(
                $total,
                2,
                '.',
                ','
            ) .
            '.',

    ];
}

        /*
        |--------------------------------------------------------------------------
        | SUMA
        |--------------------------------------------------------------------------
        */

      if ($operacion === 'sum') {

    \Log::info('SIGI FECHA DEBUG', [
        'fecha' => $fecha,
        'meses' => $meses,
        'mes' => $mes,
        'anio' => $anio,
        'texto' => $texto,
    ]);

    \Log::info('SIGI SQL DEBUG', [
        'sql' => $consulta->toSql(),
        'bindings' => $consulta->getBindings(),
    ]);

$tipoMovimiento =
    $interpretacion['tipo_movimiento'] ?? null;

$esIngreso =
    $tipoMovimiento === 'Ingreso';

$esEgreso =
    $tipoMovimiento === 'Egreso';

    $resultado =
        (float) $consulta->sum(
            'monto'
        );

            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    $this->generarMensajeSumaMovimiento(
                        $resultado,
                        $interpretacion,
                        $esIngreso,
                        $esEgreso
                    ),

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | CANTIDAD
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'count') {

            $resultado =
                $consulta->count();


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'Encontré ' .
                    $resultado .
                    ' movimientos que coinciden con tu consulta.',

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PROMEDIO
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'avg') {

            $resultado =
                (float) (
                    $consulta->avg('monto')
                    ?? 0
                );


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El promedio de los movimientos encontrados es S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) . '.',

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | MAYOR INGRESO
        |--------------------------------------------------------------------------

        |
        | Si la consulta tiene un mes específico:
        |   "mayor ingreso de enero de 2025"
        |
        | buscamos el movimiento individual de mayor monto.
        |
        | Si solo tiene el año:
        |   "qué mes tuvo mayores ingresos en 2025"
        |
        | comparamos el total acumulado de cada mes.
        |
        */

        if ($operacion === 'max_mes_ingreso') {

            if (
                $mes !== null &&
                $anio !== null
            ) {

                $movimiento =
                    Movimiento::query()
                        ->with('categoria')
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->whereMonth('fecha', $mes)
                        ->orderByDesc('monto')
                        ->orderBy('fecha')
                        ->first();

                if (!$movimiento) {

                    return [

                        'success' => true,

                        'tipo' => 'lista',

                        'resultado' => collect(),

                        'mensaje' =>
                            'No encontré ingresos en ' .
                            $this->nombreMes(
                                $mes
                            ) .
                            ' de ' .
                            $anio .
                            '.',

                    ];
                }

                $monto =
                    (float) $movimiento->monto;

                return [

                    'success' => true,

                    'tipo' => 'lista',

                    'resultado' =>
                        collect([
                            $movimiento
                        ]),

                    'mensaje' =>
                        'El mayor ingreso de ' .
                        $this->nombreMes(
                            $mes
                        ) .
                        ' de ' .
                        $anio .
                        ' fue de S/ ' .
                        number_format(
                            $monto,
                            2,
                            '.',
                            ','
                        ) .
                        '.',

                ];
            }


            if ($anio !== null) {

                $movimientos =
                    Movimiento::query()
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->get();

                if ($movimientos->isEmpty()) {

                    return [
                        'success' => true,
                        'tipo' => 'texto',
                        'resultado' => null,
                        'mensaje' =>
                            'No encontré ingresos registrados durante ' .
                            $anio .
                            '.',
                    ];
                }

                $totalesPorMes = [];

                foreach ($movimientos as $movimiento) {

                    $mesMovimiento =
                        (int) $movimiento->fecha->month;

                    if (!isset($totalesPorMes[$mesMovimiento])) {
                        $totalesPorMes[$mesMovimiento] = 0;
                    }

                    $totalesPorMes[$mesMovimiento] +=
                        (float) $movimiento->monto;
                }

                $mayor =
                    max($totalesPorMes);

                $meses = [];

                foreach ($totalesPorMes as $mesMovimiento => $total) {

                    if ((float) $total === (float) $mayor) {

                        $meses[] =
                            $this->nombreMes(
                                (int) $mesMovimiento
                            );
                    }
                }

                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => $mayor,
                    'mensaje' =>
                        'Los meses con mayores ingresos en ' .
                        $anio .
                        ' fueron ' .
                        implode(', ', $meses) .
                        ', con S/ ' .
                        number_format(
                            $mayor,
                            2,
                            '.',
                            ','
                        ) .
                        ' en cada mes.',
                ];
            }

        }


        /*
        |--------------------------------------------------------------------------
        | MENOR INGRESO
        |--------------------------------------------------------------------------
        |
        | Con mes específico: devuelve UN SOLO movimiento.
        |
        | Sin mes, pero con año: compara el total de ingresos de cada mes.
        |
        */

        if ($operacion === 'min_mes_ingreso') {

            if (
                $mes !== null &&
                $anio !== null
            ) {

                $movimiento =
                    Movimiento::query()
                        ->with('categoria')
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->whereMonth('fecha', $mes)
                        ->orderBy('monto')
                        ->orderBy('fecha')
                        ->first();

                if (!$movimiento) {

                    return [

                        'success' => true,

                        'tipo' => 'lista',

                        'resultado' => collect(),

                        'mensaje' =>
                            'No encontré ingresos en ' .
                            $this->nombreMes(
                                $mes
                            ) .
                            ' de ' .
                            $anio .
                            '.',

                    ];
                }

                $monto =
                    (float) $movimiento->monto;

                return [

                    'success' => true,

                    'tipo' => 'lista',

                    'resultado' =>
                        collect([
                            $movimiento
                        ]),

                    'mensaje' =>
                        'El menor ingreso de ' .
                        $this->nombreMes(
                            $mes
                        ) .
                        ' de ' .
                        $anio .
                        ' fue de S/ ' .
                        number_format(
                            $monto,
                            2,
                            '.',
                            ','
                        ) .
                        '.',

                ];
            }


            if ($anio !== null) {

                $movimientos =
                    Movimiento::query()
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->get();

                if ($movimientos->isEmpty()) {

                    return [
                        'success' => true,
                        'tipo' => 'texto',
                        'resultado' => null,
                        'mensaje' =>
                            'No encontré ingresos registrados durante ' .
                            $anio .
                            '.',
                    ];
                }

                $totalesPorMes = [];

                foreach ($movimientos as $movimiento) {

                    $mesMovimiento =
                        (int) $movimiento->fecha->month;

                    if (!isset($totalesPorMes[$mesMovimiento])) {
                        $totalesPorMes[$mesMovimiento] = 0;
                    }

                    $totalesPorMes[$mesMovimiento] +=
                        (float) $movimiento->monto;
                }

                $menor =
                    min($totalesPorMes);

                $meses = [];

                foreach ($totalesPorMes as $mesMovimiento => $total) {

                    if ((float) $total === (float) $menor) {

                        $meses[] =
                            $this->nombreMes(
                                (int) $mesMovimiento
                            );
                    }
                }

                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => $menor,
                    'mensaje' =>
                        'Los meses con menores ingresos en ' .
                        $anio .
                        ' fueron ' .
                        implode(', ', $meses) .
                        ', con S/ ' .
                        number_format(
                            $menor,
                            2,
                            '.',
                            ','
                        ) .
                        ' en cada mes.',
                ];
            }


                    }


        /*
        |--------------------------------------------------------------------------
        | MAYOR
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'max') {

            $movimiento =
                (clone $consulta)
                    ->with('categoria')
                    ->orderByDesc('monto')
                    ->orderBy('fecha')
                    ->first();

            if (!$movimiento) {

                return [

                    'success' => true,

                    'tipo' => 'lista',

                    'resultado' => collect(),

                    'mensaje' =>
                        'No encontré movimientos que coincidan con tu consulta.',

                ];
            }

            $resultado =
                (float) $movimiento->monto;

            return [

                'success' => true,

                'tipo' => 'lista',

                'resultado' =>
                    collect([
                        $movimiento
                    ]),

                'mensaje' =>
                    'El movimiento de mayor monto encontrado fue de S/ ' .
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
        | MENOR
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'min') {

            $movimiento =
                (clone $consulta)
                    ->with('categoria')
                    ->orderBy('monto')
                    ->orderBy('fecha')
                    ->first();

            if (!$movimiento) {

                return [

                    'success' => true,

                    'tipo' => 'lista',

                    'resultado' => collect(),

                    'mensaje' =>
                        'No encontré movimientos que coincidan con tu consulta.',

                ];
            }

            $resultado =
                (float) $movimiento->monto;

            return [

                'success' => true,

                'tipo' => 'lista',

                'resultado' =>
                    collect([
                        $movimiento
                    ]),

                'mensaje' =>
                    'El movimiento de menor monto encontrado fue de S/ ' .
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
        | LISTADO
        |--------------------------------------------------------------------------
        */

        $resultado =
            $consulta
                ->latest('fecha')
                ->limit(20)
                ->get();


        return [

            'success' => true,

            'tipo' => 'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'Encontré ' .
                $resultado->count() .
                ' movimientos que coinciden con tu consulta.',

        ];
    }

    private function generarMensajeSumaMovimiento(
        float $resultado,
        array $interpretacion,
        bool $esIngreso,
        bool $esEgreso
    ): string {

        $fecha =
            $interpretacion['fecha'] ?? [];


        $mes =
            $fecha['mes'] ?? null;


        $mesDesde =
            $fecha['mes_desde'] ?? null;


        $mesHasta =
            $fecha['mes_hasta'] ?? null;


        $meses =
            $fecha['meses'] ?? [];


        if (!is_array($meses)) {
            $meses = [];
        }


        $meses = array_values(
            array_unique(
                array_map(
                    'intval',
                    array_filter(
                        $meses,
                        fn ($valor) => (int) $valor >= 1 && (int) $valor <= 12
                    )
                )
            )
        );

        sort($meses);


        $anio =
            $fecha['anio'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | RESPALDO DEL AÑO
        |--------------------------------------------------------------------------
        |
        | Si el intérprete no envía el año dentro de fecha, lo recuperamos
        | desde el texto original de la consulta.
        |
        */

        if (!$anio) {

            $textoOriginal =
                $interpretacion['texto'] ?? '';

            if (
                preg_match(
                    '/\\b(20\\d{2})\\b/',
                    $textoOriginal,
                    $coincidencia
                )
            ) {

                $anio =
                    (int) $coincidencia[1];

            }

        }


        $nombreMeses = [

            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',

        ];


        /*
        |--------------------------------------------------------------------------
        | DESCRIPCIÓN
        |--------------------------------------------------------------------------
        */

        $categoria =
            $interpretacion['categoria']['nombre']
            ?? null;


        $concepto =
            $interpretacion['concepto']
            ?? null;


        $descripcion = '';


        if ($categoria) {

            $descripcion =
                ' por ' .
                mb_strtolower(
                    $categoria
                );

        }


        if ($concepto) {

            $descripcion .=
                ' de ' .
                $concepto;

        }


        /*
        |--------------------------------------------------------------------------
        | RANGO / TRIMESTRE
        |--------------------------------------------------------------------------
        */

        if (
            $mesDesde !== null &&
            $mesHasta !== null
        ) {

            $nombreDesde =
                $nombreMeses[$mesDesde]
                ?? '';


            $nombreHasta =
                $nombreMeses[$mesHasta]
                ?? '';


            $periodoTexto =
                $nombreDesde .
                ' a ' .
                $nombreHasta;


            if ($anio) {

                $periodoTexto .=
                    ' de ' .
                    $anio;

            }


            if ($esIngreso) {

                return
                    'Entre ' .
                    $periodoTexto .
                    ' se recaudaron S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    $descripcion .
                    '.';

            }


            if ($esEgreso) {

                return
                    'Entre ' .
                    $periodoTexto .
                    ' se registraron egresos por S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    $descripcion .
                    '.';

            }


            return
                'Entre ' .
                $periodoTexto .
                ' el total encontrado fue de S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                '.';
        }


        /*
        |--------------------------------------------------------------------------
        | VARIOS MESES
        |--------------------------------------------------------------------------
        */

      if (count($meses) > 1) {

    $nombresMesesSeleccionados = [];

    foreach ($meses as $mesSeleccionado) {

        $nombresMesesSeleccionados[] =
            $nombreMeses[$mesSeleccionado]
            ?? '';

    }

    $periodoTexto =
        implode(
            ' y ',
            $nombresMesesSeleccionados
        );

    if ($anio) {

        $periodoTexto .=
            ' de ' .
            $anio;

    }

    if ($esIngreso) {

        return
            'Durante ' .
            $periodoTexto .
            ' se recaudaron S/ ' .
            number_format(
                $resultado,
                2,
                '.',
                ','
            ) .
            $descripcion .
            '.';

    }

    if ($esEgreso) {

        return
            'Durante ' .
            $periodoTexto .
            ' se registraron egresos por S/ ' .
            number_format(
                $resultado,
                2,
                '.',
                ','
            ) .
            $descripcion .
            '.';

    }

    return
        'Durante ' .
        $periodoTexto .
        ' el total encontrado fue de S/ ' .
        number_format(
            $resultado,
            2,
            '.',
            ','
        ) .
        '.';
}

        /*
        |--------------------------------------------------------------------------
        | MES INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        $periodoTexto = '';


        if ($mes) {

            $periodoTexto =
                $nombreMeses[$mes]
                ?? '';

        }


        if ($anio && $periodoTexto) {

            $periodoTexto .=
                ' de ' .
                $anio;

        } elseif ($anio) {

            $periodoTexto =
                'el ' .
                $anio;

        }


        /*
        |--------------------------------------------------------------------------
        | INGRESOS
        |--------------------------------------------------------------------------
        */

        if ($esIngreso) {

            $mensaje =
                'Durante';


            if ($periodoTexto) {

                $mensaje .=
                    ' ' .
                    $periodoTexto;

            }


            $mensaje .=
                ' se recaudaron S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                $descripcion .
                '.';


            return $mensaje;
        }


        /*
        |--------------------------------------------------------------------------
        | EGRESOS
        |--------------------------------------------------------------------------
        */

        if ($esEgreso) {

            $mensaje =
                'Durante';


            if ($periodoTexto) {

                $mensaje .=
                    ' ' .
                    $periodoTexto;

            }


            $mensaje .=
                ' se registraron egresos por S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                $descripcion .
                '.';


            return $mensaje;
        }


        /*
        |--------------------------------------------------------------------------
        | GENERAL
        |--------------------------------------------------------------------------
        */

        return
            'El total encontrado es de S/ ' .
            number_format(
                $resultado,
                2,
                '.',
                ','
            ) .
            '.';
    }

    private function nombreMes(
        int $mes
    ): string {

        $meses = [

            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',

        ];

        return $meses[$mes]
            ?? 'mes desconocido';
    }

}