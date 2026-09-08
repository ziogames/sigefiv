<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    /**
     * Obtener el consolidado de Caja.
     *
     * Utiliza exactamente la misma lógica financiera
     * que la versión web de SIGEFIV.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /*
            |--------------------------------------------------------------------------
            | AÑOS DISPONIBLES
            |--------------------------------------------------------------------------
            */

            $anios = Periodo::select('anio')
                ->distinct()
                ->orderByDesc('anio')
                ->pluck('anio')
                ->values();

            /*
            |--------------------------------------------------------------------------
            | AÑO SELECCIONADO
            |--------------------------------------------------------------------------
            */

            $anio = $request->filled('anio')
                ? (int) $request->input('anio')
                : (int) ($anios->first() ?? 0);

            /*
            |--------------------------------------------------------------------------
            | CONSOLIDADO DINÁMICO
            |--------------------------------------------------------------------------
            |
            | Se utiliza el mismo método que utiliza la página web.
            | Los datos se calculan desde los movimientos reales.
            |
            */

            $consolidado = [];

            if ($anio > 0) {
                $consolidado =
                    ReporteService::obtenerConsolidadoDinamico(
                        $anio,
                        1,
                        12
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | RESUMEN ANUAL
            |--------------------------------------------------------------------------
            */

            $saldoInicial =
                !empty($consolidado)
                    ? (float) ($consolidado[0]->saldo_inicial ?? 0)
                    : 0;

            $totalIngresos =
                (float) collect($consolidado)
                    ->sum('total_ingresos');

            $totalEgresos =
                (float) collect($consolidado)
                    ->sum('total_egresos');

            /*
            |--------------------------------------------------------------------------
            | ÚLTIMO MES CON MOVIMIENTOS
            |--------------------------------------------------------------------------
            |
            | El consolidado contiene los 12 meses del año.
            | Los meses que todavía no tienen movimientos tienen
            | ingresos y egresos en cero.
            |
            | Por eso no usamos last(), porque podría devolver
            | diciembre aunque todavía no tenga movimientos.
            |
            */

            $ultimoMesConMovimientos = collect($consolidado)
                ->filter(function ($fila) {
                    return
                        (float) ($fila->total_ingresos ?? 0) != 0 ||
                        (float) ($fila->total_egresos ?? 0) != 0;
                })
                ->last();

            /*
            |--------------------------------------------------------------------------
            | SALDO FINAL
            |--------------------------------------------------------------------------
            */

            if ($ultimoMesConMovimientos !== null) {
                $saldoFinal =
                    (float) (
                        $ultimoMesConMovimientos->saldo_final ?? 0
                    );
            } else {
                $saldoFinal = $saldoInicial;
            }

            /*
            |--------------------------------------------------------------------------
            | PORCENTAJES
            |--------------------------------------------------------------------------
            */

            $totalMovimientos =
                $totalIngresos + $totalEgresos;

            $porcentajeIngresos =
                $totalMovimientos > 0
                    ? ($totalIngresos / $totalMovimientos) * 100
                    : 0;

            $porcentajeEgresos =
                $totalMovimientos > 0
                    ? ($totalEgresos / $totalMovimientos) * 100
                    : 0;

            /*
            |--------------------------------------------------------------------------
            | MESES
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
            | FORMATEAR CONSOLIDADO
            |--------------------------------------------------------------------------
            */

            $filas = collect($consolidado)
                ->map(function ($fila) use ($meses) {
                    return [
                        'mes' =>
                            (int) $fila->mes,

                        'nombre_mes' =>
                            $meses[$fila->mes]
                            ?? 'Mes',

                        'saldo_inicial' =>
                            (float) ($fila->saldo_inicial ?? 0),

                        'ingresos' =>
                            (float) ($fila->total_ingresos ?? 0),

                        'egresos' =>
                            (float) ($fila->total_egresos ?? 0),

                        'saldo_final' =>
                            (float) ($fila->saldo_final ?? 0),
                    ];
                })
                ->values();

            /*
            |--------------------------------------------------------------------------
            | RESPUESTA
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,

                'message' =>
                    'Consolidado de Caja obtenido correctamente.',

                'anio' =>
                    $anio,

                'anios' =>
                    $anios,

                'resumen' => [
                    'saldo_inicial' =>
                        $saldoInicial,

                    'ingresos' =>
                        $totalIngresos,

                    'egresos' =>
                        $totalEgresos,

                    'saldo_final' =>
                        $saldoFinal,

                    'porcentaje_ingresos' =>
                        round($porcentajeIngresos, 2),

                    'porcentaje_egresos' =>
                        round($porcentajeEgresos, 2),
                ],

                'consolidado' =>
                    $filas,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,

                'message' =>
                    'No se pudo obtener el consolidado de Caja.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}