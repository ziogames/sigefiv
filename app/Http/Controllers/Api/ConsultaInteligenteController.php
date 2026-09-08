<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConsultaEjecutorService;
use App\Services\ConsultaInteligenteService;
use App\Services\SigiMemoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultaInteligenteController extends Controller
{
    /**
     * Procesar una consulta de ZOE desde la aplicación Android.
     *
     * Este endpoint reutiliza exactamente los servicios de ZOE
     * que utiliza la versión web de SIGEFIV.
     *
     * La autenticación se realiza mediante Laravel Sanctum.
     * El usuario se obtiene desde $request->user(), nunca desde
     * un usuario_id enviado por Android.
     */
    public function consultar(
        Request $request,
        ConsultaInteligenteService $interprete,
        ConsultaEjecutorService $ejecutor,
        SigiMemoriaService $memoria
    ): JsonResponse {
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
        | USUARIO AUTENTICADO
        |--------------------------------------------------------------------------
        |
        | Sanctum identifica al usuario mediante el Bearer Token.
        | Nunca confiamos en un usuario_id enviado desde Android.
        |
        */

        $usuarioId =
            $request->user()?->id;

        /*
        |--------------------------------------------------------------------------
        | MEMORIA EXPLÍCITA DE ZOE
        |--------------------------------------------------------------------------
        */

        $memoriaGuardada =
            $memoria->detectarYRecordar(
                $usuarioId,
                $consulta
            );

        if ($memoriaGuardada !== null) {

            return response()->json([

                'success' => true,

                'consulta' =>
                    $consulta,

                'interpretacion' => [
                    'operacion' => 'memory',
                ],

                'resultado' => null,

                'tipo' => 'memory',

                'mensaje' =>
                    'Perfecto. Lo recordaré para futuras conversaciones.',

            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | INTERPRETAR CONSULTA
        |--------------------------------------------------------------------------
        |
        | La inteligencia de ZOE permanece en los servicios existentes.
        |
        */

        $interpretacion =
            $interprete->interpretar(
                $consulta
            );

        /*
        |--------------------------------------------------------------------------
        | EJECUTAR CONSULTA
        |--------------------------------------------------------------------------
        |
        | Se entrega el usuario autenticado para que las comprobaciones
        | de seguridad de ZOE se mantengan activas.
        |
        */

        $resultado =
            $ejecutor->ejecutar(
                $interpretacion,
                $usuarioId
            );

        /*
        |--------------------------------------------------------------------------
        | RESPUESTA
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                $resultado['success']
                ?? false,

            'consulta' =>
                $consulta,

            'interpretacion' =>
                $interpretacion,

            'resultado' =>
                $resultado['resultado']
                ?? null,

            'tipo' =>
                $resultado['tipo']
                ?? null,

            'mensaje' =>
                $resultado['mensaje']
                ?? 'Consulta procesada.',

        ]);
    }
}
