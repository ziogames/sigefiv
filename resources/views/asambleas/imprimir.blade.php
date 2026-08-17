<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Citaciones - {{ $asamblea->titulo }}
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        @page {
            size: A4 portrait;
            margin: 8mm;
        }


        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }


        .hoja {
            width: 194mm;
            height: 281mm;

            display: grid;

            grid-template-columns: 1fr 1fr;
            grid-template-rows: repeat(3, 1fr);

            gap: 4mm;
        }


        .citacion {

            border: 0.4mm solid #555;

            padding: 4mm;

            position: relative;

            overflow: hidden;

            display: flex;

            flex-direction: column;
        }


        .titulo {

            text-align: center;

            font-size: 13pt;

            font-weight: bold;

            margin-bottom: 3mm;

            text-transform: uppercase;

            letter-spacing: 0.3mm;
        }


        .linea {
            border-top: 0.3mm solid #777;
            margin: 2mm 0;
        }


        .texto {

            font-size: 8.5pt;

            line-height: 1.35;

            text-align: justify;

            margin-bottom: 2mm;
        }


        .datos {

            font-size: 8.5pt;

            line-height: 1.45;

            margin-top: 1mm;
        }


        .dato {

            margin-bottom: 1mm;
        }


        .etiqueta {
            font-weight: bold;
        }


        .citaciones {

            display: flex;

            justify-content: center;

            gap: 8mm;

            margin: 2mm 0;

            font-size: 8.5pt;

            font-weight: bold;
        }


        .agenda-titulo {

            font-size: 9pt;

            font-weight: bold;

            margin-top: 1mm;

            margin-bottom: 1mm;

        }


        .agenda {

            margin: 0;

            padding-left: 5mm;

            font-size: 7.8pt;

            line-height: 1.3;
        }


        .agenda li {

            margin-bottom: 1mm;

        }


        .firma {

            margin-top: auto;

            text-align: center;

            font-size: 7.5pt;

            padding-top: 2mm;

        }


        .firma-linea {

            width: 35mm;

            border-top: 0.3mm solid #555;

            margin: 0 auto 1mm auto;

        }


        .controles {

            position: fixed;

            top: 10px;

            right: 10px;

            z-index: 9999;

            display: flex;

            gap: 8px;
        }


        .btn {

            border: none;

            border-radius: 5px;

            padding: 9px 15px;

            cursor: pointer;

            font-size: 14px;

            font-weight: bold;
        }


        .btn-imprimir {
            background: #0d6efd;
            color: white;
        }


        .btn-cerrar {
            background: #6c757d;
            color: white;
        }


        @media print {

            .controles {
                display: none !important;
            }

            html,
            body {
                width: 210mm;
                height: 297mm;
            }

            .hoja {
                width: 194mm;
                height: 281mm;
            }

        }

    </style>

</head>


<body>


    {{-- Botones solamente visibles en pantalla --}}
    <div class="controles">

        <button
            type="button"
            class="btn btn-imprimir"
            onclick="window.print()"
        >
            🖨️ Imprimir
        </button>

        <button
            type="button"
            class="btn btn-cerrar"
            onclick="window.close()"
        >
            ✕ Cerrar
        </button>

    </div>


    <div class="hoja">


        {{-- ========================================================= --}}
        {{-- CITACIONES --}}
        {{-- ========================================================= --}}

        @for($i = 1; $i <= 6; $i++)

            <div class="citacion">


                {{-- TÍTULO --}}
                <div class="titulo">
                    CITACIÓN
                </div>


                <div class="linea"></div>


                {{-- TEXTO --}}
                <div class="texto">

                    @if($asamblea->importancia === 'urgente')

                        Se cita con carácter de
                        <strong>URGENCIA</strong>

                    @elseif($asamblea->importancia === 'importante')

                        Se cita con carácter
                        <strong>IMPORTANTE</strong>

                    @else

                        Se cita a

                    @endif


                    a la

                    <strong>
                        {{ $asamblea->titulo }}
                    </strong>.


                    @if($asamblea->sector || $asamblea->grupo || $asamblea->manzana || $asamblea->lote)

                        @if($asamblea->sector)
                            Sector {{ $asamblea->sector }}
                        @endif

                        @if($asamblea->grupo)
                            Grupo {{ $asamblea->grupo }}
                        @endif

                        @if($asamblea->manzana)
                            Mz. {{ $asamblea->manzana }}
                        @endif

                        @if($asamblea->lote)
                            Lote {{ $asamblea->lote }}
                        @endif

                    @endif

                </div>


                {{-- FECHA --}}
                <div class="datos">

                    <div class="dato">

                        <span class="etiqueta">
                            DÍA:
                        </span>

                        {{ $asamblea->fecha?->locale('es')->translatedFormat('l d \d\e F \d\e\l Y') }}

                    </div>


                    {{-- CITACIONES --}}
                    <div class="citaciones">

                        <span>
                            1ra citación:
                            {{ $asamblea->primera_citacion?->format('g:i a') }}
                        </span>

                        <span>
                            2da citación:
                            {{ $asamblea->segunda_citacion?->format('g:i a') }}
                        </span>

                    </div>


                    {{-- LUGAR --}}
                    <div class="dato">

                        <span class="etiqueta">
                            LUGAR:
                        </span>

                        {{ $asamblea->lugar }}

                    </div>

                </div>


                <div class="linea"></div>


                {{-- AGENDA --}}
                <div class="agenda-titulo">
                    AGENDA:
                </div>


                @if($asamblea->agendas->count())

                    <ol class="agenda">

                        @foreach($asamblea->agendas as $agenda)

                            <li>
                                {{ $agenda->descripcion }}
                            </li>

                        @endforeach

                    </ol>

                @else

                    <div class="agenda">
                        No se ha registrado agenda.
                    </div>

                @endif


                {{-- FIRMA --}}
                <div class="firma">

                    <div class="firma-linea"></div>

                    {{ $asamblea->convoca }}

                </div>


            </div>

        @endfor


    </div>


</body>

</html>