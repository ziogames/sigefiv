<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use App\Services\Estadisticas\ZoeEstadisticasService;
use App\Services\ZoeDirectivaService;
use App\Services\ZoeEmergenciaService;
use App\Services\Zoe\ZoeConversationMemoryService;
use App\Services\Zoe\ZoeQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Services\Zoe\ZoeContextDetectorService;
use Throwable;

class ZoeAiController extends Controller
{
    public function resumenPeriodo(
        Request $request,
        ZoeQueryService $zoeQuery
    ) {
        $datos = $request->validate([
            'mes' => ['required', 'integer', 'between:1,12'],
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        try {
            $mes = (int) $datos['mes'];
            $anio = (int) $datos['anio'];

            $ahoraLima = Carbon::now('America/Lima');
            $esFuturo = ($anio > (int) $ahoraLima->year) || 
                        ($anio === (int) $ahoraLima->year && $mes > (int) $ahoraLima->month);

            // Si el período aún no inicia, informar explícitamente sin consultar saldos ni inventar datos
            if ($esFuturo) {
                $nombreMes = Carbon::createFromDate($anio, $mes, 1)->locale('es')->translatedFormat('F');
                return response()->json([
                    'success' => true,
                    'es_futuro' => true,
                    'periodo_abierto' => false,
                    'resultado' => null,
                    'mensaje' => "El mes de {$nombreMes} de {$anio} aún no ha comenzado y no cuenta con registros de ingresos, egresos ni balance en SIGEFIV."
                ]);
            }

            $resultado = $zoeQuery->resumenPeriodo($anio, $mes);

            if ($resultado === null) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No existe información para el período solicitado.',
                    'resultado' => null,
                ], 404);
            }

            // Consultar el estado real del período en la base de datos
            $periodo = DB::table('periodos')
                ->where('mes', $mes)
                ->where('anio', $anio)
                ->first();

            $esMesActual = ($mes === (int) $ahoraLima->month && $anio === (int) $ahoraLima->year);
            $estadoPeriodo = strtolower((string) ($periodo->estado ?? ''));
            $periodoAbierto = ($estadoPeriodo === 'abierto' || $estadoPeriodo === 'en_curso' || ($periodo === null && $esMesActual));

            // Representación explícita de montos formateados
            $saldoInicial = (float) ($resultado['saldo_inicial'] ?? 0);
            $ingresos = (float) ($resultado['total_ingresos'] ?? 0);
            $egresos = (float) ($resultado['total_egresos'] ?? 0);
            $saldoFinal = (float) ($resultado['saldo_final'] ?? 0);

            $resultado['saldo_inicial_formateado'] = 'S/ ' . number_format($saldoInicial, 2, '.', ',');
            $resultado['total_ingresos_formateado'] = 'S/ ' . number_format($ingresos, 2, '.', ',');
            $resultado['total_egresos_formateado'] = 'S/ ' . number_format($egresos, 2, '.', ',');
            $resultado['saldo_final_formateado'] = 'S/ ' . number_format($saldoFinal, 2, '.', ',');

            // Metadatos de control para ZOE y clientes
            $resultado['estado_periodo'] = $periodo->estado ?? ($periodoAbierto ? 'abierto' : 'cerrado');
            $resultado['periodo_abierto'] = $periodoAbierto;
            $resultado['es_mes_actual'] = $esMesActual;
            $resultado['es_futuro'] = false;
            $resultado['etiqueta_saldo'] = $periodoAbierto ? 'Saldo al momento' : 'Saldo final';

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

            $saldoInicial = (float) ($resultado['saldo_inicial'] ?? 0);
            $ingresos = (float) ($resultado['total_ingresos'] ?? 0);
            $egresos = (float) ($resultado['total_egresos'] ?? 0);
            $saldoFinal = (float) ($resultado['saldo_final'] ?? 0);

            $resultado['saldo_inicial_formateado'] = 'S/ ' . number_format($saldoInicial, 2, '.', ',');
            $resultado['total_ingresos_formateado'] = 'S/ ' . number_format($ingresos, 2, '.', ',');
            $resultado['total_egresos_formateado'] = 'S/ ' . number_format($egresos, 2, '.', ',');
            $resultado['saldo_final_formateado'] = 'S/ ' . number_format($saldoFinal, 2, '.', ',');

            $resultado['estado_periodo'] = $periodo->estado ?? 'abierto';
            $resultado['periodo_abierto'] = true;
            $resultado['es_mes_actual'] = ((int) $periodo->mes === (int) Carbon::now('America/Lima')->month && (int) $periodo->anio === (int) Carbon::now('America/Lima')->year);
            $resultado['es_futuro'] = false;
            $resultado['etiqueta_saldo'] = 'Saldo al momento';

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

    public function movimientos(
        Request $request,
        ZoeQueryService $zoeQuery
    ) {
        if ($request->has('tipo') && is_string($request->input('tipo'))) {
            $tipoTexto = strtolower($request->input('tipo'));
            if (str_contains($tipoTexto, 'ingreso')) {
                $request->merge(['tipo' => 'Ingreso']);
            } elseif (str_contains($tipoTexto, 'egreso') || str_contains($tipoTexto, 'gasto')) {
                $request->merge(['tipo' => 'Egreso']);
            } else {
                $request->merge(['tipo' => null]);
            }
        }

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
            $mes = $datos['mes'] ?? null;
            $anio = $datos['anio'] ?? null;

            if ($mes !== null && $anio !== null) {
                $ahoraLima = Carbon::now('America/Lima');
                $esFuturo = ((int) $anio > (int) $ahoraLima->year) || 
                            ((int) $anio === (int) $ahoraLima->year && (int) $mes > (int) $ahoraLima->month);

                if ($esFuturo) {
                    $nombreMes = Carbon::createFromDate((int) $anio, (int) $mes, 1)->locale('es')->translatedFormat('F');
                    return response()->json([
                        'success' => true,
                        'cantidad' => 0,
                        'total' => 0.0,
                        'total_formateado' => 'S/ 0.00',
                        'resultado' => [],
                        'mensaje' => "El mes de {$nombreMes} de {$anio} aún no ha comenzado y no cuenta con movimientos registrados."
                    ]);
                }
            }

            $filtros = [
                'mes' => $mes,
                'anio' => $anio,
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

            $totalMonto = $movimientos->sum(
                fn ($m) => (float) ($m->monto ?? 0)
            );

            if (($datos['resumen'] ?? false) === true) {
                return response()->json([
                    'success' => true,
                    'resultado' => [
                        'cantidad' => $movimientos->count(),
                        'total' => round($totalMonto, 2),
                        'total_formateado' => 'S/ ' . number_format($totalMonto, 2, '.', ','),
                    ],
                ]);
            }

            $movimientosLigeros = $movimientos->map(function ($m) {
                $fechaFormateada = null;
                if (!empty($m->fecha)) {
                    $fechaFormateada = is_string($m->fecha)
                        ? Carbon::parse($m->fecha)->format('d/m')
                        : $m->fecha->format('d/m');
                }

                return [
                    'fecha' => $fechaFormateada,
                    'concepto' => $m->concepto ?? 'Sin concepto',
                    'monto' => (float) ($m->monto ?? 0),
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'cantidad' => count($movimientosLigeros),
                'total' => round($totalMonto, 2),
                'total_formateado' => 'S/ ' . number_format($totalMonto, 2, '.', ','),
                'resultado' => $movimientosLigeros,
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'mensaje' => 'No fue posible consultar los movimientos.',
            ], 500);
        }
    }

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

    public function saldoCajaActivo(
        ZoeQueryService $zoeQuery
    ) {
        try {
            $periodo = Periodo::obtenerAbierto();

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
                'saldo_caja_formateado' => 'S/ ' . number_format((float) $resultado['saldo_final'], 2, '.', ','),
                'periodo_abierto' => true,
                'es_futuro' => false,
            ]);

        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'mensaje' => 'No fue posible consultar el saldo en caja.',
            ], 500);
        }
    }

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
        $respuesta = $directivaService->obtenerInformacionDirectiva();

        return response()->json([
            'success' => true,
            'respuesta' => $respuesta,
        ]);
    }

    public function saldoCaja(Request $request)
    {
        $mes = $request->input('mes');
        $anio = $request->input('anio');

        $now = Carbon::now('America/Lima');
        if (!$mes || !$anio) {
            $mes = $mes ?? $now->month;
            $anio = $anio ?? $now->year;
        }

        $mes = (int) $mes;
        $anio = (int) $anio;

        $esFuturo = ($anio > (int) $now->year) || 
                    ($anio === (int) $now->year && $mes > (int) $now->month);

        if ($esFuturo) {
            $nombreMes = Carbon::createFromDate($anio, $mes, 1)->locale('es')->translatedFormat('F');
            return response()->json([
                'success' => true,
                'resultado' => null,
                'es_futuro' => true,
                'mensaje' => "El período de {$nombreMes} de {$anio} aún no ha iniciado y no cuenta con saldo registrado en caja."
            ]);
        }

        $periodo = DB::table('periodos')
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        if (!$periodo) {
            return response()->json([
                'success' => true,
                'resultado' => null,
                'mensaje' => 'No encontré un período registrado para esa fecha.'
            ]);
        }

        $esMesActual = ($mes === (int) $now->month && $anio === (int) $now->year);
        $estadoPeriodo = strtolower((string) ($periodo->estado ?? ''));
        $periodoAbierto = ($estadoPeriodo === 'abierto' || $estadoPeriodo === 'en_curso' || $esMesActual);

        $saldoFinal = (float) $periodo->saldo_final;

        return response()->json([
            'success' => true,
            'resultado' => [
                'mes' => (int) $periodo->mes,
                'anio' => (int) $anio,
                'nombre_mes' => $periodo->nombre,
                'saldo_inicial' => (float) $periodo->saldo_inicial,
                'saldo_inicial_formateado' => 'S/ ' . number_format((float) $periodo->saldo_inicial, 2, '.', ','),
                'total_ingresos' => (float) $periodo->total_ingresos,
                'total_ingresos_formateado' => 'S/ ' . number_format((float) $periodo->total_ingresos, 2, '.', ','),
                'total_egresos' => (float) $periodo->total_egresos,
                'total_egresos_formateado' => 'S/ ' . number_format((float) $periodo->total_egresos, 2, '.', ','),
                'saldo' => $saldoFinal,
                'saldo_formateado' => 'S/ ' . number_format($saldoFinal, 2, '.', ','),
                'estado' => $periodo->estado,
                'periodo_abierto' => $periodoAbierto,
                'es_mes_actual' => $esMesActual,
                'es_futuro' => false,
                'etiqueta_saldo' => $periodoAbierto ? 'Saldo al momento' : 'Saldo final'
            ]
        ]);
    }



