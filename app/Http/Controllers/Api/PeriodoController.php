<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use App\Services\PeriodoService;
use Illuminate\Http\JsonResponse;

class PeriodoController extends Controller
{
    /**
     * Devuelve el período contable actualmente abierto.
     */
    public function abierto(): JsonResponse
    {
        $periodo = PeriodoService::obtenerPeriodoAbierto();

        if (!$periodo) {

            return response()->json([
                'success' => false,
                'message' => 'No existe un período contable abierto.',
                'periodo' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,

            'periodo' => [
                'id' =>
                    $periodo->id,

                'nombre' =>
                    $periodo->nombre,

                'nombre_completo' =>
                    $periodo->nombre_completo,

                'anio' =>
                    $periodo->anio,

                'mes' =>
                    $periodo->mes,

                'saldo_inicial' =>
                    (float) $periodo->saldo_inicial,

                'total_ingresos' =>
                    (float) $periodo->total_ingresos,

                'total_egresos' =>
                    (float) $periodo->total_egresos,

                'saldo_final' =>
                    (float) $periodo->saldo_final,

                'fecha_cierre' =>
                    $periodo->fecha_cierre?->format(
                        'Y-m-d H:i:s'
                    ),

                'estado' =>
                    $periodo->estado,
            ],
        ]);
    }


    /**
     * Devuelve todos los períodos contables registrados.
     */
    public function index(): JsonResponse
    {
        $periodos = Periodo::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();

        return response()->json([
            'success' => true,

            'periodos' => $periodos->map(function ($periodo) {

                return [
                    'id' =>
                        $periodo->id,

                    'nombre' =>
                        $periodo->nombre,

                    'nombre_completo' =>
                        $periodo->nombre_completo,

                    'anio' =>
                        $periodo->anio,

                    'mes' =>
                        $periodo->mes,

                    'saldo_inicial' =>
                        (float) $periodo->saldo_inicial,

                    'total_ingresos' =>
                        (float) $periodo->total_ingresos,

                    'total_egresos' =>
                        (float) $periodo->total_egresos,

                    'saldo_final' =>
                        (float) $periodo->saldo_final,

                    'fecha_cierre' =>
                        $periodo->fecha_cierre?->format(
                            'Y-m-d H:i:s'
                        ),

                    'estado' =>
                        $periodo->estado,
                ];

            })->values(),
        ]);
    }


    /**
     * Devuelve el resumen financiero de un período.
     */
    public function show(
        int $id
    ): JsonResponse {

        $periodo =
            Periodo::find($id);

        if (!$periodo) {

            return response()->json([
                'success' => false,
                'message' => 'El período solicitado no existe.',
                'periodo' => null,
            ], 404);
        }


        /*
        |--------------------------------------------------------------------------
        | Valores financieros
        |--------------------------------------------------------------------------
        */

        $saldoAnterior =
            (float) $periodo->saldo_inicial;

        $totalIngresos =
            (float) $periodo->total_ingresos;

        $saldoDisponible =
            $saldoAnterior +
            $totalIngresos;

        $totalEgresos =
            (float) $periodo->total_egresos;

        $saldoCaja =
            $saldoDisponible -
            $totalEgresos;


        /*
        |--------------------------------------------------------------------------
        | Cantidad de movimientos registrados
        |--------------------------------------------------------------------------
        */

        $totalMovimientos =
            $periodo->movimientos()
                ->where('estado', 'Registrado')
                ->count();


        return response()->json([

            'success' => true,

            'periodo' => [

                'id' =>
                    $periodo->id,

                'nombre' =>
                    $periodo->nombre,

                'nombre_completo' =>
                    $periodo->nombre_completo,

                'anio' =>
                    $periodo->anio,

                'mes' =>
                    $periodo->mes,

                'estado' =>
                    $periodo->estado,

                'fecha_cierre' =>
                    $periodo->fecha_cierre?->format(
                        'Y-m-d H:i:s'
                    ),

                'saldo_anterior' =>
                    $saldoAnterior,

                'total_ingresos' =>
                    $totalIngresos,

                'saldo_disponible' =>
                    $saldoDisponible,

                'total_egresos' =>
                    $totalEgresos,

                'saldo_caja' =>
                    $saldoCaja,

                'saldo_final' =>
                    (float) $periodo->saldo_final,

                'total_movimientos' =>
                    $totalMovimientos,
            ],
        ]);
    }


    /**
     * Devuelve los movimientos registrados de un período específico.
     */
    public function movimientos(
        int $id
    ): JsonResponse {

        $periodo =
            Periodo::find($id);

        if (!$periodo) {

            return response()->json([
                'success' => false,
                'message' => 'El período solicitado no existe.',
                'movimientos' => [],
            ], 404);
        }


        $movimientos =
            $periodo
                ->movimientos()
                ->with('categoria')
                ->where('estado', 'Registrado')
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->get();


        return response()->json([

            'success' => true,

            'periodo' => [

                'id' =>
                    $periodo->id,

                'nombre' =>
                    $periodo->nombre,

                'nombre_completo' =>
                    $periodo->nombre_completo,

                'anio' =>
                    $periodo->anio,

                'mes' =>
                    $periodo->mes,

                'estado' =>
                    $periodo->estado,

                'fecha_cierre' =>
                    $periodo->fecha_cierre?->format(
                        'Y-m-d H:i:s'
                    ),
            ],

            'movimientos' =>
                $movimientos
                    ->map(function ($movimiento) {

                        return [

                            'id' =>
                                $movimiento->id,

                            'numero' =>
                                $movimiento->numero,

                            'fecha' =>
                                $movimiento->fecha?->format(
                                    'Y-m-d'
                                ),

                            'tipo' =>
                                $movimiento->tipo,

                            'categoria' =>
                                $movimiento
                                    ->categoria?->nombre,

                            'concepto' =>
                                $movimiento->concepto,

                            'persona' =>
                                $movimiento->persona,

                            'forma_pago' =>
                                $movimiento->forma_pago,

                            'monto' =>
                                (float) $movimiento->monto,

                            'comprobante' =>
                                $movimiento->comprobante,

                            'referencia' =>
                                $movimiento->referencia,

                            'observaciones' =>
                                $movimiento->observaciones,

                            'estado' =>
                                $movimiento->estado,
                        ];
                    })
                    ->values(),
        ]);
    }
}