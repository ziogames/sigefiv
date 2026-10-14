<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movimiento;
use App\Models\Periodo;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Datos principales del Dashboard.
     */
    public function index(): JsonResponse
    {
        $periodo = Periodo::obtenerAbierto();

        if (!$periodo) {

            return response()->json([
                'success' => false,
                'message' => 'No existe un período abierto.',
                'movimientos' => [],
            ], 404);
        }

        // Movimientos recientes del período abierto
       $movimientos = Movimiento::with([
        'categoria',
        'periodo'
    ])
    ->where('estado', 'Registrado')
    ->where('periodo_id', $periodo->id)
    ->orderByDesc('fecha')
    ->orderByDesc('id')
    ->limit(5)
    ->get()
    ->map(function ($movimiento) {

        $periodoMovimiento = $movimiento->periodo;

        return [
            'id' => $movimiento->id,
            'numero' => $movimiento->numero,
            'fecha' => $movimiento->fecha?->format('Y-m-d'),
            'tipo' => $movimiento->tipo,
            'categoria' => $movimiento->categoria?->nombre,
            'concepto' => $movimiento->concepto,
            'persona' => $movimiento->persona,
            'forma_pago' => $movimiento->forma_pago,
            'monto' => (float) $movimiento->monto,
            'comprobante' => $movimiento->comprobante,
            'referencia' => $movimiento->referencia,
            'observaciones' => $movimiento->observaciones,
            'estado' => $movimiento->estado,

            'periodo' => $periodoMovimiento
                ? [
                    'id' => $periodoMovimiento->id,
                    'nombre' => $periodoMovimiento->nombre,
                    'nombre_completo' => $periodoMovimiento->nombre_completo,
                    'anio' => $periodoMovimiento->anio,
                    'mes' => $periodoMovimiento->mes,
                    'estado' => $periodoMovimiento->estado,
                    'abierto' => $periodoMovimiento->estado === 'Abierto',
                    'fecha_cierre' => $periodoMovimiento->fecha_cierre?->format('Y-m-d H:i:s'),
                    'saldo_inicial' => (float) $periodoMovimiento->saldo_inicial,
                    'total_ingresos' => (float) $periodoMovimiento->total_ingresos,
                    'total_egresos' => (float) $periodoMovimiento->total_egresos,
                    'saldo_final' => (float) $periodoMovimiento->saldo_final,
                ]
                : null,
        ];
    });

        return response()->json([

            'success' => true,

            'periodo' => [
                'nombre' =>
                    $periodo->nombre_completo,

                'anio' =>
                    $periodo->anio,

                'mes' =>
                    $periodo->mes,

                'estado' =>
                    $periodo->estado,

                'saldo_inicial' =>
                    (float) $periodo->saldo_inicial,

                'ingresos' =>
                    (float) $periodo->total_ingresos,

                'egresos' =>
                    (float) $periodo->total_egresos,

                'saldo_final' =>
                    (float) $periodo->saldo_final,
            ],

            'movimientos' =>
                $movimientos,

        ]);
    }
}