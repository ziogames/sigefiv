<?php

namespace App\Http\Controllers;

use App\Services\ConsultaEjecutorService;
use App\Services\ConsultaInteligenteService;
use Illuminate\Http\Request;

class ConsultaInteligenteController extends Controller
{
    public function consultar(
        Request $request,
        ConsultaInteligenteService $interprete,
        ConsultaEjecutorService $ejecutor
    ) {
        $request->validate([
            'consulta' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $consulta = trim(
            $request->input('consulta')
        );


        /*
        |--------------------------------------------------------------------------
        | 1. INTERPRETAR LA PREGUNTA
        |--------------------------------------------------------------------------
        */

        $interpretacion =
            $interprete->interpretar(
                $consulta
            );


        /*
        |--------------------------------------------------------------------------
        | 2. EJECUTAR LA CONSULTA
        |--------------------------------------------------------------------------
        */

        $resultado =
            $ejecutor->ejecutar(
                $interpretacion
            );


        /*
        |--------------------------------------------------------------------------
        | 3. DEVOLVER EL RESULTADO
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                $resultado['success'] ?? false,

            'consulta' =>
                $consulta,

            'interpretacion' =>
                $interpretacion,

            'resultado' =>
                $resultado['resultado'] ?? null,

            'tipo' =>
                $resultado['tipo'] ?? null,

            'mensaje' =>
                $resultado['mensaje']
                ?? 'Consulta procesada.',

        ]);
    }
}