<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovimientoRequest;
use App\Models\Movimiento;
use App\Models\Periodo;
use App\Services\MovimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovimientoController extends Controller
{
    /**
     * Devuelve los movimientos.
     *
     * Si se envía ?limite=10, devuelve solamente
     * los 10 movimientos más recientes.
     *
     * Sin límite, devuelve todos los movimientos registrados.
     */
    public function index(Request $request): JsonResponse
    {
        $periodo = Periodo::obtenerAbierto();

        if (! $periodo) {

            return response()->json([
                'success' => false,
                'message' => 'No existe un período contable abierto.',
                'movimientos' => [],
            ], 404);
        }

        $movimientos = Movimiento::with([
            'categoria',
            'periodo',
        ])
            ->where('estado', 'Registrado')
            ->where('periodo_id', $periodo->id)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->when(
                $request->filled('limite'),
                function ($query) use ($request) {

                    $limite = (int) $request->input('limite');

                    if ($limite > 0) {

                        $query->limit($limite);
                    }
                }
            )
            ->get();

        return response()->json([

            'success' => true,

            'movimientos' => $movimientos->map(function ($movimiento) {

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

                ];

            })->values(),

        ]);
    }

    /**
     * Registrar un nuevo movimiento desde la API.
     */
    public function store(
        StoreMovimientoRequest $request
    ): JsonResponse {

        try {

            /*
            |--------------------------------------------------------------------------
            | Datos validados
            |--------------------------------------------------------------------------
            */

            $datos =
                $request->validated();

            /*
            |--------------------------------------------------------------------------
            | Usuario que registra
            |--------------------------------------------------------------------------
            */

            $datos['user_id'] =
                auth()->id();

            /*
            |--------------------------------------------------------------------------
            | Guardar utilizando la lógica existente
            |--------------------------------------------------------------------------
            */

            $movimiento =
                MovimientoService::guardar(
                    $datos
                );

            /*
            |--------------------------------------------------------------------------
            | Cargar relaciones
            |--------------------------------------------------------------------------
            */

            $movimiento->load([
                'categoria',
                'periodo',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Respuesta
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => true,

                'message' => 'Movimiento registrado correctamente.',

                'movimiento' => [

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

                ],

            ], 201);

        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Error
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'success' => false,

                'message' => 'No se pudo registrar el movimiento.',

                'error' => $e->getMessage(),

            ], 500);
        }
    }
}
