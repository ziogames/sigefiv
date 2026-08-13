<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Reporte Financiero</title>

</head>

<body>

@if($generarEstado)

    @foreach($estados as $estado)

        @php

            $ingresos = $estado['ingresos'];

            $egresos = $estado['egresos'];

            $resumen = $estado['resumen'];

            $maxFilas = $estado['maxFilas'];

            $mesEstado = $estado['mes'];

            $anioEstado = $estado['anio'];

        @endphp

        @include('pdf.estado')

        @if(
            !$loop->last ||
            $generarConsolidado
        )

            <div style="page-break-after: always;"></div>

        @endif

    @endforeach

@endif

@if($generarEstado && $generarConsolidado)

    <div style="page-break-before: always;"></div>

@endif


@if($generarConsolidado)

    @include('pdf.consolidado')

@endif

</body>

</html>