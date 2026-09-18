<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovimientoRequest;
use App\Http\Requests\UpdateMovimientoRequest;
use App\Models\Movimiento;
use App\Models\Periodo;
use App\Services\FcmService;
use App\Services\MovimientoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovimientoController extends Controller
{
    public function __construct(
        private readonly FcmService $fcmService
    ) {
    }

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

        $movimientos = Movimiento::with(['categoria', 'periodo'])
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
            'periodo' => $this->transformarPeriodo($periodo),
            'movimientos' => $movimientos
                ->map(
                    fn (Movimiento $movimiento) =>
                        $this->transformarMovimiento($movimiento)
                )
                ->values(),
        ]);
    }

    /**
     * El detalle se puede consultar aunque el período esté cerrado.
     */
    public function show(Movimiento $movimiento): JsonResponse
    {
        $movimiento->load([
            'categoria',
            'periodo',
            'usuario',
        ]);

        return response()->json([
            'success' => true,
            'movimiento' => $this->transformarMovimiento(
                $movimiento,
                true
            ),
        ]);
    }

    /**
     * Registrar movimiento.
     *
     * MovimientoService::guardar() valida que el período
     * esté abierto.
     */
    public function store(
        StoreMovimientoRequest $request
    ): JsonResponse {
        try {

            $datos = $request->validated();

            $datos['user_id'] = auth()->id();

            $movimiento = MovimientoService::guardar(
                $datos
            );

            $movimiento->load([
                'categoria',
                'periodo',
                'usuario',
            ]);

            /*
             * Notificamos silenciosamente a los dispositivos
             * para que actualicen sus datos en tiempo real.
             */
            $this->fcmService->enviarEventoAUsuarios([
                'tipo' => 'movimiento_actualizado',
                'movimiento_id' => $movimiento->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento registrado correctamente.',
                'movimiento' => $this->transformarMovimiento(
                    $movimiento,
                    true
                ),
            ], 201);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
                    ?: 'No se pudo registrar el movimiento.',
            ], 422);
        }
    }

    /**
     * Actualizar movimiento.
     *
     * MovimientoService::actualizar() valida que el período
     * de destino esté abierto.
     */
    public function update(
        UpdateMovimientoRequest $request,
        Movimiento $movimiento
    ): JsonResponse {
        try {

            $datos = $request->validated();

            $movimiento = MovimientoService::actualizar(
                $movimiento,
                $datos
            );

            $movimiento->load([
                'categoria',
                'periodo',
                'usuario',
            ]);

            /*
             * Notificamos silenciosamente a los dispositivos
             * para que actualicen sus datos en tiempo real.
             */
            $this->fcmService->enviarEventoAUsuarios([
                'tipo' => 'movimiento_actualizado',
                'movimiento_id' => $movimiento->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento actualizado correctamente.',
                'movimiento' => $this->transformarMovimiento(
                    $movimiento,
                    true
                ),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
                    ?: 'No se pudo actualizar el movimiento.',
            ], 422);
        }
    }

    /**
     * Eliminar movimiento.
     *
     * MovimientoService::eliminar() valida que el período
     * esté abierto.
     */
    public function destroy(
        Movimiento $movimiento
    ): JsonResponse {
        try {

            $movimiento->load([
                'categoria',
                'periodo',
            ]);

            /*
             * Guardamos los datos antes de eliminarlo.
             */
            $id = $movimiento->id;
            $numero = $movimiento->numero;
            $periodo = $movimiento->periodo;

            MovimientoService::eliminar(
                $movimiento
            );

            /*
             * Notificamos silenciosamente a los dispositivos
             * para que actualicen sus datos en tiempo real.
             */
            $this->fcmService->enviarEventoAUsuarios([
                'tipo' => 'movimiento_actualizado',
                'movimiento_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento eliminado correctamente.',
                'movimiento_id' => $id,
                'numero' => $numero,
                'periodo' => $periodo
                    ? $this->transformarPeriodo($periodo)
                    : null,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
                    ?: 'No se pudo eliminar el movimiento.',
            ], 422);
        }
    }

    /**
     * Estructura estable para Android.
     *
     * Android puede usar estos campos para la interfaz,
     * pero la protección definitiva siempre la aplica Laravel.
     */
    private function transformarMovimiento(
        Movimiento $movimiento,
        bool $detalle = false
    ): array {
        $periodo = $movimiento->periodo;

        $resultado = [
            'id' => $movimiento->id,
            'numero' => $movimiento->numero,
            'fecha' => $movimiento->fecha?->format('d-m-Y'),
            'tipo' => $movimiento->tipo,
            'categoria' => $movimiento->categoria?->nombre,
            'categoria_id' => $movimiento->categoria_id,
            'concepto' => $movimiento->concepto,
            'persona' => $movimiento->persona,
            'forma_pago' => $movimiento->forma_pago,
            'monto' => (float) $movimiento->monto,
            'comprobante' => $movimiento->comprobante,
            'referencia' => $movimiento->referencia,
            'observaciones' => $movimiento->observaciones,
            'estado' => $movimiento->estado,
            'periodo' => $periodo
                ? $this->transformarPeriodo($periodo)
                : null,
            'periodo_abierto' => $periodo
                ? $periodo->estado === 'Abierto'
                : false,
            'puede_editar' => $periodo
                ? $periodo->estado === 'Abierto'
                : false,
            'puede_eliminar' => $periodo
                ? $periodo->estado === 'Abierto'
                : false,
        ];

        if ($detalle) {

            $resultado['usuario'] =
                $movimiento->usuario
                    ? [
                        'id' =>
                            $movimiento->usuario->id,

                        'nombre' =>
                            $movimiento->usuario->name,

                        'email' =>
                            $movimiento->usuario->email,
                    ]
                    : null;
        }

        return $resultado;
    }

    private function transformarPeriodo(
        Periodo $periodo
    ): array {
        return [
            'id' => $periodo->id,
            'nombre' => $periodo->nombre,
            'nombre_completo' =>
                $periodo->nombre_completo,
            'anio' => $periodo->anio,
            'mes' => $periodo->mes,
            'estado' => $periodo->estado,
            'abierto' =>
                $periodo->estado === 'Abierto',
            'fecha_cierre' =>
                $periodo->fecha_cierre
                    ?->format('Y-m-d H:i:s'),
            'saldo_inicial' =>
                (float) $periodo->saldo_inicial,
            'total_ingresos' =>
                (float) $periodo->total_ingresos,
            'total_egresos' =>
                (float) $periodo->total_egresos,
            'saldo_final' =>
                (float) $periodo->saldo_final,
        ];
    }
}