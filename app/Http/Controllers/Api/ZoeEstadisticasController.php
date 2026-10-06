<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Estadisticas\ZoeEstadisticasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoeEstadisticasController extends Controller
{
    public function __construct(
        private ZoeEstadisticasService $estadisticas
    ) {
    }

    /**
     * Ingresos vs egresos de los últimos períodos.
     *
     * GET /api/estadisticas/ingresos-egresos?limite=6
     */
    public function ingresosVsEgresos(Request $request): JsonResponse
    {
        $limite = (int) $request->input('limite', 6);

        return response()->json(
            $this->estadisticas->ingresosVsEgresos($limite)
        );
    }

    /**
     * Evolución del saldo de los últimos períodos.
     *
     * GET /api/estadisticas/evolucion-saldo?limite=6
     */
    public function evolucionSaldo(Request $request): JsonResponse
    {
        $limite = (int) $request->input('limite', 6);

        return response()->json(
            $this->estadisticas->evolucionSaldo($limite)
        );
    }

    /**
     * Egresos agrupados por categoría.
     *
     * GET /api/estadisticas/egresos-categoria?anio=2026&mes=9
     */
    public function egresosPorCategoria(Request $request): JsonResponse
    {
        $anio = (int) $request->input('anio');
        $mes = (int) $request->input('mes');

        if ($anio < 2000 || $anio > 2100) {
            return response()->json([
                'error' => 'El año indicado no es válido.',
            ], 422);
        }

        if ($mes < 1 || $mes > 12) {
            return response()->json([
                'error' => 'El mes indicado no es válido.',
            ], 422);
        }

        return response()->json(
            $this->estadisticas->egresosPorCategoria($anio, $mes)
        );
    }
    public function resumenFinanciero(Request $request)
{
    $limite = (int) $request->input('limite', 6);

    return response()->json([
        'success' => true,
        'estadistica' => $this->estadisticas->resumenFinanciero($limite),
    ]);
}
}