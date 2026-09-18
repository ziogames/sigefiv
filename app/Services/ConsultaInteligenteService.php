<?php

namespace App\Services;

use App\Services\ConsultaInteligente\ConsultaInteligenteCategoriaService;
use App\Services\ConsultaInteligente\ConsultaInteligenteConceptoService;
use App\Services\ConsultaInteligente\ConsultaInteligenteFechaService;
use App\Services\ConsultaInteligente\ConsultaInteligenteOperacionService;
use App\Services\ConsultaInteligente\ConsultaInteligenteTablaService;
use Illuminate\Support\Facades\Cache;

class ConsultaInteligenteService
{
    public function __construct(
        private readonly ConsultaInteligenteTablaService $tablaService,
        private readonly ConsultaInteligenteOperacionService $operacionService,
        private readonly ConsultaInteligenteFechaService $fechaService,
        private readonly ConsultaInteligenteCategoriaService $categoriaService,
        private readonly ConsultaInteligenteConceptoService $conceptoService,
    ) {
    }

    public function interpretar(string $consulta, ?int $usuarioId = null): array
    {
        $usuarioId = $usuarioId ?? auth()->id();

        // 1. Ejecutar el análisis base
        $interpretacionActual = $this->procesarAnalisisBase($consulta);

        // 2. Gestionar la memoria de contexto del usuario
        $contextKey = $usuarioId
            ? "sigi_zoe_context_user_{$usuarioId}"
            : null;

        $contextoPrevio = $contextKey
            ? Cache::get($contextKey, [])
            : [];

        // 3. Fusionar contexto de forma inteligente
        $interpretacionFinal = $this->fusionarContexto(
            $interpretacionActual,
            $contextoPrevio
        );

        // 4. Guardar el nuevo estado exitoso en la memoria
        if (
            $contextKey &&
            (
                !empty($interpretacionFinal['fecha']['anio']) ||
                !empty($interpretacionFinal['fecha']['mes']) ||
                !empty($interpretacionFinal['tipo_movimiento'])
            )
        ) {
            Cache::put(
                $contextKey,
                [
                    'tabla' => $interpretacionFinal['tabla'],
                    'operacion' => $interpretacionFinal['operacion'],
                    'fecha' => $interpretacionFinal['fecha'],
                    'tipo_movimiento' => $interpretacionFinal['tipo_movimiento'],
                    'categoria' => $interpretacionFinal['categoria'] ?? null,
                    'categorias' => $interpretacionFinal['categorias'] ?? [],
                    'concepto' => $interpretacionFinal['concepto'] ?? null,
                    'conceptos' => $interpretacionFinal['conceptos'] ?? [],
                    'usar_periodo_activo' => $interpretacionFinal['usar_periodo_activo'] ?? false,
                ],
                now()->addMinutes(15)
            );
        }

        return $interpretacionFinal;
    }

