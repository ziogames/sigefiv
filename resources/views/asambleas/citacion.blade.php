<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Citación Vecinal | SIGEFIV
    </title>


    {{-- ================================================================
         TIPOGRAFÍAS
         ================================================================ --}}

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;500;600;700&family=Fredoka:wght@400;500;600;700&family=Nunito:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
        }


        html,
        body {

            margin: 0;
            padding: 0;

            width: 100%;

            background: #ffffff;

        }


        body {

            font-family:
                'Nunito',
                Arial,
                sans-serif;

            color: #173f5f;

        }


        /*
        |--------------------------------------------------------------------------
        | PÁGINA
        |--------------------------------------------------------------------------
        */

        .pagina {

            width: 100%;

            margin: 0;

            padding: 0;

            display: flex;

            justify-content: center;

        }


        /*
        |--------------------------------------------------------------------------
        | CITACIÓN
        |
        | IMPORTANTE:
        | No usamos background-image.
        | La imagen estará como <img> real.
        | Así nunca se deforma.
        |--------------------------------------------------------------------------
        */

        .citacion {

            position: relative;

            width: 100%;

            max-width: 1080px;

            margin: 0 auto;

            overflow: hidden;

            line-height: normal;

        }


        /*
        |--------------------------------------------------------------------------
        | IMAGEN REAL DEL FONDO
        |--------------------------------------------------------------------------
        */

        .imagen-fondo {

            display: block;

            width: 100%;

            height: auto;

            max-width: 100%;

            margin: 0;

            padding: 0;

            object-fit: contain;

        }


        /*
        |--------------------------------------------------------------------------
        | CONTENIDO SOBRE LA IMAGEN
        |--------------------------------------------------------------------------
        */

        .contenido {

            position: absolute;

            inset: 0;

            width: 100%;

            height: 100%;

            padding: 5.5% 7% 4%;

            display: flex;

            flex-direction: column;

        }


        /*
        |--------------------------------------------------------------------------
        | ENCABEZADO
        |--------------------------------------------------------------------------
        */

        .encabezado {

            width: 100%;

            text-align: center;

            margin: 0;

            padding: 0;

        }


        .encabezado h1 {

            margin: 0;

            padding: 0;

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(32px, 5vw, 68px);

            line-height: .95;

            font-weight: 700;

            letter-spacing: .5px;

            color: #ef4b23;

            text-shadow:
                2px 2px 0 #ffffff,
                -1px -1px 0 #ffffff,
                1px -1px 0 #ffffff,
                -1px 1px 0 #ffffff;

        }


        .encabezado .subtitulo {

            margin: 5px 0 0;

            padding: 0;

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(25px, 3.5vw, 48px);

            line-height: 1;

            font-weight: 600;

            color: #1479a8;

            letter-spacing: .5px;

            text-shadow:
                2px 2px 0 #ffffff,
                -1px -1px 0 #ffffff,
                1px -1px 0 #ffffff,
                -1px 1px 0 #ffffff;

        }


        /*
        |--------------------------------------------------------------------------
        | ESPACIO PRINCIPAL
        |--------------------------------------------------------------------------
        */

        .contenido-principal {

            width: 100%;

            margin-top: 3%;

            display: flex;

            flex-direction: column;

            align-items: center;

        }


        /*
        |--------------------------------------------------------------------------
        | TÍTULO
        |--------------------------------------------------------------------------
        */

        .titulo {

            margin: 0;

            padding: 0;

            text-align: center;

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(24px, 3.3vw, 43px);

            line-height: 1.05;

            font-weight: 600;

            color: #173f5f;

            text-shadow:
                1px 1px 0 #ffffff,
                -1px -1px 0 #ffffff,
                1px -1px 0 #ffffff,
                -1px 1px 0 #ffffff;

        }


        /*
        |--------------------------------------------------------------------------
        | DESCRIPCIÓN
        |--------------------------------------------------------------------------
        */

        .descripcion {

            width: 88%;

            margin-top: 2%;

            padding: 0;

            text-align: center;

            font-family:
                'Caveat',
                cursive;

            font-size:
                clamp(20px, 2.7vw, 34px);

            line-height: 1.15;

            font-weight: 600;

            color: #4c281f;

            text-shadow:
                1px 1px 0 #ffffff,
                -1px -1px 0 #ffffff,
                1px -1px 0 #ffffff,
                -1px 1px 0 #ffffff;

        }


        /*
        |--------------------------------------------------------------------------
        | DATOS
        |--------------------------------------------------------------------------
        */

        .datos {

            width: 90%;

            margin-top: 3%;

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 5%;

        }


        .dato {

            padding: 0;

            background: transparent;

            border: none;

            text-align: left;

        }


        .dato-label {

            display: block;

            margin: 0 0 3px;

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(12px, 1.6vw, 19px);

            line-height: 1;

            font-weight: 600;

            text-transform: uppercase;

            color: #ef6c24;

            text-shadow:
                1px 1px 0 #ffffff;

        }


        .dato-valor {

            display: block;

            font-family:
                'Nunito',
                sans-serif;

            font-size:
                clamp(15px, 2vw, 25px);

            line-height: 1.1;

            font-weight: 800;

            color: #173f5f;

            text-shadow:
                1px 1px 0 #ffffff,
                -1px -1px 0 #ffffff;

        }


        /*
        |--------------------------------------------------------------------------
        | CITACIONES
        |--------------------------------------------------------------------------
        */

        .citaciones {

            width: 90%;

            margin-top: 3%;

            display: grid;

            gap: 5%;

            align-items: center;

            justify-content: center;

        }


        .citaciones.una {

            grid-template-columns: 1fr;

        }


        .citaciones.dos {

            grid-template-columns:
                repeat(2, 1fr);

        }


        .citacion-hora {

            text-align: center;

            padding: 0;

            background: transparent;

            border: none;

        }


        .citacion-hora strong {

            display: block;

            margin: 0 0 3px;

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(14px, 1.8vw, 23px);

            line-height: 1;

            font-weight: 600;

            color: #c94a20;

            text-shadow:
                1px 1px 0 #ffffff;

        }


        .citacion-hora.segunda strong {

            color: #245b80;

        }


        .hora {

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(25px, 3vw, 42px);

            line-height: 1;

            font-weight: 700;

            color: #cf392d;

            text-shadow:
                2px 2px 0 #ffffff,
                -1px -1px 0 #ffffff;

        }


        .citacion-hora.segunda .hora {

            color: #245b80;

        }


        .hora small {

            font-family:
                'Fredoka',
                sans-serif;

            font-size: .65em;

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | LUGAR
        |--------------------------------------------------------------------------
        */

        .lugar {

            margin-top: 3%;

            text-align: center;

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(20px, 2.7vw, 34px);

            line-height: 1.05;

            font-weight: 600;

            color: #28743a;

            text-shadow:
                2px 2px 0 #ffffff,
                -1px -1px 0 #ffffff;

        }


        /*
        |--------------------------------------------------------------------------
        | AGENDA
        |--------------------------------------------------------------------------
        */

        .agenda-titulo {

            width: 88%;

            margin-top: 3%;

            padding: 0;

            text-align: center;

            font-family:
                'Fredoka',
                sans-serif;

            font-size:
                clamp(25px, 3.5vw, 44px);

            line-height: 1;

            font-weight: 700;

            color: #ef5d1a;

            letter-spacing: 1px;

            text-shadow:
                2px 2px 0 #ffffff,
                -1px -1px 0 #ffffff;

        }


        .agenda {

            width: 88%;

            margin: 1.5% 0 0;

            padding: 0;

            list-style: none;

        }


        .agenda li {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            margin: 0 0 1.2%;

            padding: 0;

            background: transparent;

            border: none;

            font-family:
                'Nunito',
                sans-serif;

            font-size:
                clamp(14px, 1.8vw, 22px);

            line-height: 1.2;

            font-weight: 700;

            color: #40271e;

            text-shadow:
                1px 1px 0 #ffffff;

        }


        .agenda-check {

            display: flex;

            align-items: center;

            justify-content: center;

            width:
                clamp(24px, 3vw, 38px);

            height:
                clamp(24px, 3vw, 38px);

            min-width:
                clamp(24px, 3vw, 38px);

            border-radius: 50%;

            background: #35a64a;

            color: #ffffff;

            font-size:
                clamp(15px, 2vw, 24px);

            line-height: 1;

            font-weight: 900;

            text-shadow: none;

        }


        /*
        |--------------------------------------------------------------------------
        | PIE
        |--------------------------------------------------------------------------
        */

        .pie {

            margin-top: auto;

            padding-top: 2%;

            text-align: center;

            font-family:
                'Nunito',
                sans-serif;

            font-size:
                clamp(10px, 1.2vw, 15px);

            line-height: 1;

            font-weight: 700;

            color: #28618a;

            text-shadow:
                1px 1px 0 #ffffff;

        }


        /*
        |--------------------------------------------------------------------------
        | PANTALLAS PEQUEÑAS
        |--------------------------------------------------------------------------
        */

        @media (max-width: 600px) {


            .contenido {

                padding:
                    5% 6% 4%;

            }


            .contenido-principal {

                margin-top: 2%;

            }


            .datos {

                width: 94%;

                gap: 3%;

            }


            .citaciones {

                width: 94%;

            }


            .agenda {

                width: 94%;

            }


            .agenda-titulo {

                width: 94%;

            }


            .descripcion {

                width: 94%;

            }


            .lugar {

                margin-top: 2.5%;

            }


        }


        /*
        |--------------------------------------------------------------------------
        | IMPRESIÓN
        |--------------------------------------------------------------------------
        */

        @media print {


            html,
            body {

                background: #ffffff;

            }


            .pagina {

                padding: 0;

            }


            .citacion {

                max-width: none;

                width: 100%;

            }


        }

    </style>

</head>


<body>


@php

    /*
    |--------------------------------------------------------------------------
    | PLANTILLA
    |--------------------------------------------------------------------------
    */

    $plantilla = (int) (
        $asamblea->plantilla_citacion ?? 1
    );


    if (
        $plantilla < 1 ||
        $plantilla > 7
    ) {

        $plantilla = 1;

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGEN DE FONDO
    |--------------------------------------------------------------------------
    */

    $imagenFondo =
        asset(
            "assets/asambleas/fondos/fondo-{$plantilla}.jpg"
        );


    /*
    |--------------------------------------------------------------------------
    | DÍAS EN ESPAÑOL
    |--------------------------------------------------------------------------
    */

    $dias = [

        'Sunday' => 'domingo',

        'Monday' => 'lunes',

        'Tuesday' => 'martes',

        'Wednesday' => 'miércoles',

        'Thursday' => 'jueves',

        'Friday' => 'viernes',

        'Saturday' => 'sábado',

    ];


    /*
    |--------------------------------------------------------------------------
    | MESES EN ESPAÑOL
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | FECHA
    |--------------------------------------------------------------------------
    */

    $diaSemana =
        $dias[
            $asamblea->fecha->format('l')
        ];


    $dia =
        $asamblea->fecha->format('d');


    $mes =
        $meses[
            (int)
            $asamblea->fecha->format('n')
        ];


    $anio =
        $asamblea->fecha->format('Y');


    /*
    |--------------------------------------------------------------------------
    | PRIMERA CITACIÓN
    |--------------------------------------------------------------------------
    */

    $horaPrimera = null;


    if ($asamblea->primera_citacion) {

        $horaPrimera =
            $asamblea
                ->primera_citacion
                ->format('h:i');

    }


    /*
    |--------------------------------------------------------------------------
    | SEGUNDA CITACIÓN
    |--------------------------------------------------------------------------
    */

    $horaSegunda = null;


    if ($asamblea->segunda_citacion) {

        $horaSegunda =
            $asamblea
                ->segunda_citacion
                ->format('h:i');

    }


@endphp


<div class="pagina">


    <main class="citacion">


        {{-- ================================================================
             IMAGEN COMPLETA
             ================================================================ --}}

        <img
            src="{{ $imagenFondo }}"
            alt="Fondo de citación vecinal"
            class="imagen-fondo"
        >


        {{-- ================================================================
             CONTENIDO SOBRE LA IMAGEN
             ================================================================ --}}

        <div class="contenido">


            {{-- ============================================================
                 ENCABEZADO
                 ============================================================ --}}

            <header class="encabezado">

                <h1>

                    CITACIÓN

                </h1>


                <div class="subtitulo">

                    VECINAL

                </div>

            </header>


            <div class="contenido-principal">


                {{-- ========================================================
                     TÍTULO
                     ======================================================== --}}

                <h2 class="titulo">

                    {{ $asamblea->titulo }}

                </h2>


                {{-- ========================================================
                     DESCRIPCIÓN
                     ======================================================== --}}

                @if(
                    $asamblea->descripcion
                )

                    <div class="descripcion">

                        {!! nl2br(
                            e(
                                $asamblea->descripcion
                            )
                        ) !!}

                    </div>

                @endif


                {{-- ========================================================
                     DATOS
                     ======================================================== --}}

                <div class="datos">


                    <div class="dato">

                        <span class="dato-label">

                            Fecha

                        </span>


                        <span class="dato-valor">

                            {{ $diaSemana }}

                            {{ $dia }}

                            de

                            {{ $mes }}

                            de

                            {{ $anio }}

                        </span>

                    </div>


                    <div class="dato">

                        <span class="dato-label">

                            Convoca

                        </span>


                        <span class="dato-valor">

                            {{ $asamblea->convoca }}

                        </span>

                    </div>


                </div>


                {{-- ========================================================
                     CITACIONES
                     ======================================================== --}}

                @if($horaSegunda)

                    <div class="citaciones dos">

                @else

                    <div class="citaciones una">

                @endif


                    {{-- PRIMERA CITACIÓN --}}

                    @if($horaPrimera)

                        <div class="citacion-hora">


                            <strong>

                                1° CITACIÓN

                            </strong>


                            <div class="hora">

                                {{ $horaPrimera }}

                                <small>

                                    p. m.

                                </small>

                            </div>


                        </div>

                    @endif


                    {{-- SEGUNDA CITACIÓN --}}

                    @if($horaSegunda)

                        <div
                            class="citacion-hora segunda"
                        >


                            <strong>

                                2° CITACIÓN

                            </strong>


                            <div class="hora">

                                {{ $horaSegunda }}

                                <small>

                                    p. m.

                                </small>

                            </div>


                        </div>

                    @endif


                </div>


                {{-- ========================================================
                     LUGAR
                     ======================================================== --}}

                @if($asamblea->lugar)

                    <div class="lugar">

                        📍

                        {{ $asamblea->lugar }}

                    </div>

                @endif


                {{-- ========================================================
                     AGENDA
                     ======================================================== --}}

                @if(
                    $asamblea->agendas &&
                    $asamblea->agendas->count()
                )


                    <div class="agenda-titulo">

                        📋 AGENDA

                    </div>


                    <ol class="agenda">


                        @foreach(
                            $asamblea->agendas
                            as $agenda
                        )


                            <li>


                                <span
                                    class="agenda-check"
                                >

                                    ✓

                                </span>


                                <span>

                                    {{ $agenda->descripcion }}

                                </span>


                            </li>


                        @endforeach


                    </ol>


                @endif


                {{-- ========================================================
                     PIE
                     ======================================================== --}}

                <div class="pie">

                    Convocatoria generada por SIGEFIV

                </div>


            </div>


        </div>


    </main>


</div>


</body>

</html>