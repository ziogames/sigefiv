<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<style>

@page{
    margin:3mm 8mm;
}

*{
    font-family: DejaVu Sans;
    font-size:8px;
    line-height:1.05;
}

body{
    margin:0;
    padding:0;
    color:#000;
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
   TABLAS
   ============================================================ */

table{
    width:100%;
    border-collapse:collapse;
}

th{
    border:1px solid #000;
    background:#E5E5E5;
    padding:2px;
    text-align:center;
    font-weight:bold;
    line-height:1.05;
}

td{
    border:1px solid #000;
    padding:1px 2px;
    vertical-align:top;
    line-height:1.05;
}

.sin-borde td{
    border:none;
}


/* ============================================================
   TEXTO
   ============================================================ */

.center{
    text-align:center;
}

.right{
    text-align:right;
}

.bold{
    font-weight:bold;
}


/* ============================================================
   TOTALES
   ============================================================ */

.total{
    border-top:2px solid #000 !important;
    border-bottom:2px solid #000 !important;
    font-weight:bold;
    text-align:right;
    font-size:9px;
}

.total-texto{
    border-top:2px solid #000 !important;
    border-bottom:2px solid #000 !important;
    font-weight:bold;
    text-align:right;
}


/* ============================================================
   ESTRUCTURA PRINCIPAL
   ============================================================ */

.estado{
    width:100%;
    border-collapse:collapse;
    page-break-before:avoid;
}

.estado > tbody > tr > td{
    border:none;
    padding:0;
}


/* ============================================================
   RESUMEN FINAL
   ============================================================ */

.resumen{
    width:100%;
    margin:0;
    border-collapse:collapse;
}

.resumen th{
    background:#E5E5E5;
    border:2px solid #000;
    padding:2px;
}

.resumen td{
    border:2px solid #000;
    font-weight:bold;
    text-align:center;
    padding:3px 2px;
}


/* ============================================================
   EVITAR SEPARAR EL RESUMEN
   ============================================================ */

.resumen,
.resumen tr,
.resumen thead,
.resumen tbody{
    page-break-inside:avoid;
}

</style>

</head>


<body>


{{-- ============================================================
     TÍTULO DEL MES
     ============================================================ --}}

<div class="bloque-mes">

    <div class="subtitulo">

        {{ strtoupper($meses[$mesEstado]) }}
        {{ $anioEstado }}

    </div>


    {{-- ============================================================
         TABLA PRINCIPAL
         ============================================================ --}}

    <table class="estado">

<tbody>

<tr>

<td>


    {{-- ========================================================
         INGRESOS Y EGRESOS
         ======================================================== --}}

    <table class="sin-borde">

        <tr>


            {{-- ==================================================
                 INGRESOS
                 ================================================== --}}

            <td width="49%" valign="top">

                <table>

                    <thead>

                        <tr>

                            <th colspan="3">
                                INGRESOS
                            </th>

                        </tr>

                        <tr>

                            <th width="17%">
                                Fecha
                            </th>

                            <th>
                                Detalle
                            </th>

                            <th width="18%">
                                Importe
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    @for($i = 0; $i < $maxFilas; $i++)

                        @php

                            $ingreso = $ingresos[$i] ?? null;

                        @endphp


                        <tr>

                            @if($ingreso)

                                <td class="center">

                                    {{ \Carbon\Carbon::parse($ingreso->fecha)->format('d/m/Y') }}

                                </td>


                                <td>

                                    <strong>

                                        [{{ strtoupper($ingreso->categoria->nombre) }}]

                                    </strong>

                                    {{ $ingreso->concepto }}

                                </td>


                                <td class="right">

                                    {{ number_format($ingreso->monto,2) }}

                                </td>


                            @else

                                <td>&nbsp;</td>
                                <td></td>
                                <td></td>

                            @endif

                        </tr>

                    @endfor


                    <tr>

                        <td
                            colspan="2"
                            class="total-texto">

                            TOTAL INGRESOS

                        </td>


                        <td class="total">

                            {{ number_format($resumen['ingresos'],2) }}

                        </td>

                    </tr>


                    </tbody>

                </table>

            </td>


            {{-- Separación --}}

            <td width="2%"></td>


            {{-- ==================================================
                 EGRESOS
                 ================================================== --}}

            <td width="49%" valign="top">

                <table>

                    <thead>

                        <tr>

                            <th colspan="3">
                                EGRESOS
                            </th>

                        </tr>


                        <tr>

                            <th width="17%">
                                Fecha
                            </th>

                            <th>
                                Detalle
                            </th>

                            <th width="18%">
                                Importe
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    @for($i = 0; $i < $maxFilas; $i++)

                        @php

                            $egreso = $egresos[$i] ?? null;

                        @endphp


                        <tr>

                            @if($egreso)

                                <td class="center">

                                    {{ \Carbon\Carbon::parse($egreso->fecha)->format('d/m/Y') }}

                                </td>


                                <td>

                                    <strong>

                                        [{{ strtoupper($egreso->categoria->nombre) }}]

                                    </strong>

                                    {{ $egreso->concepto }}

                                </td>


                                <td class="right">

                                    {{ number_format($egreso->monto,2) }}

                                </td>


                            @else

                                <td>&nbsp;</td>
                                <td></td>
                                <td></td>

                            @endif

                        </tr>

                    @endfor


                    <tr>

                        <td
                            colspan="2"
                            class="total-texto">

                            TOTAL EGRESOS

                        </td>


                        <td class="total">

                            {{ number_format($resumen['egresos'],2) }}

                        </td>

                    </tr>


                    </tbody>

                </table>

            </td>


        </tr>

    </table>


    {{-- ========================================================
         ESPACIO DINÁMICO ANTES DEL RESUMEN
         ======================================================== --}}

 





    {{-- ========================================================
         RESUMEN
         ======================================================== --}}

    <table class="resumen">

        <thead>

            <tr>

                <th width="20%">
                    Saldo Inicial
                </th>

                <th width="20%">
                    Total Ingresos
                </th>

                <th width="20%">
                    Disponible
                </th>

                <th width="20%">
                    Total Egresos
                </th>

                <th width="20%">
                    Saldo Caja
                </th>

            </tr>

        </thead>


        <tbody>

            <tr>

                <td class="center bold">

                    {{ number_format($resumen['saldo_inicial'],2) }}

                </td>


                <td class="center bold">

                    {{ number_format($resumen['ingresos'],2) }}

                </td>


                <td class="center bold">

                    {{ number_format($resumen['disponible'],2) }}

                </td>


                <td class="center bold">

                    {{ number_format($resumen['egresos'],2) }}

                </td>


                <td class="center bold">

                    {{ number_format($resumen['saldo_caja'],2) }}

                </td>

            </tr>

  </tbody>

</table>


</td>

</tr>

</tbody>

</table>

</div>


</body>