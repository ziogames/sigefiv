<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Estadisticas\ZoeEstadisticasService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoeEstadisticasBridgeController extends Controller
{
    public function __construct(
        private ZoeEstadisticasService $estadisticas
    ) {
    }

    public function consultar(Request $request): JsonResponse
    {
        $tipo = $request->input('tipo');

        switch ($tipo) {

            case 'ingresos_vs_egresos':

                $limite = (int) $request->input('limite', 6);

                return response()->json([
                    'success' => true,
                    'estadistica' => $this->estadisticas
                        ->ingresosVsEgresos($limite),
                ]);

            case 'evolucion_saldo':

                $limite = (int) $request->input('limite', 6);

                return response()->json([
                    'success' => true,
                    'estadistica' => $this->estadisticas
                        ->evolucionSaldo($limite),
                ]);

            case 'egresos_por_categoria':

                $anio = (int) $request->input('anio');
                $mes = (int) $request->input('mes');

                if ($anio < 2000 || $anio > 2100) {
                    return response()->json([
                        'success' => false,
                        'error' => 'El año indicado no es válido.',
                    ], 422);
                }

                if ($mes < 1 || $mes > 12) {
                    return response()->json([
                        'success' => false,
                        'error' => 'El mes indicado no es válido.',
                    ], 422);
                }

                return response()->json([
                    'success' => true,
                    'estadistica' => $this->estadisticas
                        ->egresosPorCategoria($anio, $mes),
                ]);

            default:

                return response()->json([
                    'success' => false,
                    'error' => 'Tipo de estadística no reconocido.',
                ], 422);
        }
    }
}