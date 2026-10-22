<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Estadisticas\ZoeEstadisticasService;
use App\Services\Zoe\ZoeConversationMemoryService;
use App\Services\Zoe\ZoeQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Periodo;
use App\Services\ZoeEmergenciaService;
use App\Services\ZoeDirectivaService;
use Throwable;

class ZoeAiController extends Controller
{
    /**
     * Obtener resumen de un período.
     */
    public function resumenPeriodo(
        Request $request,
        ZoeQueryService $zoeQuery
    ) {
        $datos = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        try {
            $resultado = $zoeQuery->resumenPeriodo(
                (int) $datos['anio'],
                (int) $datos['mes']
            );

            if ($resultado === null) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No existe información para el período solicitado.',
                    'resultado' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'resultado' => $resultado,
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'mensaje' => 'No fue posible consultar el resumen del período.',
            ], 500);
        }
    }

/**
 * Obtener resumen del período activo de SIGEFIV.
 */
public function resumenPeriodoActivo(
    ZoeQueryService $zoeQuery
) {
    try {
        $periodo = Periodo::obtenerAbierto();

        if (!$periodo) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No existe un período abierto en SIGEFIV.',
                'resultado' => null,
            ], 404);
        }

        $resultado = $zoeQuery->resumenPeriodo(
            (int) $periodo->anio,
            (int) $periodo->mes
        );

        if ($resultado === null) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se pudo obtener el resumen del período activo.',
                'resultado' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'resultado' => $resultado,
        ]);

    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'mensaje' => 'No fue posible consultar el resumen del período activo.',
        ], 500);
    }
}


    /**
 * Obtener movimientos.
 */
