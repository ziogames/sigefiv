<?php

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Periodo;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

class ConsultaEjecutorService
{
    public function ejecutar(array $interpretacion): array
    {
        $tabla = $interpretacion['tabla'] ?? null;
        $operacion = $interpretacion['operacion'] ?? 'show';

        /*
        |--------------------------------------------------------------------------
        | SALUDO
        |--------------------------------------------------------------------------
        |
        | Los saludos no pertenecen a SQLite.
        | Sigi responde de forma cercana y recuerda que también puede
        | contar chistes o informar sobre el clima.
        |
        */

        if ($operacion === 'greeting') {

            return $this->responderSaludo(
                $interpretacion['consulta_original'] ?? ''
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CONVERSACIÓN GENERAL
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'conversation') {

            return $this->conversarConOpenRouter(
                $interpretacion['consulta_original'] ?? ''
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CHISTE
        |--------------------------------------------------------------------------
        |
        | Una consulta de chistes no pertenece a ninguna tabla de SQLite.
        | Se ejecuta directamente antes de evaluar la tabla.
        |
        */

        if ($operacion === 'joke') {

            return $this->obtenerChiste();

        }


        /*
        |--------------------------------------------------------------------------
        | CLIMA
        |--------------------------------------------------------------------------
        |
        | El clima tampoco pertenece a una tabla de SQLite.
        | Se ejecuta directamente antes de evaluar $tabla.
        |
        */

        if ($operacion === 'weather') {

            return $this->obtenerClima(
                $interpretacion
            );

        }

        return match ($tabla) {

            'movimientos' => $this->consultarMovimientos(
                $interpretacion,
                $operacion
            ),

            'periodos' => $this->consultarPeriodos(
                $interpretacion,
                $operacion
            ),

            'usuarios' => $this->consultarUsuarios(
                $interpretacion,
                $operacion
            ),

            'roles' => $this->consultarRoles(
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
    | CONVERSACIÓN GENERAL CON OPENROUTER
    |--------------------------------------------------------------------------
    */

    private function conversarConOpenRouter(
        string $consulta
    ): array {

        $respuesta =
            $this->llamarOpenRouter(
                $consulta,
                'Eres Sigi, el asistente virtual de SIGEFIV. '
                . 'Responde siempre en español, de forma natural, amable y cercana. '
                . 'Puedes conversar sobre temas generales y ayudar al usuario. '
                . 'No inventes datos financieros de SIGEFIV. '
                . 'Las consultas sobre ingresos, egresos, movimientos, periodos, '
                . 'usuarios, roles y demás información del sistema son atendidas '
                . 'localmente por SIGEFIV. '
                . 'Si el usuario quiere conversar, responde como un asistente '
                . 'amigable y conciso.'
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
                    '🤖 ' . $respuesta,

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
            $this->llamarOpenRouter(
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

                'success' => true,

                'tipo' => 'texto',

                'resultado' => null,

                'mensaje' => '🤖 ' . $respuesta,

            ];
        }


        return [

            'success' => false,

            'tipo' => 'texto',

            'resultado' => null,

            'mensaje' =>
                '🤖 Hola ❤️. Estoy aquí para ayudarte. También puedo '
                . 'contarte un chiste o informarte sobre el clima.',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | CLIMA DESDE OPEN-METEO
    |--------------------------------------------------------------------------
    */

    private function obtenerClima(
        array $interpretacion
    ): array {

        try {

            /*
            |--------------------------------------------------------------------------
            | Por ahora usamos Lima como ubicación predeterminada.
            | Más adelante podremos interpretar una ciudad escrita por el usuario.
            |--------------------------------------------------------------------------
            */

            $ubicacion =
                env(
                    'WEATHER_DEFAULT_LOCATION',
                    'Lima, Peru'
                );


            /*
            |--------------------------------------------------------------------------
            | Geocodificación
            |--------------------------------------------------------------------------
            */

            $geo =
                Http::timeout(5)
                    ->acceptJson()
                    ->get(
                        'https://geocoding-api.open-meteo.com/v1/search',
                        [
                            'name' =>
                                $ubicacion,

                            'count' =>
                                1,

                            'language' =>
                                'es',

                            'format' =>
                                'json',
                        ]
                    );


            if (!$geo->successful()) {

                return [

                    'success' => false,

                    'tipo' => 'clima',

                    'resultado' => null,

                    'mensaje' =>
                        'No pude localizar la ciudad para consultar el clima.',

                ];
            }


            $lugares =
                $geo->json('results');


            if (
                !is_array($lugares) ||
                empty($lugares)
            ) {

                return [

                    'success' => false,

                    'tipo' => 'clima',

                    'resultado' => null,

                    'mensaje' =>
                        'No encontré la ubicación solicitada.',

                ];
            }


            $lugar =
                $lugares[0];


            $latitud =
                $lugar['latitude']
                ?? null;


            $longitud =
                $lugar['longitude']
                ?? null;


            $nombre =
                $lugar['name']
                ?? $ubicacion;


            $pais =
                $lugar['country']
                ?? '';


            if (
                $latitud === null ||
                $longitud === null
            ) {

                return [

                    'success' => false,

                    'tipo' => 'clima',

                    'resultado' => null,

                    'mensaje' =>
                        'No pude obtener las coordenadas de la ubicación.',

                ];
            }


            /*
            |--------------------------------------------------------------------------
            | Consulta meteorológica
            |--------------------------------------------------------------------------
            */

            $clima =
                Http::timeout(5)
                    ->acceptJson()
                    ->get(
                        'https://api.open-meteo.com/v1/forecast',
                        [

                            'latitude' =>
                                $latitud,

                            'longitude' =>
                                $longitud,

                            'current' =>
                                'temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m',

                            'timezone' =>
                                'auto',

                        ]
                    );


            if (!$clima->successful()) {

    \Log::error('ERROR OPEN-METEO CLIMA', [
        'status' => $clima->status(),
        'body' => $clima->body(),
    ]);

    return [

                    'success' => false,

                    'tipo' => 'clima',

                    'resultado' => null,

                    'mensaje' =>
                        'No pude obtener los datos meteorológicos en este momento.',

                ];
            }


            $datos =
                $clima->json();


            $actual =
                $datos['current']
                ?? [];


            if (empty($actual)) {

                return [

                    'success' => false,

                    'tipo' => 'clima',

                    'resultado' => null,

                    'mensaje' =>
                        'La API meteorológica no devolvió datos actuales.',

                ];
            }


            $codigo =
                (int) (
                    $actual['weather_code']
                    ?? -1
                );


            $descripcion =
                match (true) {

                    $codigo === 0 =>
                        'Despejado',

                    in_array(
                        $codigo,
                        [1, 2, 3],
                        true
                    ) =>
                        'Parcialmente nublado',

                    in_array(
                        $codigo,
                        [45, 48],
                        true
                    ) =>
                        'Niebla',

                    in_array(
                        $codigo,
                        [51, 53, 55, 56, 57],
                        true
                    ) =>
                        'Llovizna',

                    in_array(
                        $codigo,
                        [61, 63, 65, 66, 67],
                        true
                    ) =>
                        'Lluvia',

                    in_array(
                        $codigo,
                        [71, 73, 75, 77],
                        true
                    ) =>
                        'Nieve',

                    in_array(
                        $codigo,
                        [80, 81, 82],
                        true
                    ) =>
                        'Chubascos',

                    in_array(
                        $codigo,
                        [95, 96, 99],
                        true
                    ) =>
                        'Tormenta',

                    default =>
                        'Condiciones variables',

                };


            $resultado = [

                'ubicacion' => [

                    'nombre' =>
                        $nombre,

                    'pais' =>
                        $pais,

                    'latitud' =>
                        $latitud,

                    'longitud' =>
                        $longitud,

                ],

                'temperatura' =>
                    $actual['temperature_2m']
                    ?? null,

                'sensacion' =>
                    $actual['apparent_temperature']
                    ?? null,

                'humedad' =>
                    $actual['relative_humidity_2m']
                    ?? null,

                'viento' =>
                    $actual['wind_speed_10m']
                    ?? null,

                'codigo' =>
                    $codigo,

                'descripcion' =>
                    $descripcion,

            ];


            /*
            |--------------------------------------------------------------------------
            | OpenRouter redacta la respuesta, pero los datos meteorológicos
            | siguen viniendo de Open-Meteo. Así conservamos la tarjeta de clima
            | y evitamos que la IA invente la temperatura.
            |--------------------------------------------------------------------------
            */

            $mensajeClima =
                $this->llamarOpenRouter(
                    'Informa brevemente al usuario sobre este clima actual. '
                    . 'Ciudad: ' . $nombre . '. '
                    . 'País: ' . $pais . '. '
                    . 'Condición: ' . $descripcion . '. '
                    . 'Temperatura: ' . $resultado['temperatura'] . ' °C. '
                    . 'Sensación: ' . $resultado['sensacion'] . ' °C. '
                    . 'Humedad: ' . $resultado['humedad'] . '%. '
                    . 'Viento: ' . $resultado['viento'] . ' km/h.',
                    'Eres Sigi. Redacta una respuesta breve, natural y amable '
                    . 'en español. Usa únicamente los datos meteorológicos '
                    . 'proporcionados. No inventes valores. No uses Markdown '
                    . 'complejo porque la interfaz mostrará una tarjeta de clima.'
                );


            if ($mensajeClima === null) {

                $mensajeClima =
                    'Clima actual en ' .
                    $nombre .
                    ': ' .
                    $descripcion .
                    ', ' .
                    $resultado['temperatura'] .
                    ' °C.';

            }


            return [

                'success' => true,

                'tipo' => 'clima',

                'resultado' => $resultado,

                'mensaje' => $mensajeClima,

            ];

        } catch (\Throwable $e) {

            return [

                'success' => false,

                'tipo' => 'clima',

                'resultado' => null,

                'mensaje' =>
                    'No pude consultar el clima en este momento: ' .
                    $e->getMessage(),

            ];
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CHISTE DESDE API JSON
    |--------------------------------------------------------------------------
    */

    private function obtenerChiste(): array
    {
        $respuesta =
            $this->llamarOpenRouter(
                'Cuéntame un chiste corto y divertido en español.',
                'Eres Sigi. Cuenta un chiste breve, familiar y apropiado '
                . 'para todo público. No expliques el chiste. Devuelve solamente '
                . 'el chiste, con un tono alegre.'
            );


        if ($respuesta !== null) {

            return [

                'success' => true,

                'tipo' => 'texto',

                'resultado' => null,

                'mensaje' => '😂 ' . $respuesta,

            ];
        }


        return [

            'success' => false,

            'tipo' => 'texto',

            'resultado' => null,

            'mensaje' =>
                'No pude conectarme con OpenRouter para obtener un chiste en este momento.',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | OPENROUTER
    |--------------------------------------------------------------------------
    */

    private function llamarOpenRouter(
        string $mensaje,
        string $instruccionesSistema
    ): ?string {

        $apiKey =
            env(
                'OPENROUTER_API_KEY'
            );


        if (
            !is_string($apiKey) ||
            trim($apiKey) === ''
        ) {

            return null;

        }


        $modelo =
            env(
                'OPENROUTER_MODEL',
                'mistralai/ministral-3b-2512'
            );


        try {

            $respuesta =
                Http::timeout(20)
                    ->withToken($apiKey)
                    ->acceptJson()
                    ->post(
                        'https://openrouter.ai/api/v1/chat/completions',
                        [

                            'model' =>
                                $modelo,

                            'messages' => [

                                [

                                    'role' =>
                                        'system',

                                    'content' =>
                                        $instruccionesSistema,

                                ],

                                [

                                    'role' =>
                                        'user',

                                    'content' =>
                                        $mensaje,

                                ],

                            ],

                            'temperature' =>
                                0.7,

                            'max_tokens' =>
                                300,

                        ]
                    );


            if (!$respuesta->successful()) {

                return null;

            }


            $contenido =
                $respuesta->json(
                    'choices.0.message.content'
                );


            if (
                !is_string($contenido) ||
                trim($contenido) === ''
            ) {

                return null;

            }


            return trim($contenido);

        } catch (\Throwable $e) {

            return null;

        }
    }


    /*
    |--------------------------------------------------------------------------
    | MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    private function consultarMovimientos(
        array $interpretacion,
        string $operacion
    ): array {

        $consulta = Movimiento::query()
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


        $mesDesde =
            $fecha['mes_desde'] ?? null;


        $mesHasta =
            $fecha['mes_hasta'] ?? null;


        $anio =
            $fecha['anio'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | RANGO DE MESES
        |--------------------------------------------------------------------------
        |
        | Tiene PRIORIDAD sobre un mes individual.
        |
        | Ejemplo:
        |
        | Primer trimestre de 2025
        |
        | 2025-01-01
        | hasta
        | 2025-03-31
        |
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


            $ultimoDia =
                cal_days_in_month(
                    CAL_GREGORIAN,
                    $mesHasta,
                    $anio
                );


            $fechaFin =
                sprintf(
                    '%04d-%02d-%02d',
                    $anio,
                    $mesHasta,
                    $ultimoDia
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
        | MES INDIVIDUAL
        |--------------------------------------------------------------------------
        */

        elseif (
            $mes !== null &&
            $anio !== null
        ) {

            $consulta->whereYear(
                'fecha',
                $anio
            );


            $consulta->whereMonth(
                'fecha',
                $mes
            );

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
        | TEXTO ORIGINAL
        |--------------------------------------------------------------------------
        */

        $texto =
            mb_strtolower(
                $interpretacion['texto'] ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | INGRESOS
        |--------------------------------------------------------------------------
        */

        $esIngreso =
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresos') ||
            str_contains($texto, 'ingresó') ||
            str_contains($texto, 'ingresaron') ||
            str_contains($texto, 'recaudó') ||
            str_contains($texto, 'recaudo') ||
            str_contains($texto, 'recaudaron');


        /*
        |--------------------------------------------------------------------------
        | EGRESOS
        |--------------------------------------------------------------------------
        */

        $esEgreso =
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'egresos') ||
            str_contains($texto, 'egresó') ||
            str_contains($texto, 'egresaron') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastos') ||
            str_contains($texto, 'gastó') ||
            str_contains($texto, 'gastaron') ||
            str_contains($texto, 'pago') ||
            str_contains($texto, 'pagos') ||
            str_contains($texto, 'pagó');


        /*
        |--------------------------------------------------------------------------
        | "Hizo sus pagos" significa que la persona realizó un pago
        | hacia la organización; por eso, para consultar sus meses,
        | buscamos esos registros como ingresos.
        |--------------------------------------------------------------------------
        */

        if (
            $operacion === 'meses_concepto' &&
            (
                str_contains($texto, 'hizo sus pago') ||
                str_contains($texto, 'hizo sus pagos') ||
                str_contains($texto, 'realizó sus pago') ||
                str_contains($texto, 'realizo sus pago') ||
                str_contains($texto, 'efectuó sus pago') ||
                str_contains($texto, 'efectuo sus pago')
            )
        ) {

            $esEgreso = false;
            $esIngreso = true;

        }


        if ($esIngreso) {

            $consulta->where(
                'tipo',
                'Ingreso'
            );

        }


        if ($esEgreso) {

            $consulta->where(
                'tipo',
                'Egreso'
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORÍA
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | CONCEPTO
        |--------------------------------------------------------------------------
        */

        $concepto =
            $interpretacion['concepto'] ?? null;


        if ($concepto) {

            $consulta->where(
                'concepto',
                'like',
                '%' . $concepto . '%'
            );

        }

        /*
|--------------------------------------------------------------------------
| MES CON MAYORES EGRESOS
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| MESES DE UN CONCEPTO
|--------------------------------------------------------------------------
*/

if ($operacion === 'meses_concepto') {

    $resultados =
        $consulta
            ->selectRaw(
                'strftime("%Y", fecha) as anio,
                 strftime("%m", fecha) as mes,
                 COUNT(*) as cantidad,
                 SUM(monto) as total'
            )
            ->groupByRaw(
                'strftime("%Y", fecha),
                 strftime("%m", fecha)'
            )
            ->orderByRaw(
                'strftime("%Y", fecha),
                 strftime("%m", fecha)'
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

        '01' => 'Enero',
        '02' => 'Febrero',
        '03' => 'Marzo',
        '04' => 'Abril',
        '05' => 'Mayo',
        '06' => 'Junio',
        '07' => 'Julio',
        '08' => 'Agosto',
        '09' => 'Septiembre',
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
            str_pad(
                (string) $fila->mes,
                2,
                '0',
                STR_PAD_LEFT
            );


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


    /*
    |--------------------------------------------------------------------------
    | Obtener egresos agrupados por mes
    |--------------------------------------------------------------------------
    */

    $resultados =
        Movimiento::query()
            ->where('tipo', 'Egreso')
            ->whereYear('fecha', $anio)
            ->selectRaw(
                'strftime("%m", fecha) as mes,
                 SUM(monto) as total'
            )
            ->groupByRaw(
                'strftime("%m", fecha)'
            )
            ->orderByDesc('total')
            ->first();


    /*
    |--------------------------------------------------------------------------
    | No hay resultados
    |--------------------------------------------------------------------------
    */

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
| MES CON MENORES EGRESOS
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
        Movimiento::query()
            ->where('tipo', 'Egreso')
            ->whereYear('fecha', $anio)
            ->selectRaw(
                'strftime("%m", fecha) as mes,
                 SUM(monto) as total'
            )
            ->groupByRaw(
                'strftime("%m", fecha)'
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

        |
        | Si la consulta tiene un mes específico:
        |   "mayor ingreso de enero de 2025"
        |
        | buscamos el movimiento individual de mayor monto.
        |
        | Si solo tiene el año:
        |   "qué mes tuvo mayores ingresos en 2025"
        |
        | comparamos el total acumulado de cada mes.
        |
        */

        if ($operacion === 'max_mes_ingreso') {

            if (
                $mes !== null &&
                $anio !== null
            ) {

                $movimiento =
                    Movimiento::query()
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
                    Movimiento::query()
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

                $meses = [];

                foreach ($totalesPorMes as $mesMovimiento => $total) {

                    if ((float) $total === (float) $mayor) {

                        $meses[] =
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
                        implode(', ', $meses) .
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
        |
        | Con mes específico: devuelve UN SOLO movimiento.
        |
        | Sin mes, pero con año: compara el total de ingresos de cada mes.
        |
        */

        if ($operacion === 'min_mes_ingreso') {

            if (
                $mes !== null &&
                $anio !== null
            ) {

                $movimiento =
                    Movimiento::query()
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
                    Movimiento::query()
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

                $meses = [];

                foreach ($totalesPorMes as $mesMovimiento => $total) {

                    if ((float) $total === (float) $menor) {

                        $meses[] =
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
                        implode(', ', $meses) .
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
                ->latest('fecha')
                ->limit(20)
                ->get();


        return [

            'success' => true,

            'tipo' => 'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'Encontré ' .
                $resultado->count() .
                ' movimientos que coinciden con tu consulta.',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | MENSAJE NATURAL PARA SUMAS
    |--------------------------------------------------------------------------
    */

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


        $anio =
            $fecha['anio'] ?? null;


        /*
        |--------------------------------------------------------------------------
        | RESPALDO DEL AÑO
        |--------------------------------------------------------------------------
        |
        | Si el intérprete no envía el año dentro de fecha, lo recuperamos
        | desde el texto original de la consulta.
        |
        */

        if (!$anio) {

            $textoOriginal =
                $interpretacion['texto'] ?? '';

            if (
                preg_match(
                    '/\\b(20\\d{2})\\b/',
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


        /*
        |--------------------------------------------------------------------------
        | DESCRIPCIÓN
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | RANGO / TRIMESTRE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | MES INDIVIDUAL
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | INGRESOS
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | EGRESOS
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | GENERAL
        |--------------------------------------------------------------------------
        */

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


    /*
    |--------------------------------------------------------------------------
    | NOMBRE DEL MES
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | PERIODOS
    |--------------------------------------------------------------------------
    */

    private function consultarPeriodos(
        array $interpretacion,
        string $operacion
    ): array {

        $consulta =
            Periodo::query();


        $fecha =
            $interpretacion['fecha']
            ?? [];


        $mes =
            $fecha['mes']
            ?? null;


        $anio =
            $fecha['anio']
            ?? null;


        if ($mes) {

            $consulta->where(
                'mes',
                $mes
            );

        }


        if ($anio) {

            $consulta->where(
                'anio',
                $anio
            );

        }


        $texto =
            mb_strtolower(
                $interpretacion['texto']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | SALDO EN CAJA
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'saldo final'
            ) ||
            str_contains(
                $texto,
                'saldo de cierre'
            ) ||
            str_contains(
                $texto,
                'saldo cierre'
            ) ||
            str_contains(
                $texto,
                'saldo en caja'
            ) ||
            str_contains(
                $texto,
                'saldo de caja'
            ) ||
            str_contains(
                $texto,
                'saldo caja'
            ) ||
            str_contains(
                $texto,
                'quedó en caja'
            ) ||
            str_contains(
                $texto,
                'quedo en caja'
            ) ||
            str_contains(
                $texto,
                'quedó disponible'
            ) ||
            str_contains(
                $texto,
                'quedo disponible'
            ) ||
            str_contains(
                $texto,
                'cuánto quedó'
            ) ||
            str_contains(
                $texto,
                'cuanto quedo'
            )
        ) {

            $periodo =
                $consulta->first();


            if (!$periodo) {

                return [

                    'success' => true,

                    'tipo' => 'numero',

                    'resultado' => 0,

                    'mensaje' =>
                        'No encontré el período solicitado.',

                ];
            }


            $resultado =
                (float) $periodo->saldo_final;


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El saldo en caja de ' .
                    $periodo->nombre_completo .
                    ' es de S/ ' .
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
        | TOTAL INGRESOS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'total de ingresos'
            ) ||
            str_contains(
                $texto,
                'total ingresos'
            )
        ) {

            $periodo =
                $consulta->first();


            if (!$periodo) {

                return [

                    'success' => true,

                    'tipo' => 'numero',

                    'resultado' => 0,

                    'mensaje' =>
                        'No encontré el período solicitado.',

                ];
            }


            $resultado =
                (float) $periodo->total_ingresos;


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El total de ingresos del período ' .
                    $periodo->nombre_completo .
                    ' es de S/ ' .
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
        | TOTAL EGRESOS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'total de egresos'
            ) ||
            str_contains(
                $texto,
                'total egresos'
            )
        ) {

            $periodo =
                $consulta->first();


            if (!$periodo) {

                return [

                    'success' => true,

                    'tipo' => 'numero',

                    'resultado' => 0,

                    'mensaje' =>
                        'No encontré el período solicitado.',

                ];
            }


            $resultado =
                (float) $periodo->total_egresos;


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'El total de egresos del período ' .
                    $periodo->nombre_completo .
                    ' es de S/ ' .
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
        | LISTADO
        |--------------------------------------------------------------------------
        */

        $resultado =
            $consulta
                ->orderByDesc('anio')
                ->orderByDesc('mes')
                ->limit(20)
                ->get();


        return [

            'success' => true,

            'tipo' => 'lista',

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
    | USUARIOS
    |--------------------------------------------------------------------------
    */

    private function consultarUsuarios(
        array $interpretacion,
        string $operacion
    ): array {

        $consulta =
            User::query();


        $texto =
            mb_strtolower(
                $interpretacion['texto']
                ?? ''
            );


        if (
            str_contains(
                $texto,
                'administrador'
            )
        ) {

            $consulta->role(
                'Administrador'
            );

        }


        if (
            str_contains(
                $texto,
                'tesorero'
            )
        ) {

            $consulta->role(
                'Tesorero'
            );

        }


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
                    ' usuarios que coinciden con la consulta.',

            ];
        }


        $resultado =
            $consulta
                ->select(
                    'id',
                    'name',
                    'email'
                )
                ->limit(20)
                ->get();


        return [

            'success' => true,

            'tipo' => 'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'Encontré ' .
                $resultado->count() .
                ' usuarios.',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ROLES
    |--------------------------------------------------------------------------
    */

    private function consultarRoles(
        array $interpretacion,
        string $operacion
    ): array {

        $roles =
            Role::query();


        if ($operacion === 'count') {

            $resultado =
                $roles->count();


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'Actualmente existen ' .
                    $resultado .
                    ' roles registrados.',

            ];
        }


        $resultado =
            $roles
                ->select(
                    'id',
                    'name'
                )
                ->get();


        return [

            'success' => true,

            'tipo' => 'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'Encontré ' .
                $resultado->count() .
                ' roles registrados.',

        ];
    }
}