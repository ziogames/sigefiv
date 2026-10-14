<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Sigi\SigiEstatutosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZoeEstatutosController extends Controller
{
    public function consultar(
        Request $request,
        SigiEstatutosService $estatutos
    ): JsonResponse {
        $consulta = trim(
            (string) $request->input('consulta', '')
        );

        if ($consulta === '') {
            return response()->json([
                'success' => false,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' => 'No se recibió ninguna consulta.',
            ], 422);
        }

        return response()->json(
            $estatutos->consultar($consulta)
        );
    }
}