<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sigi\SigiClimaService;
use Illuminate\Http\Request;
use Throwable;

class ClimaController extends Controller
{
    public function obtener(Request $request, SigiClimaService $climaService)
    {
        try {
            $ubicacion = $request->input(
                'ubicacion',
                env('WEATHER_DEFAULT_LOCATION', 'Lima, Peru')
            );

            $resultado = $climaService->obtener([
                'ubicacion' => $ubicacion,
            ]);

            return response()->json($resultado);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'tipo' => 'clima',
                'resultado' => null,
                'mensaje' => 'No fue posible consultar el clima.',
            ], 500);
        }
    }
}