    private function procesarAnalisisBase(string $consulta): array
    {
        $texto = mb_strtolower(
            trim($consulta)
        );

        $tabla = $this->tablaService->detectarTabla($texto);
        $operacion = $this->operacionService->detectarOperacion($texto);

        /*
        |--------------------------------------------------------------------------
        | DETECCIÓN EXPLÍCITA DE LISTADOS
        |--------------------------------------------------------------------------
        */

        if (
            (
                str_contains($texto, 'lista') ||
                str_contains($texto, 'listado') ||
                str_contains($texto, 'muéstrame') ||
                str_contains($texto, 'muestrame') ||
                str_contains($texto, 'ver') ||
                str_contains($texto, 'dame los') ||
                str_contains($texto, 'dame la')
            ) &&
            (
                str_contains($texto, 'ingreso') ||
                str_contains($texto, 'ingresos') ||
                str_contains($texto, 'egreso') ||
                str_contains($texto, 'egresos') ||
                str_contains($texto, 'gasto') ||
                str_contains($texto, 'gastos') ||
                str_contains($texto, 'movimiento') ||
                str_contains($texto, 'movimientos')
            ) &&
            !str_contains($texto, 'total')
        ) {
            $operacion = 'show';
        }

        /*
        |--------------------------------------------------------------------------
        | ÚLTIMOS MOVIMIENTOS
        |--------------------------------------------------------------------------
        */

        $limiteSolicitado = null;
        $tipoMovimiento = null;

        if (
            preg_match(
                '/\b(?:los\s+)?(?:ultimo|último|ultimos|últimos)\s+(?:ingreso|ingresos|egreso|egresos|gasto|gastos|movimiento|movimientos|movs?)\b/u',
                $texto
            )
        ) {
            $operacion = 'show';
            $limiteSolicitado = 1;

            if (preg_match('/\b(?:ultimo|último)\s+(?:ingreso|egreso|gasto|movimiento)\b/u', $texto)) {
                if (preg_match('/\b(?:ingreso)\b/u', $texto)) {
                    $tipoMovimiento = 'Ingreso';
                } elseif (preg_match('/\b(?:egreso|gasto)\b/u', $texto)) {
                    $tipoMovimiento = 'Egreso';
                }
            }

            if (
                !preg_match('/\b20\d{2}\b/u', $texto) &&
                !preg_match(
                    '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                    $texto
                )
            ) {
                $texto .= ' periodo actual';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | DETECCIÓN DEL TIPO DE MOVIMIENTO
        |--------------------------------------------------------------------------
        |
        | INGRESOS
        |
        | Reconocemos:
        | ingreso
        | ingresos
        | ingresamos
        | ingresó
        | ingresaron
        | ingresaba
        | ingresaban
        | ingresado
        | ingresada
        | ingresados
        | ingresadas
        | recaudación
        | recaudamos
        | recaudó
        | recaudaron
        |
        | EGRESOS
        |
        | Reconocemos:
        | egreso
        | egresos
        | gasto
        | gastos
        | gastamos
        | gastó
        | gastaron
        |
        */

        if (
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresamos') ||
            str_contains($texto, 'ingresó') ||
            str_contains($texto, 'ingresaron') ||
            str_contains($texto, 'ingresaba') ||
            str_contains($texto, 'ingresaban') ||
            str_contains($texto, 'ingresado') ||
            str_contains($texto, 'ingresada') ||
            str_contains($texto, 'ingresados') ||
            str_contains($texto, 'ingresadas') ||
            str_contains($texto, 'recaud')
        ) {
            $tipoMovimiento = 'Ingreso';

        } elseif (
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastamos') ||
            str_contains($texto, 'gastó') ||
            str_contains($texto, 'gastaron')
        ) {
            $tipoMovimiento = 'Egreso';
        }

        /*
        |--------------------------------------------------------------------------
        | ÚLTIMOS N MOVIMIENTOS
        |--------------------------------------------------------------------------
        */

        // Últimos N movimientos/ingresos/egresos.
        // El número se conserva como límite y estas consultas siempre usan
        // el período activo cuando el usuario no indicó un mes/año.
        if (
            preg_match(
                '/\b(?:los\s+)?(?:ultimos|últimos)\s+(\d+)\s+(?:movimientos?|movs?|ingresos?|egresos?|gastos?)\b/u',
                $texto,
                $coincidencia
            )
        ) {
            $limiteSolicitado = max(1, min(100, (int) $coincidencia[1]));
            $operacion = 'show';

            if (preg_match('/\b(?:ingreso|ingresos)\b/u', $texto)) {
                $tipoMovimiento = 'Ingreso';
            } elseif (preg_match('/\b(?:egreso|egresos|gasto|gastos)\b/u', $texto)) {
                $tipoMovimiento = 'Egreso';
            }

            if (
                !preg_match('/\b20\d{2}\b/u', $texto) &&
                !preg_match(
                    '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                    $texto
                )
            ) {
                $texto .= ' periodo actual';
            }
        }

        // Consultas naturales de gasto/pago en el período actual.
        $esConsultaGastoActual =
            preg_match(
                '/\b(?:en\s+que|en\s+qué)\s+(?:gastamos|gast[oó]|se\s+gast[oó]|hemos\s+gastado)\b/u',
                $texto
            ) === 1 ||
            preg_match(
                '/\b(?:cuanto|cuánto)\s+(?:hemos\s+gastado|gastamos|gast[oó])\b/u',
                $texto
            ) === 1;

        $esUltimoPago =
            preg_match(
                '/\b(?:ultimo|último)\s+(?:pago|pagado|gasto|egreso)\b/u',
                $texto
            ) === 1;

        // "pago" en consultas como "¿Quién hizo el último pago?"
        // identifica el último movimiento de tipo Egreso. No debe
        // interpretarse como la categoría "Pago" ni como la tabla "periodos".

        if ($esConsultaGastoActual) {
            $tipoMovimiento = 'Egreso';
            $operacion = preg_match('/\b(?:cuanto|cuánto)\b/u', $texto)
                ? 'sum'
                : 'show';

            if (!preg_match('/\b20\d{2}\b/u', $texto) && !preg_match(
                '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                $texto
            )) {
                $texto .= ' periodo actual';
            }
        }

        if ($esUltimoPago) {
            $tipoMovimiento = 'Egreso';
            $operacion = 'show';
            $limiteSolicitado = 1;

            if (!preg_match('/\b20\d{2}\b/u', $texto) && !preg_match(
                '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                $texto
            )) {
                $texto .= ' periodo actual';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTAS DE USUARIOS / ROLES
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $tabla,
                ['usuarios', 'roles'],
                true
            ) &&
            $operacion === 'conversation'
        ) {
            $esConsultaDirecta =
                str_contains($texto, 'muéstrame') ||
                str_contains($texto, 'muestrame') ||
                str_contains($texto, 'mostrar') ||
                str_contains($texto, 'dame') ||
                str_contains($texto, 'lista') ||
                str_contains($texto, 'listado') ||
                str_contains($texto, 'quiénes') ||
                str_contains($texto, 'quienes') ||
                str_contains($texto, 'qué usuarios') ||
                str_contains($texto, 'que usuarios') ||
                str_contains($texto, 'qué roles') ||
                str_contains($texto, 'que roles') ||
                str_contains($texto, 'usuarios del sistema') ||
                str_contains($texto, 'roles del sistema') ||
                str_contains($texto, 'usuarios existen') ||
                str_contains($texto, 'roles existen');

            if ($esConsultaDirecta) {
                $operacion = 'show';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTAS DE USUARIOS POR ROL
        |--------------------------------------------------------------------------
        */

        if (
            $this->tablaService->esConsultaUsuariosPorRol($texto)
        ) {
            $tabla = 'usuarios';

            if (
                $this->tablaService->esConsultaCantidad($texto)
            ) {
                $operacion = 'count';

            } elseif (
                $operacion === 'conversation' ||
                $this->tablaService->esConsultaListaUsuarios($texto)
            ) {
                $operacion = 'show';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORÍAS
        |--------------------------------------------------------------------------
        */

        $categoriasDetectadas =
            $this->categoriaService->detectarCategorias($texto);

        $esTotalGeneralMovimiento =
            $operacion === 'sum' &&
            (
                preg_match(
                    '/\btotal\s+(?:de\s+)?(?:ingresos?|egresos?|gastos?)\b/u',
                    $texto
                ) === 1
            );

        if (!empty($categoriasDetectadas)) {
            $tiposCategorias = array_values(
                array_unique(
                    array_filter(
                        array_map(
                            fn ($categoria) =>
                                $categoria['tipo'] ?? null,
                            $categoriasDetectadas
                        ),
                        fn ($tipo) =>
                            $tipo === 'Ingreso' ||
                            $tipo === 'Egreso'
                    )
                )
            );

            if (
                count($tiposCategorias) === 1 &&
                $tipoMovimiento === null
            ) {
                $tipoMovimiento = $tiposCategorias[0];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTRO LIMPIADOR DE CONCEPTOS
        |
        | Remueve palabras conversacionales para que no sean
        | detectadas como conceptos.
        |--------------------------------------------------------------------------
        */

        $textoParaConcepto = preg_replace(
            '/\b(?:cuanto|cuánto|tenemos|tambien|también|dame|muestrame|muéstrame|mostrar|lista|listado|total|ver)\b/u',
            '',
            $texto
        );

        /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        */

        $fechaDetectada =
            $this->fechaService->detectarFecha($texto);

        /*
        |--------------------------------------------------------------------------
        | PERÍODO ACTIVO PARA CONSULTAS FINANCIERAS NATURALES
        |--------------------------------------------------------------------------
        |
        | Si el usuario no indicó un mes/año concreto, no debemos heredar
        | accidentalmente el período de una consulta anterior. El ejecutor
        | resolverá el período activo real de SIGEFIV.
        */

        $tienePeriodoExplicito =
            preg_match('/\b20\d{2}\b/u', $texto) === 1 ||
            preg_match(
                '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                $texto
            ) === 1;

        $esConsultaFinancieraNatural =
            preg_match(
                '/\b(?:cuanto|cuánto)\s+(?:hemos\s+)?(?:gastado|gastamos|ingresado|ingresamos|recaudamos)\b/u',
                $texto
            ) === 1 ||
            preg_match(
                '/\b(?:en\s+que|en\s+qué)\s+(?:gastamos|gastó|se\s+gastó|hemos\s+gastado)\b/u',
                $texto
            ) === 1 ||
            preg_match(
                '/\b(?:qué|que)\s+(?:ingresos|egresos|gastos)\s+(?:tuvimos|hubo)\b/u',
                $texto
            ) === 1 ||
            preg_match(
                '/\b(?:quién|quien)\s+(?:hizo|realizó|realizo|efectuó|efectuo)\s+(?:el\s+)?(?:último|ultimo)\s+pago\b/u',
                $texto
            ) === 1 ||
            preg_match(
                '/\b(?:último|ultimo)\s+pago\b/u',
                $texto
            ) === 1 ||
            preg_match(
                '/\b(?:este\s+mes|periodo\s+actual|período\s+actual)\b/u',
                $texto
            ) === 1;

        if ($esConsultaFinancieraNatural && !$tienePeriodoExplicito) {
            $interpretacionFechaActual = $fechaDetectada ?? [];
            $interpretacionFechaActual['mes'] = null;
            $interpretacionFechaActual['anio'] = null;
            $interpretacionFechaActual['mes_desde'] = null;
            $interpretacionFechaActual['mes_hasta'] = null;
            $fechaDetectada = $interpretacionFechaActual;
            $usarPeriodoActivo = true;
        } else {
            $usarPeriodoActivo = false;
        }


        /*
        |--------------------------------------------------------------------------
        | NORMALIZACIÓN DE "ÚLTIMO PAGO"
        |--------------------------------------------------------------------------
        |
        | La detección de categorías puede reconocer "pago" como la
        | categoría Pago y el detector de tabla puede haber elegido
        | "periodos". Para esta intención, la consulta debe ir siempre
        | a movimientos: último egreso del período correspondiente.
        */

        if ($esUltimoPago) {
            $tabla = 'movimientos';
            $operacion = 'show';
            $tipoMovimiento = 'Egreso';
            $categoriasDetectadas = [];
        }

        /*
        |--------------------------------------------------------------------------
        | RESULTADO FINAL
        |--------------------------------------------------------------------------
        */

        return [
            'consulta_original' => $consulta,

            'tabla' => $tabla,

            'operacion' => $operacion,

            'fecha' => $fechaDetectada,

            'categoria' =>
                $categoriasDetectadas[0] ?? null,

            'categorias' =>
                $categoriasDetectadas,

            'concepto' =>
                $esTotalGeneralMovimiento
                    ? null
                    : (
                        !empty($categoriasDetectadas)
                            ? null
                            : $this->conceptoService->detectarConcepto(
                                $textoParaConcepto,
                                $operacion,
                                $fechaDetectada
                            )
                    ),

            'conceptos' =>
                $esTotalGeneralMovimiento
                    ? []
                    : (
                        !empty($categoriasDetectadas)
                            ? []
                            : $this->conceptoService->detectarConceptos(
                                $textoParaConcepto,
                                $operacion,
                                $fechaDetectada
                            )
                    ),

            'tipo_movimiento' => $tipoMovimiento,

            'limite' => $limiteSolicitado,

            'usar_periodo_activo' => $usarPeriodoActivo ?? false,

            'texto' => $texto,
        ];
    }

    private function fusionarContexto(
        array $actual,
        array $previo
    ): array {
        if (empty($previo)) {
            return $actual;
        }

        $resultado = $actual;

        $textoActual =
            trim($resultado['texto']);

        /*
        |--------------------------------------------------------------------------
        | DETECCIÓN DE SEGUIMIENTO CORTO
        |--------------------------------------------------------------------------
        */

        $esSeguimientoCorto =
            str_starts_with($textoActual, 'y ') ||
            str_starts_with($textoActual, 'tambien ') ||
            str_starts_with($textoActual, 'también ') ||
            str_starts_with($textoActual, 'o ') ||
            str_starts_with($textoActual, 'sólo ') ||
            str_starts_with($textoActual, 'solo ');

        if (
            $esSeguimientoCorto &&
            !empty($previo['operacion'])
        ) {
            $resultado['operacion'] =
                $previo['operacion'];
        }

        /*
        |--------------------------------------------------------------------------
        | CONTEXTO DE FECHA
        |--------------------------------------------------------------------------
        |
        | Las consultas normales pueden heredar año/mes de una consulta
        | anterior. Pero una consulta explícita de "últimos movimientos"
        | debe empezar desde el período activo de SIGEFIV, salvo que el
        | usuario haya indicado un período concreto.
        |--------------------------------------------------------------------------
        */

        $esUltimosMovimientos =
            preg_match(
                '/\b(?:ultimos|últimos)\s+(?:(?:\d+)\s+)?(?:movimientos?|movs?)\b/u',
                $textoActual
            ) === 1;

        $tieneFechaExplicitaActual =
            preg_match('/\b20\d{2}\b/u', $textoActual) === 1 ||
            preg_match(
                '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                $textoActual
            ) === 1;

        if ($esUltimosMovimientos && !$tieneFechaExplicitaActual) {
            // No heredamos la fecha anterior.
            // El ejecutor resolverá el período activo real de SIGEFIV.
            $resultado['fecha'] = [];
            $resultado['usar_periodo_activo'] = true;
        } else {

            /*
             |--------------------------------------------------------------------------
             | HEREDAR AÑO
             |--------------------------------------------------------------------------
             */

            if (
                empty($resultado['fecha']['anio']) &&
                !empty($previo['fecha']['anio'])
            ) {
                $resultado['fecha']['anio'] =
                    $previo['fecha']['anio'];
            }

            /*
             |--------------------------------------------------------------------------
             | HEREDAR MES
             |--------------------------------------------------------------------------
             */

            $tieneMesActual =
                !empty($resultado['fecha']['mes']) ||
                !empty($resultado['fecha']['meses']) ||
                !empty($resultado['fecha']['mes_desde']);

            if (
                !$tieneMesActual &&
                !empty($previo['fecha'])
            ) {
                if (
                    empty($resultado['fecha']['mes']) &&
                    !empty($previo['fecha']['mes'])
                ) {
                    $resultado['fecha']['mes'] =
                        $previo['fecha']['mes'];
                }

                if (
                    empty($resultado['fecha']['meses']) &&
                    !empty($previo['fecha']['meses'])
                ) {
                    $resultado['fecha']['meses'] =
                        $previo['fecha']['meses'];
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | HEREDAR TIPO DE MOVIMIENTO
        |--------------------------------------------------------------------------
        |
        | "últimos movimientos" incluye ingresos y egresos y no debe
        | heredar el tipo de una consulta anterior.
        |--------------------------------------------------------------------------
        */

        if (
            !$esUltimosMovimientos &&
            empty($resultado['tipo_movimiento']) &&
            !empty($previo['tipo_movimiento'])
        ) {
            $resultado['tipo_movimiento'] =
                $previo['tipo_movimiento'];
        }

        /*
        |--------------------------------------------------------------------------
        | HEREDAR TABLA
        |--------------------------------------------------------------------------
        */

        if (
            empty($resultado['tabla']) &&
            !empty($previo['tabla'])
        ) {
            $resultado['tabla'] =
                $previo['tabla'];
        }

        /*
        |--------------------------------------------------------------------------
        | IMPORTANTE
        |
        | NO heredamos conceptos ni categorías.
        | Esto evita que una consulta anterior contamine
        | una consulta nueva.
        |--------------------------------------------------------------------------
        */

        return $resultado;
    }
}