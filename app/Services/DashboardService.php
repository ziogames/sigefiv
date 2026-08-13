<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Movimiento;
use App\Models\Periodo;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Obtiene todos los datos necesarios para el Dashboard.
     */
    public static function obtenerDatos(): array
    {
        $configuracion = Configuracion::first();

        /*
        |--------------------------------------------------------------------------
        | Período actual
        |--------------------------------------------------------------------------
        */

        $periodo = Periodo::where('estado', 'Abierto')
            ->orderBy('anio')
            ->orderBy('mes')
            ->first();

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

        $anioSeleccionado = request()->get(
            'anio',
            $anios->first()
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
        | Datos reales del año
        |--------------------------------------------------------------------------
        |
        | Los ingresos y egresos se obtienen directamente de Movimiento.
        | No dependemos de los totales almacenados en Periodo.
        |
        */

        $periodos = Periodo::where('anio', $anioSeleccionado)
            ->orderBy('mes')
            ->get();

        $graficoAnual = self::construirDatosPeriodos($periodos);

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
        | Totales del año
        |--------------------------------------------------------------------------
        */

        $ingresos = $graficoAnual->sum('ingresos');

        $egresos = $graficoAnual->sum('egresos');

        /*
        |--------------------------------------------------------------------------
        | Saldo actual
        |--------------------------------------------------------------------------
        */

        $saldoCaja = $graficoAnual->last()['saldo'] ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Promedios
        |--------------------------------------------------------------------------
        */

        $promedioIngresos = $graficoAnual->count() > 0
            ? round($graficoAnual->avg('ingresos'), 2)
            : 0;

        $promedioEgresos = $graficoAnual->count() > 0
            ? round($graficoAnual->avg('egresos'), 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | Cantidad de movimientos
        |--------------------------------------------------------------------------
        */

        $cantidadMovimientos = Movimiento::where('estado', 'Registrado')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Mejor período
        |--------------------------------------------------------------------------
        */

        $mejorPeriodo = $graficoAnual
            ->sortByDesc('saldo')
            ->first();

        $mejorMes = $mejorPeriodo
            ? $mejorPeriodo['mes']
            : '-';

        /*
        |--------------------------------------------------------------------------
        | Cierre del período
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

            'saldoCaja' => $saldoCaja,

            'ingresos' => $ingresos,

            'egresos' => $egresos,

            'disponible' => $saldoCaja,

            'ultimoMovimiento' =>
                $ultimosMovimientos->first(),

            'ultimosMovimientos' =>
                $ultimosMovimientos,

            'graficoAnual' =>
                $graficoAnual,

            'anioSeleccionado' =>
                $anioSeleccionado,

            'anios' =>
                $anios,

            'meses' =>
                $meses,

            'promedioIngresos' =>
                $promedioIngresos,

            'promedioEgresos' =>
                $promedioEgresos,

            'cantidadMovimientos' =>
                $cantidadMovimientos,

            'mejorMes' =>
                $mejorMes,

            'cierrePeriodo' =>
                $cierrePeriodo,

        ];
    }


    /**
     * Obtiene datos para las peticiones AJAX del Dashboard.
     */
    public static function obtenerDatosAjax(
        int $anio,
        ?int $mes = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | Períodos del año
        |--------------------------------------------------------------------------
        */

        $periodos = Periodo::where('anio', $anio)
            ->orderBy('mes')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Gráfico principal
        |--------------------------------------------------------------------------
        */

        $grafico = self::construirDatosPeriodos($periodos);

        /*
        |--------------------------------------------------------------------------
        | KPIs
        |--------------------------------------------------------------------------
        */

        $saldoCaja =
            $grafico->last()['saldo'] ?? 0;

        $ingresos =
            $grafico->sum('ingresos');

        $egresos =
            $grafico->sum('egresos');

        /*
        |--------------------------------------------------------------------------
        | Gastos por categoría
        |--------------------------------------------------------------------------
        */

        $pie = Movimiento::selectRaw('
                categoria_id,
                SUM(monto) as total
            ')
            ->where('tipo', 'Egreso')
            ->where('estado', 'Registrado')
            ->whereYear('fecha', $anio)
            ->when(
                $mes,
                fn ($q) =>
                    $q->whereMonth('fecha', $mes)
            )
            ->groupBy('categoria_id')
            ->with('categoria')
            ->get()
            ->map(function ($item) {

                return [

                    'nombre' =>
                        $item->categoria->nombre
                        ?? 'Sin categoría',

                    'total' =>
                        (float) $item->total,

                ];

            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Comparación mensual
        |--------------------------------------------------------------------------
        */

        if ($mes) {

            $datoMes = $grafico
                ->firstWhere('numero_mes', $mes);

        } else {

            $datoMes =
                $grafico->last();

        }

        $comparacion = [

            'ingresos' =>
                $datoMes['ingresos']
                ?? 0,

            'egresos' =>
                $datoMes['egresos']
                ?? 0,

        ];

        /*
        |--------------------------------------------------------------------------
        | Resumen
        |--------------------------------------------------------------------------
        */

        $resumen = [

            'promedioIngresos' =>
                $grafico->count() > 0
                    ? round(
                        $grafico->avg('ingresos'),
                        2
                    )
                    : 0,

            'promedioEgresos' =>
                $grafico->count() > 0
                    ? round(
                        $grafico->avg('egresos'),
                        2
                    )
                    : 0,

            'cantidadMeses' =>
                $grafico->count(),

        ];

        /*
        |--------------------------------------------------------------------------
        | Últimos movimientos
        |--------------------------------------------------------------------------
        */

        $movimientos = Movimiento::with('categoria')
            ->where('estado', 'Registrado')
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
                        $movimiento->categoria->nombre
                        ?? '-',

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

                'saldoCaja' =>
                    $saldoCaja,

                'ingresos' =>
                    $ingresos,

                'egresos' =>
                    $egresos,

            ],

            'grafico' =>
                $grafico,

            'pie' =>
                $pie,

            'comparacion' =>
                $comparacion,

            'resumen' =>
                $resumen,

            'movimientos' =>
                $movimientos,

        ];
    }


    /**
     * Construye los datos reales de los períodos.
     *
     * Los valores se calculan desde Movimiento.
     */
    private static function construirDatosPeriodos($periodos)
    {
        $saldoAcumulado = null;

        return $periodos
            ->map(function ($periodo) use (&$saldoAcumulado) {

                /*
                |--------------------------------------------------------------------------
                | Ingresos reales
                |--------------------------------------------------------------------------
                */

                $ingresos = Movimiento::where(
                        'periodo_id',
                        $periodo->id
                    )
                    ->where('tipo', 'Ingreso')
                    ->where('estado', 'Registrado')
                    ->sum('monto');

                /*
                |--------------------------------------------------------------------------
                | Egresos reales
                |--------------------------------------------------------------------------
                */

                $egresos = Movimiento::where(
                        'periodo_id',
                        $periodo->id
                    )
                    ->where('tipo', 'Egreso')
                    ->where('estado', 'Registrado')
                    ->sum('monto');

                $ingresos = (float) $ingresos;

                $egresos = (float) $egresos;

                /*
                |--------------------------------------------------------------------------
                | Saldo
                |--------------------------------------------------------------------------
                |
                | Para el primer período utilizamos su saldo inicial.
                | Los siguientes períodos continúan desde el saldo anterior.
                |
                */

                if ($saldoAcumulado === null) {

                    $saldoAcumulado =
                        (float) $periodo->saldo_inicial;

                }

                $saldoAcumulado =
                    $saldoAcumulado
                    + $ingresos
                    - $egresos;

                return [

                    'numero_mes' =>
                        (int) $periodo->mes,

                    'mes' =>
                        $periodo->nombre,

                    'ingresos' =>
                        $ingresos,

                    'egresos' =>
                        $egresos,

                    'saldo' =>
                        round($saldoAcumulado, 2),

                ];

            })
            ->values();
    }
}