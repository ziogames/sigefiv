<!DOCTYPE html>

<html lang="es">

<head>

<meta charset="UTF-8">

<style>

*{

    font-family: DejaVu Sans;

    font-size:9px;

}

body{

    margin:10px;

}

table{

    width:100%;

    border-collapse:collapse;

}

th{

    border:1px solid #000;

    background:#E5E5E5;

    padding:4px;

    text-align:center;

    font-weight:bold;

}

td{

    border:1px solid #000;

    padding:4px;

}

.center{

    text-align:center;

}

.right{

    text-align:right;

}

.bold{

    font-weight:bold;

}

.titulo{

    text-align:center;

    font-size:18px;

    font-weight:bold;

}

/* ============================================================
   TÍTULO
   ============================================================ */

.subtitulo{
    text-align:center;
    font-size:11px;
    font-weight:bold;
    margin:0 0 3px 0;
    padding:0;
    line-height:1;
}


/* ============================================================
   BLOQUE COMPLETO DEL MES
   ============================================================ */

.bloque-mes{
    page-break-inside:avoid;
}

.bloque-mes .subtitulo{
    page-break-after:avoid;
}

.bloque-mes .estado{
    page-break-before:avoid;
}
.total{

    border-top:2px solid #000;

    border-bottom:2px solid #000;

    font-weight:bold;

}

</style>

</head>

<body>

<div class="titulo">

CONSOLIDADO DEL PERÍODO

</div>

<div class="subtitulo">

{{ strtoupper($meses[$desde]) }}

{{ $anioConsolidado }}

-

{{ strtoupper($meses[$hasta]) }}

{{ $anioConsolidado }}

</div>

<table>

<thead>

<tr>

<th width="18%">

Mes

</th>

<th width="20%">

Saldo Inicial

</th>

<th width="18%">

Ingresos

</th>

<th width="18%">

Egresos

</th>

<th width="18%">

Saldo Caja

</th>

<th width="8%">

Var.

</th>

</tr>

</thead>

<tbody>
    @php

    $saldoAnterior = null;

@endphp

@foreach($consolidado as $periodo)

@php

    $variacion = '';

    if(!is_null($saldoAnterior)){

        $diferencia = $periodo->saldo_final - $saldoAnterior;

        if($diferencia > 0){

            $variacion = '▲ '.number_format($diferencia,2);

        }elseif($diferencia < 0){

            $variacion = '▼ '.number_format(abs($diferencia),2);

        }else{

            $variacion = '-';

        }

    }else{

        $variacion='-';

    }

@endphp

<tr>

<td>

{{ strtoupper($meses[$periodo->mes]) }}

</td>

<td class="right">

{{ number_format($periodo->saldo_inicial,2) }}

</td>

<td class="right">

{{ number_format($periodo->total_ingresos,2) }}

</td>

<td class="right">

{{ number_format($periodo->total_egresos,2) }}

</td>

<td class="right bold">

{{ number_format($periodo->saldo_final,2) }}

</td>

<td class="center">

@if($variacion == '-')

—

@elseif(str_starts_with($variacion,'▲'))

<strong>{{ $variacion }}</strong>

@else

<strong>{{ $variacion }}</strong>

@endif

</td>

</tr>

@php

$saldoAnterior = $periodo->saldo_final;

@endphp

@endforeach
<tr>

    <td class="total">

        TOTAL

    </td>

    <td class="total right">

        {{ number_format(collect($consolidado)->first()->saldo_inicial ?? 0,2) }}

    </td>

    <td class="total right">

        {{ number_format(collect($consolidado)->sum('total_ingresos'),2) }}

    </td>

    <td class="total right">

        {{ number_format(collect($consolidado)->sum('total_egresos'),2) }}

    </td>

    <td class="total right">

        {{ number_format(collect($consolidado)->last()->saldo_final ?? 0,2) }}

    </td>

    <td class="total">

        &nbsp;

    </td>

</tr>

</tbody>

</table>