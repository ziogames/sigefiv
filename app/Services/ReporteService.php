<?php

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Periodo;
use Illuminate\Http\Request;

class ReporteService
{
    /**
     * Consulta movimientos.
     */
    public static function consultar(
        string $reporte,
        int $anio,
        ?int $mes = null
    ) {

        $consulta = Movimiento::with([
            'categoria',
            'usuario',
            'periodo'
        ]);

        $consulta->whereHas('periodo', function ($q) use ($anio, $mes) {

            $q->where('anio', $anio);

            if ($mes) {

                $q->where('mes', $mes);

            }

        });

        switch ($reporte) {

            case 'ingresos':

                $consulta->where('tipo', 'Ingreso');

                break;

            case 'egresos':

                $consulta->where('tipo', 'Egreso');

                break;

            case 'estado':

            case 'caja':

                break;

        }

        $movimientos = $consulta

            ->orderBy('fecha')

            ->orderBy('numero')

            ->get();

        $saldo = $movimientos->first()?->periodo->saldo_inicial ?? 0;

        foreach ($movimientos as $movimiento) {

            $movimiento->saldo_anterior = $saldo;

            if ($movimiento->tipo == 'Ingreso') {

                $saldo += $movimiento->monto;

            } else {

                $saldo -= $movimiento->monto;

            }

            $movimiento->saldo = $saldo;

        }

        return $movimientos;
    }

    /**
 * Devuelve un Estado Financiero por cada mes.
 */
public static function consultarIntervalo(
    int $anio,
    int $desde,
    int $hasta
): array {

    $estados = [];

    for ($mes = $desde; $mes <= $hasta; $mes++) {

        $movimientos = self::consultar(
            'estado',
            $anio,
            $mes
        );

        $estados[] = [

            'mes' => $mes,

            'anio' => $anio,

            'movimientos' => $movimientos,

            'ingresos' => $movimientos
                ->where('tipo', 'Ingreso')
                ->values(),

            'egresos' => $movimientos
                ->where('tipo', 'Egreso')
                ->values(),

            'maxFilas' => max(
                $movimientos->where('tipo', 'Ingreso')->count(),
                $movimientos->where('tipo', 'Egreso')->count()
),

            'resumen' => self::obtenerResumen(
                $movimientos
            ),

        ];

    }

    return $estados;

}

    /**
     * Obtiene el resumen financiero.
     */
    public static function obtenerResumen($movimientos): array
    {

        if ($movimientos->isEmpty()) {

            return [

                'saldo_inicial' => 0,

                'ingresos' => 0,

                'disponible' => 0,

                'egresos' => 0,

                'saldo_caja' => 0,

            ];

        }

        $saldoInicial = $movimientos->first()->saldo_anterior;

        $ingresos = $movimientos

            ->where('tipo', 'Ingreso')

            ->sum('monto');

        $egresos = $movimientos

            ->where('tipo', 'Egreso')

            ->sum('monto');

        return [

            'saldo_inicial' => $saldoInicial,

            'ingresos' => $ingresos,

            'disponible' =>

                $saldoInicial +

                $ingresos,

            'egresos' => $egresos,

            'saldo_caja' =>

                $saldoInicial +

                $ingresos -

                $egresos,

        ];

    }

    /**
     * Obtiene los períodos del consolidado.
     */
public static function obtenerConsolidado(
    int $anio,
    int $desde,
    int $hasta
)
{
    return Periodo::where('anio', $anio)

        ->whereBetween('mes', [

            $desde,

            $hasta

        ])

        ->orderBy('mes')

        ->get([

            'id',

            'anio',

            'mes',

            'saldo_inicial',

            'total_ingresos',

            'total_egresos',

            'saldo_final',

        ]);
}
/**
 * Obtiene el consolidado recalculando cada mes
 * a partir de sus movimientos.
 */
public static function obtenerConsolidadoDinamico(
    int $anio,
    int $desde,
    int $hasta
): array {

    $consolidado = [];

    for ($mes = $desde; $mes <= $hasta; $mes++) {

        $movimientos = self::consultar(
            'estado',
            $anio,
            $mes
        );

        $resumen = self::obtenerResumen(
            $movimientos
        );

        $consolidado[] = (object)[

            'anio' => $anio,

            'mes' => $mes,

            'saldo_inicial' => $resumen['saldo_inicial'],

            'total_ingresos' => $resumen['ingresos'],

            'total_egresos' => $resumen['egresos'],

            'saldo_final' => $resumen['saldo_caja'],

        ];

    }

    return $consolidado;

}
    /**
     * Prepara toda la información para el PDF.
     */
    public static function prepararDatosPdf(
        Request $request
    ): array {

        $generarEstado = $request->boolean('estado');

        $generarConsolidado = $request->boolean('consolidado');

        $anioEstado = (int) $request->anio;
       

        $mesEstado = (int) $request->mes;

        $anioConsolidado = $anioEstado;

        $desde = (int) $request->desde;

        $hasta = (int) $request->hasta;

       $movimientos = collect();

$estados = [];

if ($generarEstado) {

    $estados = self::consultarIntervalo(

        $anioEstado,

        $desde,

        $hasta

    );

}

        return [

            'movimientos' => $movimientos,
            'estados' => $estados,

            'resumen' => self::obtenerResumen(

                $movimientos

            ),

           'consolidado' => self::obtenerConsolidadoDinamico(

                $anioConsolidado,

                $desde,

                $hasta

            ),

            'generarEstado' =>

                $generarEstado,

            'generarConsolidado' =>

                $generarConsolidado,

            'anioEstado' =>

                $anioEstado,

            'mesEstado' =>

                $mesEstado,

            'anioConsolidado' =>

                $anioConsolidado,

            'desde' =>

                $desde,

            'hasta' =>

                $hasta,

        ];

    }
}