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

        if (
            preg_match(
                '/\b(?:los\s+)?(?:ultimos|últimos)\s+(?:ingresos?|egresos?|gastos?|movimientos?|movs?)\b/u',
                $texto
            )
        ) {
            $operacion = 'show';

            if (
                !preg_match('/\b20\d{2}\b/u', $texto) &&
                !preg_match(
                    '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                    $texto
                ) &&
                !str_contains($texto, 'ultimo periodo') &&
                !str_contains($texto, 'último periodo') &&
                !str_contains($texto, 'ultimo período') &&
                !str_contains($texto, 'último período') &&
                !str_contains($texto, 'periodo actual') &&
                !str_contains($texto, 'período actual') &&
                !str_contains($texto, 'periodo vigente') &&
                !str_contains($texto, 'período vigente')
            ) {
                $texto .= ' periodo actual';
            }
        }

        $limiteSolicitado = null;
        $tipoMovimiento = null;

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

        if (
            preg_match(
                '/\b(?:ultimos|últimos)\s+(\d+)\s+(?:movimientos?|movs?|ingresos?|egresos?)\b/u',
                $texto,
                $coincidencia
            )
        ) {
            $limiteSolicitado = max(
                1,
                min(100, (int) $coincidencia[1])
            );

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
                str_contains($texto, 'ingresadas')
            ) {
                $tipoMovimiento = 'Ingreso';

            } elseif (
                str_contains($texto, 'egreso') ||
                str_contains($texto, 'egresos') ||
                str_contains($texto, 'gasto') ||
                str_contains($texto, 'gastos') ||
                str_contains($texto, 'gastamos') ||
                str_contains($texto, 'gastó') ||
                str_contains($texto, 'gastaron')
            ) {
                $tipoMovimiento = 'Egreso';
            }

            $operacion = 'show';

            $texto = preg_replace(
                '/\b((?:ultimos|últimos)\s+\d+)\s+(?:ingresos?|egresos?)\b/u',
                '$1 movimientos',
                $texto
            );

            if (
                !preg_match('/\b20\d{2}\b/u', $texto) &&
                !preg_match(
                    '/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u',
                    $texto
                ) &&
                !str_contains($texto, 'ultimo periodo') &&
                !str_contains($texto, 'último periodo') &&
                !str_contains($texto, 'periodo actual') &&
                !str_contains($texto, 'período actual')
            ) {
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
            (
                $resultado['tabla'] === 'movimientos' ||
                empty($resultado['tabla'])
            ) &&
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