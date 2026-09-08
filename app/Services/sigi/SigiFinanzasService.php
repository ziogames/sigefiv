<?php

namespace App\Services\Sigi;

use App\Models\ZoeMovimiento;
use App\Models\Periodo;
use Illuminate\Support\Facades\DB;

class SigiFinanzasService
{
    public function consultarMovimientos(
        array $interpretacion,
        string $operacion
    ): array {
        $consulta = ZoeMovimiento::query()
            ->with('categoria');

        /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        */

        $fecha =
            $interpretacion['fecha'] ?? [];

        $mes =
            $fecha['mes'] ?? null;

        $meses =
            $fecha['meses'] ?? [];

        if (!is_array($meses)) {
            $meses = [];
        }

        $meses = array_values(
            array_unique(
                array_map(
                    'intval',
                    array_filter(
                        $meses,
                        fn ($valor) => (int) $valor >= 1 && (int) $valor <= 12
                    )
                )
            )
        );

        sort($meses);

        /*
        |--------------------------------------------------------------------------
        | RESPALDO: DETECTAR VARIOS MESES DESDE EL TEXTO ORIGINAL
        |--------------------------------------------------------------------------
        */

        $textoFecha =
            mb_strtolower(
                $interpretacion['texto'] ?? ''
            );

        $nombresMesesConsulta = [
            'enero'      => 1,
            'febrero'    => 2,
            'marzo'      => 3,
            'abril'      => 4,
            'mayo'       => 5,
            'junio'      => 6,
            'julio'      => 7,
            'agosto'     => 8,
            'septiembre' => 9,
            'setiembre'  => 9,
            'octubre'    => 10,
            'noviembre'  => 11,
            'diciembre'  => 12,
        ];

        if (count($meses) <= 1) {
            $mesesDetectados = [];

            foreach (
                $nombresMesesConsulta
                as $nombreMesConsulta => $numeroMesConsulta
            ) {
                if (
                    str_contains(
                        $textoFecha,
                        $nombreMesConsulta
                    )
                ) {
                    $mesesDetectados[] =
                        $numeroMesConsulta;
                }
            }

            if (count($mesesDetectados) > 1) {
                $meses =
                    array_values(
                        array_unique(
                            $mesesDetectados
                        )
                    );

                sort($meses);
            }
        }

        $mesDesde =
            $fecha['mes_desde'] ?? null;

        $mesHasta =
            $fecha['mes_hasta'] ?? null;

        $anio =
            $fecha['anio'] ?? null;

        if ($anio === null && ($mes !== null || $mesDesde !== null || count($meses) > 0)) {
            $anio = (int) date('Y');
        }

        /*
        |--------------------------------------------------------------------------
        | RANGO DE MESES
        |--------------------------------------------------------------------------
        */

        if (
            $mesDesde !== null &&
            $mesHasta !== null &&
            $anio !== null
        ) {
            $fechaInicio =
                sprintf(
                    '%04d-%02d-01',
                    $anio,
                    $mesDesde
                );

            $fechaFin =
                date(
                    'Y-m-t',
                    strtotime(
                        sprintf(
                            '%04d-%02d-01',
                            $anio,
                            $mesHasta
                        )
                    )
                );

            $consulta->whereBetween(
                'fecha',
                [
                    $fechaInicio,
                    $fechaFin,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MESES ESPECÍFICOS
        |--------------------------------------------------------------------------
        */

        elseif (
            count($meses) > 1 &&
            $anio !== null
        ) {
            $consulta->whereYear(
                'fecha',
                $anio
            );

            $consulta->whereIn(
                \Illuminate\Support\Facades\DB::raw(
                    'EXTRACT(MONTH FROM fecha)'
                ),
                $meses
            );
        }

       /*
        |--------------------------------------------------------------------------
        | MES INDIVIDUAL
        |--------------------------------------------------------------------------
        */

      elseif (
            $mes !== null &&
            $anio !== null
        ) {
            $consulta->where('anio', $anio)
                     ->where('mes', $mes);
        }
        /*
        |--------------------------------------------------------------------------
        | MES SIN AÑO
        |--------------------------------------------------------------------------
        */

        elseif ($mes !== null) {
            $consulta->whereMonth(
                'fecha',
                $mes
            );
        }

        /*
        |--------------------------------------------------------------------------
        | AÑO SIN MES
        |--------------------------------------------------------------------------
        */

        elseif ($anio !== null) {
            $consulta->whereYear(
                'fecha',
                $anio
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TEXTO ORIGINAL Y LÍMITES DINÁMICOS
        |--------------------------------------------------------------------------
        */

        $texto =
            mb_strtolower(
                $interpretacion['texto'] ?? ''
            );

        $esUltimoPeriodo =
            str_contains($texto, 'ultimo periodo') ||
            str_contains($texto, 'último periodo') ||
            str_contains($texto, 'ultimo período') ||
            str_contains($texto, 'último período') ||
            str_contains($texto, 'periodo actual') ||
            str_contains($texto, 'período actual') ||
            str_contains($texto, 'periodo vigente') ||
            str_contains($texto, 'período vigente');

        $limiteSolicitado = null;

        // Detectar "ultimos X movimientos/ingresos/egresos/gastos"
        if (
            preg_match(
                '/\b(?:ultimos|últimos)\s+(\d+)\s+(?:movimientos?|movs?|ingresos?|egresos?|gastos?)\b/u',
                $texto,
                $coincidencia
            )
        ) {
            $limiteSolicitado = max(1, min(100, (int) $coincidencia[1]));
        } elseif (
            preg_match(
                '/\b(\d+)\s+(?:ultimos|últimos)\s+(?:movimientos?|movs?|ingresos?|egresos?|gastos?)\b/u',
                $texto,
                $coincidencia
            )
        ) {
            $limiteSolicitado = max(1, min(100, (int) $coincidencia[1]));
        }

        $esUltimoMovimientoPorTipo =
            str_contains($texto, 'ultimos ingresos') ||
            str_contains($texto, 'últimos ingresos') ||
            str_contains($texto, 'ultimos egresos') ||
            str_contains($texto, 'últimos egresos') ||
            str_contains($texto, 'ultimos gastos') ||
            str_contains($texto, 'últimos gastos');

        $esUltimosMovimientos =
            preg_match(
                '/\b(?:los\s+)?(?:ultimos|últimos)\s+(?:movimientos?|movs?)\b/u',
                $texto
            ) === 1;

        // =========================================================================
        // ESCENARIO 1: LÍMITES DINÁMICOS (ej: "últimos 5 movimientos") -> FILTRADO POR FECHA DEL PERÍODO ABIERTO
        // =========================================================================
        if ($limiteSolicitado !== null) {
            $periodoAbierto = Periodo::obtenerAbierto() ?? Periodo::query()
                ->orderByDesc('anio')
                ->orderByDesc('mes')
                ->first();

            $nombrePeriodo = 'actual';

            if ($periodoAbierto) {
                // FILTRADO ESTRICTO POR AÑO Y MES DE FECHA (Evita depender de periodo_id roto)
                $consulta->whereYear('fecha', $periodoAbierto->anio)
                         ->whereMonth('fecha', $periodoAbierto->mes);
                $nombrePeriodo = $periodoAbierto->nombre_completo;
            }

            $tipoMovimientoInterpretado = $interpretacion['tipo_movimiento'] ?? null;
            if ($tipoMovimientoInterpretado === 'Ingreso' || $tipoMovimientoInterpretado === 'Egreso') {
                $consulta->where('tipo', $tipoMovimientoInterpretado);
            }

            $resultado = $consulta->orderByDesc('id')->limit($limiteSolicitado)->get();
            $cantidadEncontrada = $resultado->count();

            $tipoMensaje = 'movimientos';
            if ($tipoMovimientoInterpretado === 'Ingreso') $tipoMensaje = 'ingresos';
            if ($tipoMovimientoInterpretado === 'Egreso') $tipoMensaje = 'egresos';

            return [
                'success' => true,
                'tipo' => 'lista',
                'resultado' => $resultado,
                'mensaje' => 'Estos son los últimos ' . $cantidadEncontrada . ' ' . $tipoMensaje . ' del período ' . $nombrePeriodo . '.',
            ];
        }

        // =========================================================================
        // ESCENARIO 2: "ÚLTIMOS MOVIMIENTOS" (sin número) -> FILTRADO POR FECHA DEL PERÍODO ABIERTO
        // =========================================================================
        if ($esUltimoPeriodo || $esUltimoMovimientoPorTipo || $esUltimosMovimientos) {
            $periodoAbierto = Periodo::obtenerAbierto() ?? Periodo::query()
                ->orderByDesc('anio')
                ->orderByDesc('mes')
                ->first();

            $nombrePeriodo = 'actual';

            if ($periodoAbierto) {
                $consulta->whereYear('fecha', $periodoAbierto->anio)
                         ->whereMonth('fecha', $periodoAbierto->mes);
                $nombrePeriodo = $periodoAbierto->nombre_completo;
            }

            $tipoMovimientoInterpretado = $interpretacion['tipo_movimiento'] ?? null;
            if (
                $esUltimoMovimientoPorTipo && 
                ($tipoMovimientoInterpretado === 'Ingreso' || $tipoMovimientoInterpretado === 'Egreso')
            ) {
                $consulta->where('tipo', $tipoMovimientoInterpretado);
            }

            $resultado = $consulta->orderByDesc('id')->limit(20)->get();

            $tipoMensaje = 'movimientos';
            if ($tipoMovimientoInterpretado === 'Ingreso') $tipoMensaje = 'ingresos';
            if ($tipoMovimientoInterpretado === 'Egreso') $tipoMensaje = 'egresos';

            return [
                'success' => true,
                'tipo' => 'lista',
                'resultado' => $resultado,
                'mensaje' => 'Estos son los últimos ' . $resultado->count() . ' ' . $tipoMensaje . ' del período ' . $nombrePeriodo . '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | TIPO DE MOVIMIENTO
        |--------------------------------------------------------------------------
        */

        $tipoMovimiento =
            $interpretacion['tipo_movimiento'] ?? null;

        if (
            $tipoMovimiento === 'Ingreso' ||
            $tipoMovimiento === 'Egreso'
        ) {
            $consulta->where(
                'tipo',
                $tipoMovimiento
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORÍA
        |--------------------------------------------------------------------------
        */

        $categorias =
            $interpretacion['categorias'] ?? [];

        if (
            is_array($categorias) &&
            !empty($categorias)
        ) {
            $idsCategorias =
                array_values(
                    array_unique(
                        array_filter(
                            array_map(
                                fn ($categoria) => $categoria['id'] ?? null,
                                $categorias
                            ),
                            fn ($id) => $id !== null
                        )
                    )
                );

            if (!empty($idsCategorias)) {
                $consulta->whereIn(
                    'categoria_id',
                    $idsCategorias
                );
            }
        } else {
            $categoria =
                $interpretacion['categoria'] ?? null;

            if (
                $categoria &&
                isset($categoria['id'])
            ) {
                $consulta->where(
                    'categoria_id',
                    $categoria['id']
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CONCEPTO / MÚLTIPLES CONCEPTOS
        |--------------------------------------------------------------------------
        */

        $conceptos =
            $interpretacion['conceptos'] ?? [];

        if (
            is_array($conceptos) &&
            !empty($conceptos)
        ) {
            $conceptos =
                array_values(
                    array_filter(
                        array_map(
                            fn ($conceptoMultiple) =>
                                is_string($conceptoMultiple)
                                    ? trim($conceptoMultiple)
                                    : '',
                            $conceptos
                        ),
                        fn ($conceptoMultiple) =>
                            $conceptoMultiple !== ''
                    )
                );

            if (!empty($conceptos)) {
                $consulta->where(
                    function ($query) use ($conceptos) {
                        foreach (
                            $conceptos
                            as $indice => $conceptoMultiple
                        ) {
                            if ($indice === 0) {
                                $query->where(
                                    'concepto',
                                    'like',
                                    '%' . $conceptoMultiple . '%'
                                );
                            } else {
                                $query->orWhere(
                                    'concepto',
                                    'like',
                                    '%' . $conceptoMultiple . '%'
                                );
                            }
                        }
                    }
                );
            }
        } else {
            $concepto =
                $interpretacion['concepto'] ?? null;

            if ($concepto) {
                $consulta->where(
                    'concepto',
                    'like',
                    '%' . $concepto . '%'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MESES DE UN CONCEPTO (Compatible con PostgreSQL)
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'meses_concepto') {

            $resultados =
                $consulta
                    ->selectRaw(
                        'EXTRACT(YEAR FROM fecha) as anio,
                         EXTRACT(MONTH FROM fecha) as mes,
                         COUNT(*) as cantidad,
                         SUM(monto) as total'
                    )
                    ->groupByRaw(
                        'EXTRACT(YEAR FROM fecha),
                         EXTRACT(MONTH FROM fecha)'
                    )
                    ->orderByRaw(
                        'EXTRACT(YEAR FROM fecha),
                         EXTRACT(MONTH FROM fecha)'
                    )
                    ->get();

            if ($resultados->isEmpty()) {
                return [
                    'success' => true,
                    'tipo' => 'texto',
                    'resultado' => [],
                    'mensaje' =>
                        'No encontré pagos que coincidan con el concepto consultado.',
                ];
            }

            $nombreMeses = [
                '1' => 'Enero',
                '2' => 'Febrero',
                '3' => 'Marzo',
                '4' => 'Abril',
                '5' => 'Mayo',
                '6' => 'Junio',
                '7' => 'Julio',
                '8' => 'Agosto',
                '9' => 'Septiembre',
                '10' => 'Octubre',
                '11' => 'Noviembre',
                '12' => 'Diciembre',
            ];

            $conceptoTexto =
                $interpretacion['concepto']
                ?? 'concepto solicitado';

            $html =
                '<div class="consulta-meses-concepto">' .
                '<strong>Encontré pagos de ' .
                htmlspecialchars(
                    $conceptoTexto,
                    ENT_QUOTES,
                    'UTF-8'
                ) .
                ' en:</strong><br><br>';

            $datos = [];

            foreach ($resultados as $fila) {

                $mesCodigo =
                    (string) (int) $fila->mes;

                $nombreMes =
                    $nombreMeses[$mesCodigo]
                    ?? 'Mes desconocido';

                $anioResultado =
                    (int) $fila->anio;

                $cantidad =
                    (int) $fila->cantidad;

                $total =
                    (float) $fila->total;

                $datos[] = [
                    'mes' =>
                        $nombreMes,
                    'anio' =>
                        $anioResultado,
                    'cantidad' =>
                        $cantidad,
                    'total' =>
                        $total,
                ];

                $html .=
                    '<div style="margin-bottom:8px;">' .
                    '📅 <strong>' .
                    $nombreMes .
                    ' ' .
                    $anioResultado .
                    '</strong>' .
                    ' — S/ ' .
                    number_format(
                        $total,
                        2,
                        '.',
                        ','
                    ) .
                    ' (' .
                    $cantidad .
                    (
                        $cantidad === 1
                            ? ' pago'
                            : ' pagos'
                    ) .
                    ')' .
                    '</div>';
            }

            $html .=
                '</div>';

            return [
                'success' => true,
                'tipo' => 'texto',
                'resultado' =>
                    $datos,
                'mensaje' =>
                    $html,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MES CON MAYORES EGRESOS (Compatible con PostgreSQL)
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'max_mes') {

            $anio =
                $interpretacion['fecha']['anio']
                ?? null;

            if (!$anio) {
                return [
                    'success' => false,
                    'tipo' => 'texto',
                    'resultado' => null,
                    'mensaje' =>
                        'Necesito que indiques el año que deseas consultar.',
                ];
            }

            $resultados =
                ZoeMovimiento::query()
                    ->where('tipo', 'Egreso')
                    ->whereYear('fecha', $anio)
                    ->selectRaw(
                        'EXTRACT(MONTH FROM fecha) as mes,
                         SUM(monto) as total'
                    )
                    ->groupByRaw(
                        'EXTRACT(MONTH FROM fecha)'
                    )
                    ->orderByDesc('total')
                    ->first();

            if (!$resultados) {
                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => 0,
                    'mensaje' =>
                        'No encontré egresos registrados durante ' .
                        $anio .
                        '.',
                ];
            }

            $mes =
                (int) $resultados->mes;

            $total =
                (float) $resultados->total;

            $nombreMeses = [
                1 => 'enero',
                2 => 'febrero',
                3 => 'marzo',
                4 => 'abril',
                5 => 'mayo',
                6 => 'junio',
                7 => 'julio',
                8 => 'agosto',
                9 => 'septiembre',
                10 => 'octubre',
                11 => 'noviembre',
                12 => 'diciembre',
            ];

            $nombreMes =
                $nombreMeses[$mes]
                ?? 'mes desconocido';

            return [
                'success' => true,
                'tipo' => 'numero',
                'resultado' => $total,
                'mensaje' =>
                    'El mes con mayores egresos en ' .
                    $anio .
                    ' fue ' .
                    $nombreMes .
                    ', con un total de S/ ' .
                    number_format(
                        $total,
                        2,
                        '.',
                        ','
                    ) .
                    '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MES CON MENORES EGRESOS (Compatible con PostgreSQL)
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'min_mes') {

            $anio =
                $interpretacion['fecha']['anio']
                ?? null;

            if (!$anio) {
                return [
                    'success' => false,
                    'tipo' => 'texto',
                    'resultado' => null,
                    'mensaje' =>
                        'Necesito que indiques el año que deseas consultar.',
                ];
            }

            $resultado =
                ZoeMovimiento::query()
                    ->where('tipo', 'Egreso')
                    ->whereYear('fecha', $anio)
                    ->selectRaw(
                        'EXTRACT(MONTH FROM fecha) as mes,
                         SUM(monto) as total'
                    )
                    ->groupByRaw(
                        'EXTRACT(MONTH FROM fecha)'
                    )
                    ->orderBy('total')
                    ->first();

            if (!$resultado) {
                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => 0,
                    'mensaje' =>
                        'No encontré egresos registrados durante ' .
                        $anio .
                        '.',
                ];
            }

            $mes =
                (int) $resultado->mes;

            $total =
                (float) $resultado->total;

            $nombreMeses = [
                1 => 'enero',
                2 => 'febrero',
                3 => 'marzo',
                4 => 'abril',
                5 => 'mayo',
                6 => 'junio',
                7 => 'julio',
                8 => 'agosto',
                9 => 'septiembre',
                10 => 'octubre',
                11 => 'noviembre',
                12 => 'diciembre',
            ];

            $nombreMes =
                $nombreMeses[$mes]
                ?? 'mes desconocido';

            return [
                'success' => true,
                'tipo' => 'numero',
                'resultado' => $total,
                'mensaje' =>
                    'El mes con menores egresos en ' .
                    $anio .
                    ' fue ' .
                    $nombreMes .
                    ', con un total de S/ ' .
                    number_format(
                        $total,
                        2,
                        '.',
                        ','
                    ) .
                    '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | SUMA
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'sum') {
            $tipoMovimiento =
                $interpretacion['tipo_movimiento'] ?? null;

            $esIngreso =
                $tipoMovimiento === 'Ingreso';

            $esEgreso =
                $tipoMovimiento === 'Egreso';

            $categoriasInterpretadas =
                $interpretacion['categorias'] ?? [];

            if (
                is_array($categoriasInterpretadas) &&
                count($categoriasInterpretadas) > 1
            ) {

                $totalesCategorias =
                    (clone $consulta)
                        ->select('categoria_id')
                        ->selectRaw('SUM(monto) as total')
                        ->groupBy('categoria_id')
                        ->get()
                        ->keyBy('categoria_id');

                $resultado = 0.0;
                $detalleCategorias = [];

                foreach ($categoriasInterpretadas as $categoriaInterpretada) {

                    $categoriaId =
                        $categoriaInterpretada['id'] ?? null;

                    if ($categoriaId === null) {
                        continue;
                    }

                    $totalCategoria =
                        (float) ($totalesCategorias[$categoriaId]->total ?? 0);

                    $resultado += $totalCategoria;

                    $detalleCategorias[] = [
                        'nombre' =>
                            $categoriaInterpretada['nombre'] ?? 'Categoría',
                        'tipo' =>
                            $categoriaInterpretada['tipo'] ?? null,
                        'total' =>
                            $totalCategoria,
                    ];
                }

                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => $resultado,
                    'mensaje' =>
                        $this->generarMensajeSumaCategorias(
                            $resultado,
                            $detalleCategorias,
                            $interpretacion,
                            $esIngreso,
                            $esEgreso
                        ),
                ];
            }

            $resultado =
                (float) $consulta->sum(
                    'monto'
                );

            return [
                'success' => true,
                'tipo' => 'numero',
                'resultado' =>
                    $resultado,
                'mensaje' =>
                    $this->generarMensajeSumaMovimiento(
                        $resultado,
                        $interpretacion,
                        $esIngreso,
                        $esEgreso
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | CANTIDAD
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'count') {

            $resultado =
                $consulta->count();

            return [
                'success' => true,
                'tipo' => 'numero',
                'resultado' =>
                    $resultado,
                'mensaje' =>
                    'Encontré ' .
                    $resultado .
                    ' movimientos que coinciden con tu consulta.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PROMEDIO
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'avg') {

            $resultado =
                (float) (
                    $consulta->avg('monto')
                    ?? 0
                );

            return [
                'success' => true,
                'tipo' => 'numero',
                'resultado' =>
                    $resultado,
                'mensaje' =>
                    'El promedio de los movimientos encontrados es S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) . '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MAYOR INGRESO
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'max_mes_ingreso') {

            if (
                $mes !== null &&
                $anio !== null
            ) {

                $movimiento =
                    ZoeMovimiento::query()
                        ->with('categoria')
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->whereMonth('fecha', $mes)
                        ->orderByDesc('monto')
                        ->orderBy('fecha')
                        ->first();

                if (!$movimiento) {
                    return [
                        'success' => true,
                        'tipo' => 'lista',
                        'resultado' => collect(),
                        'mensaje' =>
                            'No encontré ingresos en ' .
                            $this->nombreMes(
                                $mes
                            ) .
                            ' de ' .
                            $anio .
                            '.',
                    ];
                }

                $monto =
                    (float) $movimiento->monto;

                return [
                    'success' => true,
                    'tipo' => 'lista',
                    'resultado' =>
                        collect([
                            $movimiento
                        ]),
                    'mensaje' =>
                        'El mayor ingreso de ' .
                        $this->nombreMes(
                            $mes
                        ) .
                        ' de ' .
                        $anio .
                        ' fue de S/ ' .
                        number_format(
                            $monto,
                            2,
                            '.',
                            ','
                        ) .
                        '.',
                ];
            }

            if ($anio !== null) {

                $movimientos =
                    ZoeMovimiento::query()
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->get();

                if ($movimientos->isEmpty()) {
                    return [
                        'success' => true,
                        'tipo' => 'texto',
                        'resultado' => null,
                        'mensaje' =>
                            'No encontré ingresos registrados durante ' .
                            $anio .
                            '.',
                    ];
                }

                $totalesPorMes = [];

                foreach ($movimientos as $movimiento) {
                    $mesMovimiento =
                        (int) $movimiento->fecha->month;

                    if (!isset($totalesPorMes[$mesMovimiento])) {
                        $totalesPorMes[$mesMovimiento] = 0;
                    }

                    $totalesPorMes[$mesMovimiento] +=
                        (float) $movimiento->monto;
                }

                $mayor =
                    max($totalesPorMes);

                $mesesEncontrados = [];

                foreach ($totalesPorMes as $mesMovimiento => $total) {
                    if ((float) $total === (float) $mayor) {
                        $mesesEncontrados[] =
                            $this->nombreMes(
                                (int) $mesMovimiento
                            );
                    }
                }

                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => $mayor,
                    'mensaje' =>
                        'Los meses con mayores ingresos en ' .
                        $anio .
                        ' fueron ' .
                        implode(', ', $mesesEncontrados) .
                        ', con S/ ' .
                        number_format(
                            $mayor,
                            2,
                            '.',
                            ','
                        ) .
                        ' en cada mes.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MENOR INGRESO
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'min_mes_ingreso') {

            if (
                $mes !== null &&
                $anio !== null
            ) {

                $movimiento =
                    ZoeMovimiento::query()
                        ->with('categoria')
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->whereMonth('fecha', $mes)
                        ->orderBy('monto')
                        ->orderBy('fecha')
                        ->first();

                if (!$movimiento) {
                    return [
                        'success' => true,
                        'tipo' => 'lista',
                        'resultado' => collect(),
                        'mensaje' =>
                            'No encontré ingresos en ' .
                            $this->nombreMes(
                                $mes
                            ) .
                            ' de ' .
                            $anio .
                            '.',
                    ];
                }

                $monto =
                    (float) $movimiento->monto;

                return [
                    'success' => true,
                    'tipo' => 'lista',
                    'resultado' =>
                        collect([
                            $movimiento
                        ]),
                    'mensaje' =>
                        'El menor ingreso de ' .
                        $this->nombreMes(
                            $mes
                        ) .
                        ' de ' .
                        $anio .
                        ' fue de S/ ' .
                        number_format(
                            $monto,
                            2,
                            '.',
                            ','
                        ) .
                        '.',
                ];
            }

            if ($anio !== null) {

                $movimientos =
                    ZoeMovimiento::query()
                        ->where('tipo', 'Ingreso')
                        ->whereYear('fecha', $anio)
                        ->get();

                if ($movimientos->isEmpty()) {
                    return [
                        'success' => true,
                        'tipo' => 'texto',
                        'resultado' => null,
                        'mensaje' =>
                            'No encontré ingresos registrados durante ' .
                            $anio .
                            '.',
                    ];
                }

                $totalesPorMes = [];

                foreach ($movimientos as $movimiento) {
                    $mesMovimiento =
                        (int) $movimiento->fecha->month;

                    if (!isset($totalesPorMes[$mesMovimiento])) {
                        $totalesPorMes[$mesMovimiento] = 0;
                    }

                    $totalesPorMes[$mesMovimiento] +=
                        (float) $movimiento->monto;
                }

                $menor =
                    min($totalesPorMes);

                $mesesEncontrados = [];

                foreach ($totalesPorMes as $mesMovimiento => $total) {
                    if ((float) $total === (float) $menor) {
                        $mesesEncontrados[] =
                            $this->nombreMes(
                                (int) $mesMovimiento
                            );
                    }
                }

                return [
                    'success' => true,
                    'tipo' => 'numero',
                    'resultado' => $menor,
                    'mensaje' =>
                        'Los meses con menores ingresos en ' .
                        $anio .
                        ' fueron ' .
                        implode(', ', $mesesEncontrados) .
                        ', con S/ ' .
                        number_format(
                            $menor,
                            2,
                            '.',
                            ','
                        ) .
                        ' en cada mes.',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MAYOR
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'max') {

            $movimiento =
                (clone $consulta)
                    ->with('categoria')
                    ->orderByDesc('monto')
                    ->orderBy('fecha')
                    ->first();

            if (!$movimiento) {
                return [
                    'success' => true,
                    'tipo' => 'lista',
                    'resultado' => collect(),
                    'mensaje' =>
                        'No encontré movimientos que coincidan con tu consulta.',
                ];
            }

            $resultado =
                (float) $movimiento->monto;

            return [
                'success' => true,
                'tipo' => 'lista',
                'resultado' =>
                    collect([
                        $movimiento
                    ]),
                'mensaje' =>
                    'El movimiento de mayor monto encontrado fue de S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | MENOR
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'min') {

            $movimiento =
                (clone $consulta)
                    ->with('categoria')
                    ->orderBy('monto')
                    ->orderBy('fecha')
                    ->first();

            if (!$movimiento) {
                return [
                    'success' => true,
                    'tipo' => 'lista',
                    'resultado' => collect(),
                    'mensaje' =>
                        'No encontré movimientos que coincidan con tu consulta.',
                ];
            }

            $resultado =
                (float) $movimiento->monto;

            return [
                'success' => true,
                'tipo' => 'lista',
                'resultado' =>
                    collect([
                        $movimiento
                    ]),
                'mensaje' =>
                    'El movimiento de menor monto encontrado fue de S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    '.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | LISTADO
        |--------------------------------------------------------------------------
        */

        $resultado =
            $consulta
                ->orderBy('fecha', 'desc')
                ->get();

        $cantidadMovimientos =
            $resultado->count();

        return [
            'success' => true,
            'tipo' => 'lista',
            'resultado' =>
                $resultado,
            'mensaje' =>
                'Encontré ' .
                $cantidadMovimientos .
                ' movimientos que coinciden con tu consulta.',
        ];
    }

    private function generarMensajeSumaCategorias(
        float $resultado,
        array $detalleCategorias,
        array $interpretacion,
        bool $esIngreso,
        bool $esEgreso
    ): string {
        $fecha =
            $interpretacion['fecha'] ?? [];

        $mes =
            $fecha['mes'] ?? null;

        $mesDesde =
            $fecha['mes_desde'] ?? null;

        $mesHasta =
            $fecha['mes_hasta'] ?? null;

        $anio =
            $fecha['anio'] ?? null;

        $nombreMeses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $periodoTexto = '';

        if (
            $mesDesde !== null &&
            $mesHasta !== null
        ) {
            $periodoTexto =
                ($nombreMeses[$mesDesde] ?? '') .
                ' a ' .
                ($nombreMeses[$mesHasta] ?? '');
        } elseif ($mes !== null) {
            $periodoTexto =
                $nombreMeses[$mes] ?? '';
        }

        if ($anio && $periodoTexto) {
            $periodoTexto .=
                ' de ' .
                $anio;
        } elseif ($anio) {
            $periodoTexto =
                'el ' .
                $anio;
        }

        if ($periodoTexto === '') {
            $periodoTexto = 'el período consultado';
        }

        $introduccion =
            $esIngreso
                ? 'Durante ' . $periodoTexto . ' se recaudaron'
                : ($esEgreso
                    ? 'Durante ' . $periodoTexto . ' se registraron egresos'
                    : 'Durante ' . $periodoTexto . ' se encontraron');

        $lineas = [];

        foreach ($detalleCategorias as $detalle) {
            $lineas[] =
                '• ' .
                ($detalle['nombre'] ?? 'Categoría') .
                ': S/ ' .
                number_format(
                    (float) ($detalle['total'] ?? 0),
                    2,
                    '.',
                    ','
                );
        }

        $resultadoTexto =
            number_format(
                $resultado,
                2,
                '.',
                ','
            );

        return
            $introduccion .
            ':<br>' .
            implode('<br>', $lineas) .
            '<br><strong>Total: S/ ' .
            $resultadoTexto .
            '.</strong>';
    }

    private function generarMensajeSumaMovimiento(
        float $resultado,
        array $interpretacion,
        bool $esIngreso,
        bool $esEgreso
    ): string {
        $fecha =
            $interpretacion['fecha'] ?? [];

        $mes =
            $fecha['mes'] ?? null;

        $mesDesde =
            $fecha['mes_desde'] ?? null;

        $mesHasta =
            $fecha['mes_hasta'] ?? null;

        $meses =
            $fecha['meses'] ?? [];

        if (!is_array($meses)) {
            $meses = [];
        }

        $meses = array_values(
            array_unique(
                array_map(
                    'intval',
                    array_filter(
                        $meses,
                        fn ($valor) => (int) $valor >= 1 && (int) $valor <= 12
                    )
                )
            )
        );

        sort($meses);

        $anio =
            $fecha['anio'] ?? null;

        if (!$anio) {
            $textoOriginal =
                $interpretacion['texto'] ?? '';

            if (
                preg_match(
                    '/\b(20\d{2})\b/',
                    $textoOriginal,
                    $coincidencia
                )
            ) {
                $anio =
                    (int) $coincidencia[1];
            }
        }

        $nombreMeses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        $categoria =
            $interpretacion['categoria']['nombre']
            ?? null;

        $concepto =
            $interpretacion['concepto']
            ?? null;

        $descripcion = '';

        if ($categoria) {
            $descripcion =
                ' por ' .
                mb_strtolower(
                    $categoria
                );
        }

        if ($concepto) {
            $descripcion .=
                ' de ' .
                $concepto;
        }

        if (
            $mesDesde !== null &&
            $mesHasta !== null
        ) {
            $nombreDesde =
                $nombreMeses[$mesDesde]
                ?? '';

            $nombreHasta =
                $nombreMeses[$mesHasta]
                ?? '';

            $periodoTexto =
                $nombreDesde .
                ' a ' .
                $nombreHasta;

            if ($anio) {
                $periodoTexto .=
                    ' de ' .
                    $anio;
            }

            if ($esIngreso) {
                return
                    'Entre ' .
                    $periodoTexto .
                    ' se recaudaron S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    $descripcion .
                    '.';
            }

            if ($esEgreso) {
                return
                    'Entre ' .
                    $periodoTexto .
                    ' se registraron egresos por S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    $descripcion .
                    '.';
            }

            return
                'Entre ' .
                $periodoTexto .
                ' el total encontrado fue de S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                '.';
        }

        if (count($meses) > 1) {
            $nombresMesesSeleccionados = [];

            foreach ($meses as $mesSeleccionado) {
                $nombresMesesSeleccionados[] =
                    $nombreMeses[$mesSeleccionado]
                    ?? '';
            }

            $periodoTexto =
                implode(
                    ' y ',
                    $nombresMesesSeleccionados
                );

            if ($anio) {
                $periodoTexto .=
                    ' de ' .
                    $anio;
            }

            if ($esIngreso) {
                return
                    'Durante ' .
                    $periodoTexto .
                    ' se recaudaron S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    $descripcion .
                    '.';
            }

            if ($esEgreso) {
                return
                    'Durante ' .
                    $periodoTexto .
                    ' se registraron egresos por S/ ' .
                    number_format(
                        $resultado,
                        2,
                        '.',
                        ','
                    ) .
                    $descripcion .
                    '.';
            }

            return
                'Durante ' .
                $periodoTexto .
                ' el total encontrado fue de S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                '.';
        }

        $periodoTexto = '';

        if ($mes) {
            $periodoTexto =
                $nombreMeses[$mes]
                ?? '';
        }

        if ($anio && $periodoTexto) {
            $periodoTexto .=
                ' de ' .
                $anio;
        } elseif ($anio) {
            $periodoTexto =
                'el ' .
                $anio;
        }

        if ($esIngreso) {
            $mensaje =
                'Durante';

            if ($periodoTexto) {
                $mensaje .=
                    ' ' .
                    $periodoTexto;
            }

            $mensaje .=
                ' se recaudaron S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                $descripcion .
                '.';

            return $mensaje;
        }

        if ($esEgreso) {
            $mensaje =
                'Durante';

            if ($periodoTexto) {
                $mensaje .=
                    ' ' .
                    $periodoTexto;
            }

            $mensaje .=
                ' se registraron egresos por S/ ' .
                number_format(
                    $resultado,
                    2,
                    '.',
                    ','
                ) .
                $descripcion .
                '.';

            return $mensaje;
        }

        return
            'El total encontrado es de S/ ' .
            number_format(
                $resultado,
                2,
                '.',
                ','
            ) .
            '.';
    }

    private function nombreMes(
        int $mes
    ): string {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return $meses[$mes]
            ?? 'mes desconocido';
    }
}