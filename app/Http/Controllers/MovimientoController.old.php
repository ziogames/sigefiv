<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Movimiento;
use App\Models\Periodo;
use Carbon\Carbon;
use App\Models\Categoria;
use App\Services\PeriodoService;

class DashboardService
{
    public static function obtenerDatos(): array
    {
        $hoy = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | Configuración
        |--------------------------------------------------------------------------
        */

        $configuracion = Configuracion::first();

        /*
        |--------------------------------------------------------------------------
        | Años disponibles
        |--------------------------------------------------------------------------
        */

        $anios = Periodo::select('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Período por defecto
        |--------------------------------------------------------------------------
        |
        | Se utiliza el último período existente en la base de datos.
        | Esto evita depender del mes real del servidor.
        |
        */

        $ultimoPeriodo = Periodo::orderByDesc('anio')
            ->orderByDesc('mes')
            ->first();

        $anioSeleccionado = request()->get(
            'anio',
            $ultimoPeriodo?->anio ?? $hoy->year
        );

        /*
        |--------------------------------------------------------------------------
        | Meses
        |--------------------------------------------------------------------------
        */

        $meses = [

            1  => 'Enero',
            2  => 'Febrero',
            3  => 'Marzo',
            4  => 'Abril',
            5  => 'Mayo',
            6  => 'Junio',
            7  => 'Julio',
            8  => 'Agosto',
            9  => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',

        ];

        /*
        |--------------------------------------------------------------------------
        | Mes seleccionado
        |--------------------------------------------------------------------------
        */

        $periodoUltimoDelAnio = Periodo::where(
            'anio',
            $anioSeleccionado
        )
            ->orderByDesc('mes')
            ->first();

        $mesSeleccionado = request()->get(
            'mes',
            $periodoUltimoDelAnio?->mes ?? $ultimoPeriodo?->mes ?? $hoy->month
        );

        /*
        |--------------------------------------------------------------------------
        | Período seleccionado
        |--------------------------------------------------------------------------
        */

        $periodo = Periodo::where('anio', $anioSeleccionado)
            ->where('mes', $mesSeleccionado)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Datos para gráfico anual
        |--------------------------------------------------------------------------
        */

        $graficoAnual = Periodo::where('anio', $anioSeleccionado)
            ->orderBy('mes')
            ->get()
            ->map(function ($periodo) {

                return [

                    'mes' => $periodo->nombre,

                    'ingresos' => (float) $periodo->total_ingresos,

                    'egresos' => (float) $periodo->total_egresos,

                    'saldo' => (float) $periodo->saldo_final,

                ];

            });

        /*
        |--------------------------------------------------------------------------
        | Últimos movimientos
        |--------------------------------------------------------------------------
        */

        $ultimosMovimientos = Movimiento::with('categoria')
            ->latest('fecha')
            ->take(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Resumen general
        |--------------------------------------------------------------------------
        */

        $promedioIngresos = Periodo::avg('total_ingresos');

        $promedioEgresos = Periodo::avg('total_egresos');

        $cantidadMovimientos = Movimiento::count();

        $mejorPeriodo = Periodo::orderByDesc('saldo_final')->first();

        $mejorMes = $mejorPeriodo
            ? $mejorPeriodo->nombre . ' ' . $mejorPeriodo->anio
            : '-';

        /*
        |--------------------------------------------------------------------------
        | Estado del cierre
        |--------------------------------------------------------------------------
        */

        $cierrePeriodo = PeriodoService::verificarCambioDeMes();

        /*
        |--------------------------------------------------------------------------
        | Retorno
        |--------------------------------------------------------------------------
        */

        return [

            'configuracionGlobal' => $configuracion,

            'periodo' => $periodo,

            'saldoCaja' => $periodo?->saldo_final ?? 0,

            'ingresos' => $periodo?->total_ingresos ?? 0,

            'egresos' => $periodo?->total_egresos ?? 0,

            'disponible' => $periodo?->saldo_final ?? 0,

            'ultimoMovimiento' => $ultimosMovimientos->first(),

            'ultimosMovimientos' => $ultimosMovimientos,

            'graficoAnual' => $graficoAnual,

            'anioSeleccionado' => $anioSeleccionado,

            'mesSeleccionado' => $mesSeleccionado,

            'anios' => $anios,

            'meses' => $meses,

            'promedioIngresos' => $promedioIngresos,

            'promedioEgresos' => $promedioEgresos,

            'cantidadMovimientos' => $cantidadMovimientos,

            'mejorMes' => $mejorMes,

            'cierrePeriodo' => $cierrePeriodo,

        ];
    }


    public static function obtenerDatosAjax(
        int $anio,
        ?int $mes = null
    ): array
    {

        /*
        |--------------------------------------------------------------------------
        | Períodos
        |--------------------------------------------------------------------------
        */

        $periodos = Periodo::where('anio', $anio)
            ->orderBy('mes')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Gráfico Principal
        |--------------------------------------------------------------------------
        */

        $grafico = $periodos->map(function ($periodo) {

            return [

                'mes' => $periodo->nombre,

                'ingresos' => (float) $periodo->total_ingresos,

                'egresos' => (float) $periodo->total_egresos,

                'saldo' => (float) $periodo->saldo_final,

            ];

        });

        /*
        |--------------------------------------------------------------------------
        | KPIs
        |--------------------------------------------------------------------------
        */

        if ($mes) {

            $periodoSeleccionado = Periodo::where('anio', $anio)
                ->where('mes', $mes)
                ->first();

        } else {

            $periodoSeleccionado = $periodos->last();

        }

        $saldoCaja = $periodoSeleccionado?->saldo_final ?? 0;

        $ingresos = $periodoSeleccionado?->total_ingresos ?? 0;

        $egresos = $periodoSeleccionado?->total_egresos ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Gastos por categoría
        |--------------------------------------------------------------------------
        */

        $pie = Movimiento::selectRaw('

            categoria_id,

            SUM(monto) total

        ')

            ->where('tipo', 'Egreso')

            ->whereYear('fecha', $anio)

            ->when(

                $mes,

                fn($q) => $q->whereMonth('fecha', $mes)

            )

            ->groupBy('categoria_id')

            ->with('categoria')

            ->get()

            ->map(function ($item) {

                return [

                    'nombre' =>
                        $item->categoria->nombre ?? 'Sin categoría',

                    'total' =>
                        (float) $item->total,

                ];

            });

        /*
        |--------------------------------------------------------------------------
        | Comparación mensual
        |--------------------------------------------------------------------------
        */

        $comparacion = [

            'ingresos' =>
                $periodoSeleccionado?->total_ingresos ?? 0,

            'egresos' =>
                $periodoSeleccionado?->total_egresos ?? 0,

        ];

        /*
        |--------------------------------------------------------------------------
        | Resumen
        |--------------------------------------------------------------------------
        */

        $resumen = [

            'promedioIngresos' =>

                round(

                    $periodos->avg('total_ingresos'),

                    2

                ),

            'promedioEgresos' =>

                round(

                    $periodos->avg('total_egresos'),

                    2

                ),

            'cantidadMeses' =>

                $periodos->count(),

        ];

        /*
        |--------------------------------------------------------------------------
        | Últimos movimientos
        |--------------------------------------------------------------------------
        */

        $movimientos = Movimiento::with(

            'categoria'

        )

            ->when(

                $mes,

                fn($q) => $q
                    ->whereYear('fecha', $anio)
                    ->whereMonth('fecha', $mes),

                fn($q) => $q
                    ->whereYear('fecha', $anio)

            )

            ->latest('fecha')

            ->take(10)

            ->get()

            ->map(function ($movimiento) {

                return [

                    'fecha' =>

                        $movimiento->fecha,

                    'concepto' =>

                        $movimiento->concepto,

                    'categoria' =>

                        $movimiento->categoria->nombre ?? '-',

                    'tipo' =>

                        $movimiento->tipo,

                    'monto' =>

                        $movimiento->monto,

                ];

            });

        /*
        |--------------------------------------------------------------------------
        | Respuesta
        |--------------------------------------------------------------------------
        */

        return [

            'kpis' => [

                'saldoCaja' => $saldoCaja,

                'ingresos' => $ingresos,

                'egresos' => $egresos,

            ],

            'grafico' => $grafico,

            'pie' => $pie,

            'comparacion' => $comparacion,

            'resumen' => $resumen,

            'movimientos' => $movimientos,

        ];

    }

}