<?php

namespace App\Services;

use App\Services\SigiAiService;
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

        $this->sigiAi =
            $sigiAi;

        $this->sigiEstatutos =
            $sigiEstatutos;

        $this->sigiFinanzas =
            $sigiFinanzas;

        $this->sigiClima =
            $sigiClima;

        $this->sigiChistes =
            $sigiChistes;

        $this->sigiUsuarios =
            $sigiUsuarios;

        $this->sigiRoles =
            $sigiRoles;

        $this->sigiPeriodos =
            $sigiPeriodos;

        $this->sigiSecurity =
            $sigiSecurity;

        $this->zoeQuery =
            $zoeQuery;
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
        | Movimientos y períodos pasan por la capa controlada de ZOE.
        |
        */

        if (
            in_array(
                $tabla,
                [
                    'movimientos',
                    'periodos',
                ],
                true
            )
        ) {

            return $this->ejecutarConsultaZoe(
                $interpretacion,
                $operacion
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

                'mensaje' =>
                    'No pude determinar qué información deseas consultar.',
            ],
        };
    }


    /*
    |--------------------------------------------------------------------------
    | EJECUTAR CONSULTA FINANCIERA ZOE
    |--------------------------------------------------------------------------
    */

    private function ejecutarConsultaZoe(
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
                : null;

        $mes =
            isset($fecha['mes']) &&
            is_numeric($fecha['mes'])
                ? (int) $fecha['mes']
                : null;


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
        | CONSTRUIR FILTROS CONTROLADOS
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

            'tipo_movimiento' =>
                $interpretacion['tipo_movimiento']
                ?? null,

            'categoria' =>
                $interpretacion['categoria']
                ?? null,

            'categorias' =>
                $interpretacion['categorias']
                ?? [],

            'concepto' =>
                $interpretacion['concepto']
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
                    '/\b(egreso|egresos|gasto|gastos|gastamos|gastó)\b/u',
                    $texto
                )
            ) {

                $filtros['tipo_movimiento'] =
                    'Egreso';

            } elseif (
                preg_match(
                    '/\b(ingreso|ingresos|ingresamos|recaudación|recaudamos)\b/u',
                    $texto
                )
            ) {

                $filtros['tipo_movimiento'] =
                    'Ingreso';
            }
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
        | PERÍODOS
        |--------------------------------------------------------------------------
        */

        if (
            $tabla === 'periodos'
        ) {

            return $this->ejecutarConsultaPeriodoZoe(
                $texto,
                $anio,
                $mes,
                $meses,
                $operacion
            );
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
        string $texto,
        ?int $anio,
        ?int $mes,
        array $meses,
        string $operacion
    ): array {

        /*
        |--------------------------------------------------------------------------
        | PERÍODO ACTUAL
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'periodo actual') ||
            str_contains($texto, 'período actual') ||
            str_contains($texto, 'periodo vigente') ||
            str_contains($texto, 'período vigente')
        ) {

            $periodo =
                $this->zoeQuery
                    ->periodoActual();


            if (!$periodo) {

                return [

                    'success' =>
                        true,

                    'tipo' =>
                        'lista',

                    'resultado' =>
                        collect(),

                    'mensaje' =>
                        'No encontré un período actual.',
                ];
            }


            return [

                'success' =>
                    true,

                'tipo' =>
                    'lista',

                'resultado' =>
                    collect([
                        $periodo
                    ]),

                'mensaje' =>
                    'El período actual es ' .
                    $periodo->nombre .
                    ' ' .
                    $periodo->anio .
                    '.',
            ];
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
            str_contains($texto, 'ingreso')
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
            str_contains($texto, 'gastos')
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
    | CONVERSACIÓN GENERAL CON SIGI
    |--------------------------------------------------------------------------
    */

    private function conversarConSigi(
        string $consulta,
        ?int $usuarioId = null
    ): array {

        $respuesta =
            $this->sigiAi->responder(
                $consulta,

                'Eres Sigi, el asistente virtual de SIGEFIV. '
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
    | SALUDO DE SIGI
    |--------------------------------------------------------------------------
    */

    private function responderSaludo(
        string $consulta
    ): array {

        $respuesta =
            $this->sigiAi->responder(
                $consulta,

                'Eres Sigi, el asistente virtual de SIGEFIV. '
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