public function movimientos(
    Request $request,
    ZoeQueryService $zoeQuery
) {
    $datos = $request->validate([
        'mes' => ['nullable', 'integer', 'between:1,12'],
        'anio' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        'tipo' => ['nullable', 'in:Ingreso,Egreso'],
        'categoria' => ['nullable', 'string', 'max:100'],
        'concepto' => ['nullable', 'string', 'max:150'],
        'persona' => ['nullable', 'string', 'max:150'],
        'forma_pago' => ['nullable', 'string', 'max:100'],
        'limite' => ['nullable', 'integer', 'between:1,100'],
        'resumen' => ['nullable', 'boolean'],
    ]);

    try {
        $filtros = [
            'mes' => $datos['mes'] ?? null,
            'anio' => $datos['anio'] ?? null,
            'tipo_movimiento' => $datos['tipo'] ?? null,
            'categoria' => $datos['categoria'] ?? null,
            'concepto' => $datos['concepto'] ?? null,
            'persona' => $datos['persona'] ?? null,
            'forma_pago' => $datos['forma_pago'] ?? null,
            'limite' => $datos['limite'] ?? 100,
        ];

        $filtros = array_filter(
            $filtros,
            fn ($valor) => $valor !== null && $valor !== ''
        );

        $movimientos = $zoeQuery->listarMovimientos($filtros);

        // Modo resumen: devolver solamente cantidad y total
        if (($datos['resumen'] ?? false) === true) {

            $total = $movimientos->sum(
                fn ($movimiento) => (float) ($movimiento->monto ?? 0)
            );

            return response()->json([
                'success' => true,
                'resultado' => [
                    'cantidad' => $movimientos->count(),
                    'total' => round($total, 2),
                ],
            ]);
        }

        // Modo detalle: devolver movimientos completos
        return response()->json([
            'success' => true,
            'resultado' => $movimientos,
            'total' => $movimientos->count(),
        ]);

    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'mensaje' => 'No fue posible consultar los movimientos.',
        ], 500);
    }
}


    /**
     * Obtener egresos agrupados por categoría.
     */
    public function egresosPorCategoria(
        Request $request,
        ZoeEstadisticasService $estadisticas
    ) {
        $datos = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        try {
            $resultado = $estadisticas->egresosPorCategoria(
                (int) $datos['anio'],
                (int) $datos['mes']
            );

            return response()->json([
                'success' => true,
                'resultado' => $resultado,
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'mensaje' => 'No fue posible consultar los egresos por categoría.',
            ], 500);
        }
    }


    /**
     * Obtener el contexto persistente de ZOE.
     */
    public function obtenerContexto(
        Request $request,
        ZoeConversationMemoryService $memoria
    ) {
        $datos = $request->validate([
            'usuario_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
        ]);

        return response()->json([
            'success' => true,
            'contexto' => $memoria->obtener(
                (int) $datos['usuario_id']
            ),
        ]);
    }


    /**
     * Actualizar el contexto persistente de ZOE.
     */
    public function actualizarContexto(
        Request $request,
        ZoeConversationMemoryService $memoria
    ) {
        $datos = $request->validate([
            'usuario_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'contexto' => [
                'required',
                'array',
            ],
        ]);

        $contexto = $memoria->actualizar(
            (int) $datos['usuario_id'],
            $datos['contexto']
        );

        return response()->json([
            'success' => true,
            'contexto' => $contexto,
        ]);
    }


    public function chat(Request $request)
{
    $datos = $request->validate([
        'usuario_id' => [
            'required',
            'integer',
            'exists:users,id',
        ],
        'nombre_usuario' => [
            'required',
            'string',
            'max:150',
        ],
        'mensaje' => [
            'required',
            'string',
            'max:2000',
        ],
    ]);

    try {
        $webhook = config('services.n8n.zoe_webhook_url');

        $response = Http::timeout(120)
            ->connectTimeout(10)
            ->acceptJson()
            ->asJson()
            ->post($webhook, [
                'usuario_id' => (int) $datos['usuario_id'],
                'nombre_usuario' => $datos['nombre_usuario'],
                'mensaje' => $datos['mensaje'],
            ]);

        if (!$response->successful()) {
            report(
                new \RuntimeException(
                    'n8n ZOE respondió con HTTP ' . $response->status()
                )
            );

            return response()->json([
                'success' => false,
                'mensaje' => 'ZOE AI no pudo procesar la consulta.',
            ], 502);
        }

        $respuesta = $response->json();

        return response()->json(
            is_array($respuesta)
                ? $respuesta
                : [
                    'success' => false,
                    'mensaje' => 'Respuesta inválida de ZOE AI.',
                ]
        );

    } catch (Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'mensaje' => 'No fue posible comunicarse con ZOE AI.',
        ], 502);
    }
}

/**
 * Obtener únicamente el saldo en caja del período abierto.
 */
public function saldoCajaActivo(
    \App\Services\Zoe\ZoeQueryService $zoeQuery
) {
    try {
        $periodo = \App\Models\Periodo::obtenerAbierto();

        if (!$periodo) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No existe un período abierto en SIGEFIV.',
            ], 404);
        }

        $resultado = $zoeQuery->resumenPeriodo(
            (int) $periodo->anio,
            (int) $periodo->mes
        );

        if ($resultado === null) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se pudo consultar el saldo en caja.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'saldo_caja' => (float) $resultado['saldo_final'],
        ]);

    } catch (\Throwable $e) {
        report($e);

        return response()->json([
            'success' => false,
            'mensaje' => 'No fue posible consultar el saldo en caja.',
        ], 500);
    }
}
// ... tus otros métodos (movimientos, resumen, estatutos, etc.)

public function obtenerEmergencias(Request $request, ZoeEmergenciaService $emergenciaService)
{
    $consulta = $request->input('consulta');
    $respuesta = $emergenciaService->obtenerNumerosEmergencia($consulta);

    return response()->json([
        'success' => true,
        'respuesta' => $respuesta,
    ]);
}

  public function obtenerDirectiva(Request $request, ZoeDirectivaService $directivaService)
{
    // El servicio actual no requiere filtrar por consulta, devuelve toda la directiva
    $respuesta = $directivaService->obtenerInformacionDirectiva();

    return response()->json([
        'success' => true,
        'respuesta' => $respuesta,
    ]);
}
}