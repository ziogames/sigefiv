<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            ], 404);
        }

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

        ]);
    }
}