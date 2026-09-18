<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use App\Services\PeriodoService;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

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


        // Rango completo del mes del período: primer día hasta último día.
        $inicioMes = Carbon::create(
            $periodo->anio,
            $periodo->mes,
            1
        )->startOfMonth();

        $finMes = Carbon::create(
            $periodo->anio,
            $periodo->mes,
            1
        )->endOfMonth();

        $movimientos =
            $periodo
                ->movimientos()
                ->with('categoria')
                ->where('estado', 'Registrado')
                ->whereBetween('fecha', [
                    $inicioMes->toDateString(),
                    $finMes->toDateString()
                ])
               ->orderBy('fecha')
                ->orderBy('id')
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
                                    'd-m-Y'
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

    /**
 * Cierra un período contable.
 *
 * Solo el Tesorero puede ejecutar esta operación.
 */
public function cerrar(
    int $id
): JsonResponse {

    $usuario = request()->user();

    /*
    |--------------------------------------------------------------------------
    | Verificar rol
    |--------------------------------------------------------------------------
    */

    if (
    !$usuario ||
    !$usuario->hasRole('Tesorero')
) {
        return response()->json([
            'success' => false,
            'message' => 'Solo el Tesorero puede cerrar períodos.',
        ], 403);
    }

    /*
    |--------------------------------------------------------------------------
    | Buscar período
    |--------------------------------------------------------------------------
    */

    $periodo = Periodo::find($id);

    if (!$periodo) {

        return response()->json([
            'success' => false,
            'message' => 'El período solicitado no existe.',
            'periodo' => null,
        ], 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Verificar que esté abierto
    |--------------------------------------------------------------------------
    */

    if ($periodo->estado !== 'Abierto') {

        return response()->json([
            'success' => false,
            'message' => 'El período ya está cerrado.',
            'periodo' => null,
        ], 422);
    }

    /*
    |--------------------------------------------------------------------------
    | Cerrar período
    |--------------------------------------------------------------------------
    */

    PeriodoService::cerrarPeriodo($periodo);

    /*
    |--------------------------------------------------------------------------
    | Obtener período actualizado
    |--------------------------------------------------------------------------
    */

    $periodo->refresh();

    return response()->json([
        'success' => true,
        'message' =>
            'El período ' .
            $periodo->nombre_completo .
            ' fue cerrado correctamente.',
        'periodo' => [
            'id' => $periodo->id,
            'nombre' => $periodo->nombre,
            'nombre_completo' => $periodo->nombre_completo,
            'anio' => $periodo->anio,
            'mes' => $periodo->mes,
            'saldo_inicial' => (float) $periodo->saldo_inicial,
            'total_ingresos' => (float) $periodo->total_ingresos,
            'total_egresos' => (float) $periodo->total_egresos,
            'saldo_final' => (float) $periodo->saldo_final,
            'estado' => $periodo->estado,
            'fecha_cierre' =>
                $periodo->fecha_cierre?->format(
                    'Y-m-d H:i:s'
                ),
        ],
    ]);
}
}