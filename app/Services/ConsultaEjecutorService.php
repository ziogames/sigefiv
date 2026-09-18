<?php

namespace App\Services;

use App\Services\Sigi\SigiChistesService;
use App\Services\Sigi\SigiClimaService;
use App\Services\Sigi\SigiEstatutosService;
use App\Services\Sigi\SigiFinanzasService;
use App\Services\Sigi\SigiPeriodosService;
use App\Services\Sigi\SigiRolesService;
use App\Services\Sigi\SigiSecurityService;
use App\Services\Sigi\SigiUsuariosService;
use App\Services\Zoe\ZoeQueryService;

class ConsultaEjecutorService
{
    private SigiAiService $sigiAi;

    private SigiEstatutosService $sigiEstatutos;

    private SigiFinanzasService $sigiFinanzas;

    private SigiClimaService $sigiClima;

    private SigiChistesService $sigiChistes;

    private SigiUsuariosService $sigiUsuarios;

    private SigiRolesService $sigiRoles;

    private SigiPeriodosService $sigiPeriodos;

    private SigiSecurityService $sigiSecurity;

    private ZoeQueryService $zoeQuery;


    public function __construct(
        SigiAiService $sigiAi,
        SigiEstatutosService $sigiEstatutos,
        SigiFinanzasService $sigiFinanzas,
        SigiClimaService $sigiClima,
        SigiChistesService $sigiChistes,
        SigiUsuariosService $sigiUsuarios,
        SigiRolesService $sigiRoles,
        SigiPeriodosService $sigiPeriodos,
        SigiSecurityService $sigiSecurity,
        ZoeQueryService $zoeQuery
    ) {
        $this->sigiAi = $sigiAi;

        $this->sigiEstatutos = $sigiEstatutos;

        $this->sigiFinanzas = $sigiFinanzas;

        $this->sigiClima = $sigiClima;

        $this->sigiChistes = $sigiChistes;

        $this->sigiUsuarios = $sigiUsuarios;

        $this->sigiRoles = $sigiRoles;

        $this->sigiPeriodos = $sigiPeriodos;

        $this->sigiSecurity = $sigiSecurity;

        $this->zoeQuery = $zoeQuery;
    }


