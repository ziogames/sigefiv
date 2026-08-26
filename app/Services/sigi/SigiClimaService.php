<?php

namespace App\Services\Sigi;

use App\Models\Clima;
use App\Services\SigiAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SigiClimaService
{
    private SigiAiService $sigiAi;

    public function __construct(
        SigiAiService $sigiAi
    ) {

        $this->sigiAi =
            $sigiAi;
    }

    public function obtener(
        array $interpretacion
    ): array {

        return $this->obtenerClima(
            $interpretacion
        );
    }


    private function obtenerClima(
    array $interpretacion
): array {

    try {

        /*
        |--------------------------------------------------------------------------
        | UBICACIÓN PREDETERMINADA
        |--------------------------------------------------------------------------
        */

        $ubicacion =
            env(
                'WEATHER_DEFAULT_LOCATION',
                'Lima, Peru'
            );


        /*
        |--------------------------------------------------------------------------
        | GEOCODIFICACIÓN
        |--------------------------------------------------------------------------
        */

        $lugares =
            Cache::remember(
                'sigi_geocoding_' . md5($ubicacion),
                now()->addHours(24),
                function () use ($ubicacion) {

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

                        return null;

                    }


                    return $geo->json('results');

                }
            );


        if (
            !is_array($lugares) ||
            empty($lugares)
        ) {

            return [
                'success' => false,
                'tipo' => 'clima',
                'resultado' => null,
                'mensaje' =>
                    'No pude localizar la ciudad para consultar el clima.',
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
        | BUSCAR CLIMA EN POSTGRESQL
        |--------------------------------------------------------------------------
        */

        $climaGuardado =
            Clima::query()
                ->where('ubicacion', $nombre)
                ->where('proveedor', 'open-meteo')
                ->where('expira_en', '>', now())
                ->latest('consultado_en')
                ->first();


        /*
        |--------------------------------------------------------------------------
        | SI EXISTE UN CLIMA VIGENTE
        |--------------------------------------------------------------------------
        */

        if ($climaGuardado) {

            $resultado = [

                'ubicacion' => [

                    'nombre' =>
                        $climaGuardado->ubicacion,

                    'pais' =>
                        $climaGuardado->pais,

                    'latitud' =>
                        $climaGuardado->latitud,

                    'longitud' =>
                        $climaGuardado->longitud,

                ],

                'temperatura' =>
                    $climaGuardado->temperatura,

                'sensacion' =>
                    $climaGuardado->sensacion,

                'humedad' =>
                    $climaGuardado->humedad,

                'viento' =>
                    $climaGuardado->viento,

                'codigo' =>
                    $climaGuardado->codigo,

                'descripcion' =>
                    $climaGuardado->descripcion,

            ];


            return $this->respuestaClima(
                $resultado
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTAR OPEN-METEO
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


        /*
        |--------------------------------------------------------------------------
        | SI OPEN-METEO FALLA
        |--------------------------------------------------------------------------
        |
        | Intentamos utilizar el último dato almacenado aunque haya vencido.
        | Esto evita dejar a Sigi sin clima cuando Open-Meteo esté temporalmente
        | limitado o responda con HTTP 429.
        |--------------------------------------------------------------------------
        */

        if (!$clima->successful()) {

            $climaAnterior =
                Clima::query()
                    ->where('ubicacion', $nombre)
                    ->latest('consultado_en')
                    ->first();


            if ($climaAnterior) {

                $resultado = [

                    'ubicacion' => [

                        'nombre' =>
                            $climaAnterior->ubicacion,

                        'pais' =>
                            $climaAnterior->pais,

                        'latitud' =>
                            $climaAnterior->latitud,

                        'longitud' =>
                            $climaAnterior->longitud,

                    ],

                    'temperatura' =>
                        $climaAnterior->temperatura,

                    'sensacion' =>
                        $climaAnterior->sensacion,

                    'humedad' =>
                        $climaAnterior->humedad,

                    'viento' =>
                        $climaAnterior->viento,

                    'codigo' =>
                        $climaAnterior->codigo,

                    'descripcion' =>
                        $climaAnterior->descripcion,

                ];


                return $this->respuestaClima(
                    $resultado,
                    true
                );

            }


            return [
                'success' => false,
                'tipo' => 'clima',
                'resultado' => null,
                'mensaje' =>
                    'No pude obtener los datos meteorológicos en este momento.',
            ];

        }


        /*
        |--------------------------------------------------------------------------
        | DATOS ACTUALES
        |--------------------------------------------------------------------------
        */

        $datos =
            $clima->json();


        $actual =
            $datos['current']
            ?? [];


        if (
            empty($actual)
        ) {

            return [
                'success' => false,
                'tipo' => 'clima',
                'resultado' => null,
                'mensaje' =>
                    'La API meteorológica no devolvió datos actuales.',
            ];

        }


        /*
        |--------------------------------------------------------------------------
        | CÓDIGO METEOROLÓGICO
        |--------------------------------------------------------------------------
        */

        $codigo =
            (int) (
                $actual['weather_code']
                ?? -1
            );


        /*
        |--------------------------------------------------------------------------
        | DESCRIPCIÓN
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | GUARDAR EN POSTGRESQL
        |--------------------------------------------------------------------------
        */

        $registroClima =
            Clima::updateOrCreate(

                [
                    'ubicacion' =>
                        $nombre,

                    'proveedor' =>
                        'open-meteo',
                ],

                [

                    'pais' =>
                        $pais,

                    'latitud' =>
                        $latitud,

                    'longitud' =>
                        $longitud,

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

                    'consultado_en' =>
                        now(),

                    'expira_en' =>
                        now()->addMinutes(15),

                ]
            );


        /*
        |--------------------------------------------------------------------------
        | RESULTADO
        |--------------------------------------------------------------------------
        */

        $resultado = [

            'ubicacion' => [

                'nombre' =>
                    $registroClima->ubicacion,

                'pais' =>
                    $registroClima->pais,

                'latitud' =>
                    $registroClima->latitud,

                'longitud' =>
                    $registroClima->longitud,

            ],

            'temperatura' =>
                $registroClima->temperatura,

            'sensacion' =>
                $registroClima->sensacion,

            'humedad' =>
                $registroClima->humedad,

            'viento' =>
                $registroClima->viento,

            'codigo' =>
                $registroClima->codigo,

            'descripcion' =>
                $registroClima->descripcion,

        ];


        return $this->respuestaClima(
            $resultado
        );


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

private function respuestaClima(
    array $resultado,
    bool $datoAnterior = false
): array {

    $ubicacion =
        $resultado['ubicacion']['nombre']
        ?? 'Lima';

    $pais =
        $resultado['ubicacion']['pais']
        ?? '';

    $temperatura =
        $resultado['temperatura']
        ?? null;

    $sensacion =
        $resultado['sensacion']
        ?? null;

    $humedad =
        $resultado['humedad']
        ?? null;

    $viento =
        $resultado['viento']
        ?? null;

    $descripcion =
        $resultado['descripcion']
        ?? 'Condiciones variables';


    $mensajeClima =
    $this->sigiAi->responder(

            'Informa brevemente al usuario sobre este clima actual. '
            . 'Ciudad: ' . $ubicacion . '. '
            . 'País: ' . $pais . '. '
            . 'Condición: ' . $descripcion . '. '
            . 'Temperatura: ' . $temperatura . ' °C. '
            . 'Sensación: ' . $sensacion . ' °C. '
            . 'Humedad: ' . $humedad . '%. '
            . 'Viento: ' . $viento . ' km/h.',

            'Eres Sigi. Redacta una respuesta breve, natural y amable '
            . 'en español. Usa únicamente los datos meteorológicos '
            . 'proporcionados. No inventes valores. No uses Markdown '
            . 'complejo porque la interfaz mostrará una tarjeta de clima.'
        );


    if ($mensajeClima === null) {

        $mensajeClima =
            'Clima actual en ' .
            $ubicacion .
            ': ' .
            $descripcion .
            ', ' .
            $temperatura .
            ' °C.';

    }


    if ($datoAnterior) {

        $mensajeClima .=
            ' Estos son los últimos datos disponibles porque '
            . 'el proveedor meteorológico no respondió en este momento.';

    }


    return [

        'success' => true,

        'tipo' => 'clima',

        'resultado' => $resultado,

        'mensaje' => $mensajeClima,

    ];
}
}