// Agrega estos métodos al final de la clase:

public function prepararContexto(
    Request $request,
    ZoeConversationMemoryService $memoria,
    ZoeContextDetectorService $detector
) {
    $datos = $request->validate([
        'usuario_id' => ['required', 'integer'],
        'mensaje'    => ['required', 'string'],
    ]);

    $usuarioId = (int) $datos['usuario_id'];
    $contextoPrevio = $memoria->obtener($usuarioId);

    $analisis = $detector->analizar($datos['mensaje'], $contextoPrevio);

    return response()->json([
        'success' => true,
        'usuario_id' => $usuarioId,
        'dominio' => $analisis['dominio'],
        'intencion' => $analisis['intencion'],
        'mes' => $analisis['mes'],
        'anio' => $analisis['anio'],
        'categoria' => $analisis['categoria'],
        'palabras_busqueda' => $analisis['palabras_busqueda'],
        'fecha_actual' => $analisis['fecha_actual'],
        'hora_actual' => $analisis['hora_actual'],
        'contexto_previo' => $contextoPrevio,
    ]);
}

public function guardarContexto(
    Request $request,
    ZoeConversationMemoryService $memoria
) {
    $datos = $request->validate([
        'usuario_id' => ['required', 'integer'],
        'dominio'    => ['nullable', 'string'],
        'intencion'  => ['nullable', 'string'],
        'mes'        => ['nullable', 'integer', 'between:1,12'],
        'anio'       => ['nullable', 'integer', 'min:2000', 'max:2100'],
    ]);

    $usuarioId = (int) $datos['usuario_id'];

    $actualizaciones = [
        'ultimo_dominio'   => $datos['dominio'] ?? null,
        'ultima_intencion' => $datos['intencion'] ?? null,
        'ultimo_mes'       => $datos['mes'] ?? null,
        'ultimo_anio'      => $datos['anio'] ?? null,
        'actualizado_en'   => Carbon::now('America/Lima')->toDateTimeString(),
    ];

    $contextoActualizado = $memoria->actualizar(
        $usuarioId,
        array_filter($actualizaciones, fn($v) => $v !== null)
    );

    return response()->json([
        'success' => true,
        'contexto' => $contextoActualizado,
    ]);
}
}