    public function ejecutar(
        array $interpretacion,
        ?int $usuarioId = null
    ): array {

        $tabla =
            $interpretacion['tabla']
            ?? null;

        $operacion =
            $interpretacion['operacion']
            ?? 'show';


        /*
        |--------------------------------------------------------------------------
        | SALUDO
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'greeting') {

            return $this->responderSaludo(
                $interpretacion['consulta_original']
                ?? ''
            );
        }


        /*
        |--------------------------------------------------------------------------
        | ESTATUTOS DEL GRUPO 21
        |--------------------------------------------------------------------------
        */

        $consultaOriginal =
            $interpretacion['consulta_original']
            ?? $interpretacion['texto']
            ?? '';

        if (
            $this->sigiEstatutos
                ->esConsultaEstatutos(
                    $consultaOriginal
                )
        ) {

            return $this->sigiEstatutos->consultar(
                (string) $consultaOriginal
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CONVERSACIÓN GENERAL
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'conversation') {

            return $this->conversarConSigi(
                $interpretacion['consulta_original']
                ?? '',
                $usuarioId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHISTE
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'joke') {

            return $this->sigiChistes->obtener();
        }


        /*
        |--------------------------------------------------------------------------
        | CLIMA
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'weather') {

            return $this->sigiClima->obtener(
                $interpretacion
            );
        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN ADMINISTRATIVA
        |--------------------------------------------------------------------------
        |
        | Usuarios y roles continúan utilizando sus servicios actuales.
        |
        */

        if (
            in_array(
                $tabla,
                [
                    'usuarios',
                    'roles',
                ],
                true
            )
        ) {

            if (!$usuarioId) {

                return [
                    'success' => false,

                    'tipo' => 'seguridad',

                    'resultado' => null,

                    'mensaje' =>
                        $this->sigiSecurity
                            ->respuestaSinPermisoUsuarios(),
                ];
            }


            $usuario =
                \App\Models\User::find(
                    $usuarioId
                );


            if (
                !$usuario ||
                !$this->sigiSecurity
                    ->puedeConsultarUsuarios(
                        $usuario
                    )
            ) {

                return [
                    'success' => false,

                    'tipo' => 'seguridad',

                    'resultado' => null,

                    'mensaje' =>
                        $this->sigiSecurity
                            ->respuestaSinPermisoUsuarios(),
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTAS FINANCIERAS
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | Movimientos y períodos utilizan exclusivamente la capa controlada
        | de ZoeQueryService.
        |
        */

        if ($tabla === 'movimientos') {

            return $this->ejecutarConsultaMovimientosZoe(
                $interpretacion,
                $operacion
            );
        }


        if ($tabla === 'periodos') {

            return $this->ejecutarConsultaPeriodoZoe(
                $interpretacion
            );
        }


        /*
        |--------------------------------------------------------------------------
        | USUARIOS / ROLES
        |--------------------------------------------------------------------------
        */

        return match ($tabla) {

            'usuarios' =>
                $this->sigiUsuarios
                    ->consultarUsuarios(
                        $interpretacion,
                        $operacion
                    ),

            'roles' =>
                $this->sigiRoles
                    ->consultarRoles(
                        $interpretacion,
                        $operacion
                    ),

            default => [

                'success' => false,

                'tipo' => 'texto',

                'resultado' => null,

                'mensaje' =>
                    'No pude determinar qué información deseas consultar.',
            ],
        };
    }


    /*
    |--------------------------------------------------------------------------
    | CONSULTA DE MOVIMIENTOS ZOE
    |--------------------------------------------------------------------------
    */

    private function ejecutarConsultaMovimientosZoe(
        array $interpretacion,
        string $operacion
    ): array {

        $texto =
            mb_strtolower(
                (string) (
                    $interpretacion['consulta_original']
                    ?? $interpretacion['texto']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        */

        $fecha =
            $interpretacion['fecha']
            ?? [];

        $anio =
            isset($fecha['anio']) &&
            is_numeric($fecha['anio'])
                ? (int) $fecha['anio']
                : (
                    isset($interpretacion['anio']) &&
                    is_numeric($interpretacion['anio'])
                        ? (int) $interpretacion['anio']
                        : null
                );

        $mes =
            isset($fecha['mes']) &&
            is_numeric($fecha['mes'])
                ? (int) $fecha['mes']
                : (
                    isset($interpretacion['mes']) &&
                    is_numeric($interpretacion['mes'])
                        ? (int) $interpretacion['mes']
                        : null
                );


        /*
        |--------------------------------------------------------------------------
        | DETECTAR MESES ESCRITOS
        |--------------------------------------------------------------------------
        */

        $meses = [];

        $nombreMeses = [

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


        foreach (
            $nombreMeses
            as $nombre => $numero
        ) {

            if (
                str_contains(
                    $texto,
                    $nombre
                )
            ) {

                $meses[] =
                    $numero;
            }
        }


        $meses =
            array_values(
                array_unique(
                    $meses
                )
            );


        sort($meses);


        /*
        |--------------------------------------------------------------------------
        | SI EL MES VIENE EXPLÍCITAMENTE, RESPETARLO
        |--------------------------------------------------------------------------
        */

        if (
            empty($meses) &&
            $mes !== null
        ) {
            $meses = [$mes];
        }


        /*
        |--------------------------------------------------------------------------
        | CONSTRUIR FILTROS CONTROLADOS
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | ÚLTIMOS MOVIMIENTOS DEL PERÍODO ACTIVO
        |--------------------------------------------------------------------------
        |
        | Cuando el usuario pide "últimos movimientos" sin indicar año
        | ni mes, el período correcto es el período activo de SIGEFIV.
        | No usamos la fecha actual del servidor ni el período de una
        | consulta anterior. Consultamos el período activo mediante la
        | capa segura de ZoeQueryService.
        |--------------------------------------------------------------------------
        */

        $esUltimosMovimientos = preg_match('/\b(?:ultimo|último|ultimos|últimos)\s+(?:(?:\d+)\s+)?(?:ingreso|ingresos|egreso|egresos|gasto|gastos|movimiento|movimientos|movs?)\b/u', $texto) === 1 || preg_match('/\b(?:mu[eé]strame|muestrame|dame|lista|mostrar)\b.*\b(?:ultimos|últimos)\s+\d+\s+(?:movimientos?|movs?)\b/u', $texto) === 1;

        $tienePeriodoExplicito = preg_match('/\b20\d{2}\b/u', $texto) === 1 || preg_match('/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u', $texto) === 1;

        $esGastoActual = preg_match('/\b(?:en\s+que|en\s+qué)\s+(?:gastamos|gast[oó]|se\s+gast[oó]|hemos\s+gastado)\b/u', $texto) === 1 || preg_match('/\b(?:cuanto|cuánto)\s+(?:hemos\s+gastado|gastamos|gast[oó])\b/u', $texto) === 1 || preg_match('/\b(?:que|qué)\s+(?:gastamos|hemos\s+gastado|se\s+gast[oó])\b/u', $texto) === 1;

        $esUltimoPago = preg_match('/\b(?:ultimo|último)\s+(?:pago|pagado|gasto|egreso)\b/u', $texto) === 1;

        $usarPeriodoActivo = ($interpretacion['usar_periodo_activo'] ?? false) === true;

        if ($usarPeriodoActivo && !$tienePeriodoExplicito) { $periodoActivo=$this->zoeQuery->periodoActual(); if($periodoActivo){$anio=(int)$periodoActivo->anio; $mes=(int)$periodoActivo->mes;} } elseif (($esUltimosMovimientos || $esGastoActual || $esUltimoPago) && !$tienePeriodoExplicito) { $periodoActivo=$this->zoeQuery->periodoActual(); if($periodoActivo){$anio=(int)$periodoActivo->anio; $mes=(int)$periodoActivo->mes;} }

        if ($esGastoActual) {
            $filtrosTipo = 'Egreso';
        } else {
            $filtrosTipo = $interpretacion['tipo_movimiento'] ?? null;
        }

        if ($esUltimoPago) {
            $filtrosTipo = 'Egreso';
            $interpretacion['limite'] = 1;
            $operacion = 'show';
        }

        $filtros = [

            'anio' =>
                $anio,

            'mes' =>
                count($meses) === 1
                    ? $meses[0]
                    : $mes,

            'meses' =>
                count($meses) > 1
                    ? $meses
                    : [],

            'tipo_movimiento' =>
                $filtrosTipo,

            'categoria' =>
                $interpretacion['categoria']
                ?? null,

            'categorias' =>
                $interpretacion['categorias']
                ?? [],

            'concepto' =>
                $interpretacion['concepto']
                ?? null,

            'persona' =>
                $interpretacion['persona']
                ?? null,

            'forma_pago' =>
                $interpretacion['forma_pago']
                ?? null,

            'fecha_desde' =>
                $interpretacion['fecha_desde']
                ?? null,

            'fecha_hasta' =>
                $interpretacion['fecha_hasta']
                ?? null,

            'limite' =>
                $interpretacion['limite']
                ?? 20,
        ];


        /*
        |--------------------------------------------------------------------------
        | REFORZAR TIPO DE MOVIMIENTO
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $filtros['tipo_movimiento']
            )
        ) {

            if (
                preg_match(
                    '/\b(egreso|egresos|gasto|gastos|gastamos|gastó|gastó)\b/u',
                    $texto
                )
            ) {

                $filtros['tipo_movimiento'] =
                    'Egreso';

            } elseif (
                preg_match(
                    '/\b(ingreso|ingresos|ingresamos|recaudación|recaudamos|recaudacion)\b/u',
                    $texto
                )
            ) {

                $filtros['tipo_movimiento'] =
                    'Ingreso';
            }
        }


        if ($esGastoActual && !preg_match('/\b(?:en\s+que|en\s+qué)\b/u', $texto)) {
            $operacion = 'sum';
        }

        /*
        |--------------------------------------------------------------------------
        | OPERACIÓN: SUMA
        |--------------------------------------------------------------------------
        |
        | También reconocemos frases naturales como:
        |
        | - cuánto ingresó
        | - cuánto ingresamos
        | - cuánto gastamos
        | - cuánto gastó
        | - total de ingresos
        | - total de egresos
        |
        */

        if (
            preg_match(
                '/\b(total\s+(?:de\s+)?(?:ingresos?|egresos?)|cu[aá]nto\s+(?:ingres[oó]|ingresamos|gastamos|gast[oó]))\b/u',
                $texto
            )
        ) {

            $operacion = 'sum';
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL DE EGRESOS
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/\btotal\s+(?:de\s+)?egresos?\b/u',
                $texto
            )
        ) {

            $operacion =
                'sum';

            $filtros['tipo_movimiento'] =
                'Egreso';
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL DE INGRESOS
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/\btotal\s+(?:de\s+)?ingresos?\b/u',
                $texto
            )
        ) {

            $operacion =
                'sum';

            $filtros['tipo_movimiento'] =
                'Ingreso';
        }


        /*
        |--------------------------------------------------------------------------
        | SUMA
        |--------------------------------------------------------------------------
        */

        if (
            $operacion === 'sum'
        ) {

            $resultado =
                $this->zoeQuery
                    ->sumarMovimientos(
                        $filtros
                    );


            $tipo =
                $filtros['tipo_movimiento']
                ?? null;


            if (
                $tipo === 'Ingreso'
            ) {

                $nombre =
                    'ingresos';

            } elseif (
                $tipo === 'Egreso'
            ) {

                $nombre =
                    'egresos';

            } else {

                $nombre =
                    'movimientos';
            }


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El total de ' .
                    $nombre .
                    ' consultado es de S/ ' .
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
        | COUNT
        |--------------------------------------------------------------------------
        */

        if (
            $operacion === 'count'
        ) {

            $resultado =
                $this->zoeQuery
                    ->contarMovimientos(
                        $filtros
                    );


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'Encontré ' .
                    $resultado .
                    ' movimientos.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | PROMEDIO
        |--------------------------------------------------------------------------
        */

        if (
            $operacion === 'avg'
        ) {

            $resultado =
                $this->zoeQuery
                    ->promedioMovimientos(
                        $filtros
                    );


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El promedio de los movimientos consultados es de S/ ' .
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
        | MÁXIMO
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $operacion,
                [
                    'max',
                    'maximo',
                ],
                true
            )
        ) {

            $movimiento =
                $this->zoeQuery
                    ->maximoMovimiento(
                        $filtros
                    );


            if (!$movimiento) {

                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'numero',

                    'resultado' =>
                        0,

                    'mensaje' =>
                        'No encontré movimientos para la consulta.',
                ];
            }


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    (float) $movimiento->monto,

                'mensaje' =>
                    'El movimiento de mayor monto es de S/ ' .
                    number_format(
                        (float) $movimiento->monto,
                        2,
                        '.',
                        ','
                    ) .
                    '.',

                'detalle' =>
                    $movimiento,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | MÍNIMO
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $operacion,
                [
                    'min',
                    'minimo',
                ],
                true
            )
        ) {

            $movimiento =
                $this->zoeQuery
                    ->minimoMovimiento(
                        $filtros
                    );


            if (!$movimiento) {

                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'numero',

                    'resultado' =>
                        0,

                    'mensaje' =>
                        'No encontré movimientos para la consulta.',
                ];
            }


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    (float) $movimiento->monto,

                'mensaje' =>
                    'El movimiento de menor monto es de S/ ' .
                    number_format(
                        (float) $movimiento->monto,
                        2,
                        '.',
                        ','
                    ) .
                    '.',

                'detalle' =>
                    $movimiento,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | LISTADO DE MOVIMIENTOS
        |--------------------------------------------------------------------------
        */

        $resultado =
            $this->zoeQuery
                ->listarMovimientos(
                    $filtros
                );


        /*
        |--------------------------------------------------------------------------
        | ÚLTIMO PAGO: RESPUESTA DIRECTA
        |--------------------------------------------------------------------------
        |
        | "¿Quién hizo el último pago?" no debe terminar como una consulta
        | genérica ni depender de la interpretación del modelo. La consulta
        | ya está limitada al último egreso del período activo.
        |
        */

        if ($esUltimoPago) {

            if ($resultado->isEmpty()) {

                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'texto',

                    'resultado' =>
                        null,

                    'mensaje' =>
                        'No encontré ningún pago en el período consultado.',
                ];
            }

            $ultimoPago =
                $resultado->first();

            $persona =
                $ultimoPago->persona
                ?? null;

            $fechaPago =
                $ultimoPago->fecha
                ?? null;

            $montoPago =
                isset($ultimoPago->monto)
                    ? (float) $ultimoPago->monto
                    : 0;

            $conceptoPago =
                $ultimoPago->concepto
                ?? null;

            if (empty($persona)) {

                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'texto',

                    'resultado' =>
                        $ultimoPago,

                    'mensaje' =>
                        'Encontré el último pago por S/ ' .
                        number_format(
                            $montoPago,
                            2,
                            '.',
                            ','
                        ) .
                        (
                            $fechaPago
                                ? ' con fecha ' . $fechaPago
                                : ''
                        ) .
                        (
                            $conceptoPago
                                ? ', concepto: ' . $conceptoPago
                                : ''
                        ) .
                        ', pero SIGEFIV no tiene registrada la persona que realizó el pago.',
                ];
            }

            return [

                'success' =>
                    true,

                'tipo' =>
                    'texto',

                'resultado' =>
                    $ultimoPago,

                'mensaje' =>
                    'El último pago fue realizado por ' .
                    $persona .
                    ' por S/ ' .
                    number_format(
                        $montoPago,
                        2,
                        '.',
                        ','
                    ) .
                    (
                        $fechaPago
                            ? ' el ' . $fechaPago
                            : ''
                    ) .
                    (
                        $conceptoPago
                            ? ', por concepto de ' . $conceptoPago
                            : ''
                    ) .
                    '.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | ÚLTIMOS MOVIMIENTOS: INFORMAR SI HAY MENOS RESULTADOS QUE LOS PEDIDOS
        |--------------------------------------------------------------------------
        */

        if ($esUltimosMovimientos) {

            preg_match(
                '/(?:ultimo|último|ultimos|últimos)\s+(\d+)\s+(?:ingreso|ingresos|egreso|egresos|gasto|gastos|movimiento|movimientos|movs?)/u',
                $texto,
                $coincidenciaLimite
            );

            $limiteSolicitado =
                isset($coincidenciaLimite[1])
                    ? (int) $coincidenciaLimite[1]
                    : null;

            $cantidadEncontrada =
                $resultado->count();

            if (
                $limiteSolicitado !== null &&
                $cantidadEncontrada < $limiteSolicitado
            ) {

                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'lista',

                    'resultado' =>
                        $resultado,

                    'mensaje' =>
                        'Solicitaste ' .
                        $limiteSolicitado .
                        ' movimientos, pero solo encontré ' .
                        $cantidadEncontrada .
                        ' en el período consultado.',
                ];
            }
        }


        return [

            'success' =>
                true,

            'tipo' =>
                'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'Encontré ' .
                $resultado->count() .
                ' movimientos.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CONSULTA DE PERÍODOS ZOE
    |--------------------------------------------------------------------------
    */

    private function ejecutarConsultaPeriodoZoe(
        array $interpretacion
    ): array {

        $texto =
            mb_strtolower(
                (string) (
                    $interpretacion['consulta_original']
                    ?? $interpretacion['texto']
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        */

        $fecha =
            $interpretacion['fecha']
            ?? [];

        $anio =
            isset($fecha['anio']) &&
            is_numeric($fecha['anio'])
                ? (int) $fecha['anio']
                : (
                    isset($interpretacion['anio']) &&
                    is_numeric($interpretacion['anio'])
                        ? (int) $interpretacion['anio']
                        : null
                );

        $mes =
            isset($fecha['mes']) &&
            is_numeric($fecha['mes'])
                ? (int) $fecha['mes']
                : (
                    isset($interpretacion['mes']) &&
                    is_numeric($interpretacion['mes'])
                        ? (int) $interpretacion['mes']
                        : null
                );


        /*
        |--------------------------------------------------------------------------
        | DETECTAR MESES ESCRITOS
        |--------------------------------------------------------------------------
        */

        $meses = [];

        $nombreMeses = [

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


        foreach (
            $nombreMeses
            as $nombre => $numero
        ) {

            if (
                str_contains(
                    $texto,
                    $nombre
                )
            ) {

                $meses[] =
                    $numero;
            }
        }


        $meses =
            array_values(
                array_unique(
                    $meses
                )
            );


        sort($meses);


        /*
        |--------------------------------------------------------------------------
        | RESOLVER PERÍODO ACTIVO PARA CONSULTAS SIN FECHA
        |--------------------------------------------------------------------------
        */

        $usarPeriodoActivo = ($interpretacion['usar_periodo_activo'] ?? false) === true;
        $tienePeriodoExplicito = preg_match('/\b20\d{2}\b/u', $texto) === 1 || preg_match('/\b(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|setiembre|octubre|noviembre|diciembre)\b/u', $texto) === 1;
        $esUltimosMovimientos = preg_match('/\b(?:ultimo|último|ultimos|últimos)\s+(?:(?:\d+)\s+)?(?:ingreso|ingresos|egreso|egresos|gasto|gastos|movimiento|movimientos|movs?)\b/u', $texto) === 1;
        $esGastoActual = preg_match('/\b(?:en\s+que|en\s+qué)\s+(?:gastamos|gast[oó]|se\s+gast[oó]|hemos\s+gastado)\b/u', $texto) === 1 || preg_match('/\b(?:cuanto|cuánto)\s+(?:hemos\s+gastado|gastamos|gast[oó])\b/u', $texto) === 1;
        $esIngresoActual = preg_match('/\b(?:qué|que)\s+(?:ingresos?|recaudamos|recaudación)\b/u', $texto) === 1 || preg_match('/\b(?:cuanto|cuánto)\s+(?:hemos\s+ingresado|ingresamos|se\s+recaud[oó]|recaudamos)\b/u', $texto) === 1;
        $esUltimoPago = preg_match('/\b(?:ultimo|último)\s+(?:pago|pagado|gasto|egreso)\b/u', $texto) === 1;

        $esConsultaSinPeriodo = !$tienePeriodoExplicito && ($usarPeriodoActivo || $esUltimosMovimientos || $esGastoActual || $esUltimoPago || $esIngresoActual || str_contains($texto, 'cuánto dinero tenemos') || str_contains($texto, 'cuanto dinero tenemos') || str_contains($texto, 'cuánto tenemos') || str_contains($texto, 'cuanto tenemos') || str_contains($texto, 'en caja') || str_contains($texto, 'saldo final') || str_contains($texto, 'saldo de cierre'));

        if ($esConsultaSinPeriodo) {
            $periodoActivo = $this->zoeQuery->periodoActual();

            if ($periodoActivo) {
                $anio = (int) $periodoActivo->anio;
                $mes = (int) $periodoActivo->mes;
                $meses = [$mes];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SALDO INICIAL
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'saldo inicial'
            )
        ) {

            if (
                $anio === null ||
                $mes === null
            ) {

                return [

                    'success' =>
                        false,

                    'tipo' =>
                        'texto',

                    'resultado' =>
                        null,

                    'mensaje' =>
                        'Necesito saber el año y el mes para consultar el saldo inicial.',
                ];
            }


            $resultado =
                $this->zoeQuery
                    ->saldoInicialPeriodo(
                        $anio,
                        $mes
                    );


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El saldo inicial del período es de S/ ' .
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
        | SALDO FINAL / CAJA
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'saldo final') ||
            str_contains($texto, 'saldo de cierre') ||
            str_contains($texto, 'saldo de caja') ||
            str_contains($texto, 'saldo caja') ||
            str_contains($texto, 'saldo en caja') ||
            str_contains($texto, 'cuánto tenemos en caja') ||
            str_contains($texto, 'cuanto tenemos en caja') ||
            str_contains($texto, 'cuánto hay en caja') ||
            str_contains($texto, 'cuanto hay en caja') ||
            str_contains($texto, 'cuánto dinero tenemos') ||
            str_contains($texto, 'cuanto dinero tenemos')
        ) {

            if (
                $anio === null ||
                $mes === null
            ) {

                return [

                    'success' =>
                        false,

                    'tipo' =>
                        'texto',

                    'resultado' =>
                        null,

                    'mensaje' =>
                        'Necesito saber el año y el mes para consultar el saldo en caja.',
                ];
            }


            $resultado =
                $this->zoeQuery
                    ->saldoFinalPeriodo(
                        $anio,
                        $mes
                    );


            return [

                'success' =>
                    true,

                'tipo' =>
                    'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El saldo en caja es de S/ ' .
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
        | INGRESOS DEL PERÍODO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'total de ingresos') ||
            str_contains($texto, 'total ingresos') ||
            str_contains($texto, 'ingresos de') ||
            str_contains($texto, 'ingresos del') ||
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresamos') ||
            str_contains($texto, 'recaudamos') ||
            str_contains($texto, 'recaudación') ||
            str_contains($texto, 'recaudacion') ||
            str_contains($texto, 'recaudo') ||
            str_contains($texto, 'recaudó') ||
            str_contains($texto, 'recaudaron')
        ) {

            if (
                $anio !== null &&
                $mes !== null
            ) {

                $resultado =
                    $this->zoeQuery
                        ->totalIngresosPeriodo(
                            $anio,
                            $mes
                        );


                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'numero',

                    'resultado' =>
                        $resultado,

                    'mensaje' =>
                        'Los ingresos del período son de S/ ' .
                        number_format(
                            $resultado,
                            2,
                            '.',
                            ','
                        ) .
                        '.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | EGRESOS DEL PERÍODO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'total de egresos') ||
            str_contains($texto, 'total egresos') ||
            str_contains($texto, 'egresos de') ||
            str_contains($texto, 'egresos del') ||
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastos') ||
            str_contains($texto, 'gastamos') ||
            str_contains($texto, 'gastó') ||
            str_contains($texto, 'hemos gastado')
        ) {

            if (
                $anio !== null &&
                $mes !== null
            ) {

                $resultado =
                    $this->zoeQuery
                        ->totalEgresosPeriodo(
                            $anio,
                            $mes
                        );


                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'numero',

                    'resultado' =>
                        $resultado,

                    'mensaje' =>
                        'Los egresos del período son de S/ ' .
                        number_format(
                            $resultado,
                            2,
                            '.',
                            ','
                        ) .
                        '.',
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | LISTADO DE PERÍODOS
        |--------------------------------------------------------------------------
        */

        $filtros = [

            'anio' =>
                $anio,

            'mes' =>
                count($meses) === 1
                    ? $meses[0]
                    : $mes,

            'meses' =>
                count($meses) > 1
                    ? $meses
                    : [],
        ];


        $resultado =
            $this->zoeQuery
                ->listarPeriodos(
                    $filtros
                );


        return [

            'success' =>
                true,

            'tipo' =>
                'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'Encontré ' .
                $resultado->count() .
                ' períodos.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CONVERSACIÓN GENERAL CON ZOE
    |--------------------------------------------------------------------------
    */

    private function conversarConSigi(
        string $consulta,
        ?int $usuarioId = null
    ): array {

        $respuesta =
            $this->sigiAi->responder(
                $consulta,

                'Eres ZOE, la asistente virtual de SIGEFIV. '
                . 'Responde siempre en español, de forma natural, amable y cercana. '
                . 'Puedes conversar sobre temas generales y ayudar al usuario. '
                . 'No inventes datos financieros de SIGEFIV. '
                . 'Las consultas sobre ingresos, egresos, movimientos, periodos, '
                . 'usuarios, roles y demás información del sistema son atendidas '
                . 'localmente por SIGEFIV. '
                . 'Si el usuario quiere conversar, responde como un asistente '
                . 'amigable y conciso.',

                $usuarioId
            );


        if ($respuesta !== null) {

            return [

                'success' =>
                    true,

                'tipo' =>
                    'texto',

                'resultado' =>
                    null,

                'mensaje' =>
                    '🤖 ' .
                    $respuesta,
            ];
        }


        return [

            'success' =>
                false,

            'tipo' =>
                'texto',

            'resultado' =>
                null,

            'mensaje' =>
                '🤖 No pude conectarme con mi asistente de conversación en este momento. '
                . 'Puedes intentarlo nuevamente en unos segundos.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SALUDO DE ZOE
    |--------------------------------------------------------------------------
    */

    private function responderSaludo(
        string $consulta
    ): array {

        $respuesta =
            $this->sigiAi->responder(
                $consulta,

                'Eres ZOE, la asistente virtual de SIGEFIV. '
                . 'Responde en español, de forma muy amable, cercana y afectiva. '
                . 'Cuando el usuario salude, devuélvele un saludo cálido. '
                . 'Menciona de forma natural que también puedes ayudar con consultas '
                . 'financieras de SIGEFIV, chistes y clima. '
                . 'No inventes datos financieros.'
            );


        if ($respuesta !== null) {

            return [

                'success' =>
                    true,

                'tipo' =>
                    'texto',

                'resultado' =>
                    null,

                'mensaje' =>
                    '🤖 ' .
                    $respuesta,
            ];
        }


        return [

            'success' =>
                false,

            'tipo' =>
                'texto',

            'resultado' =>
                null,

            'mensaje' =>
                '🤖 Hola ❤️. Estoy aquí para ayudarte. También puedo '
                . 'contarte un chiste o informarte sobre el clima.',
        ];
    }
}