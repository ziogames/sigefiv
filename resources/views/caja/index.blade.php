@extends('layouts.app')

@section('title', 'Caja')

@section('content')

@php

    $saldoInicial =
        $consolidado[0]->saldo_inicial ?? 0;

    $totalIngresos =
        collect($consolidado)->sum('total_ingresos');

    $totalEgresos =
        collect($consolidado)->sum('total_egresos');

    $saldoFinal =
        collect($consolidado)->last()->saldo_final ?? 0;

    $totalMovimiento = $totalIngresos + $totalEgresos;

    $porcentajeIngresos = $totalMovimiento > 0
        ? ($totalIngresos / $totalMovimiento) * 100
        : 0;

    $porcentajeEgresos = $totalMovimiento > 0
        ? ($totalEgresos / $totalMovimiento) * 100
        : 0;

@endphp


<div class="container-fluid py-4 caja-page">

    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <div class="card caja-header border-0 shadow-sm mb-4">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div class="d-flex align-items-center gap-3">

                    <div class="caja-header-icon">

                        <i class="cil-wallet"></i>

                    </div>

                    <div>

                        <div class="d-flex align-items-center gap-2">

                            <h2 class="mb-0 fw-bold">
                                Caja
                            </h2>

                            <span class="caja-status">
                                Consolidado
                            </span>

                        </div>

                        <p class="text-body-secondary mb-0 mt-1">
                            Balance financiero del año {{ $anio }}
                        </p>

                    </div>

                </div>


                {{-- SELECTOR DE AÑO --}}

                <form method="GET">

                    <div class="caja-year-selector">

                        <label>
                            Año
                        </label>

                        <select
                            name="anio"
                            class="form-select"
                            onchange="this.form.submit()">

                            @foreach($anios as $item)

                                <option
                                    value="{{ $item }}"
                                    @selected($anio == $item)>

                                    {{ $item }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                </form>

            </div>

        </div>

    </div>



    {{-- =========================================================
         TARJETAS RESUMEN
    ========================================================== --}}

    <div class="row g-3 mb-4">


        {{-- SALDO INICIAL --}}

        <div class="col-xl-3 col-md-6">

            <div class="card caja-summary-card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <span class="caja-label">
                                Saldo inicial
                            </span>

                            <h3 class="caja-value mt-2 mb-0">

                                S/
                                {{ number_format($saldoInicial, 2) }}

                            </h3>

                        </div>

                        <div class="caja-card-icon caja-icon-blue">

                            <i class="cil-wallet"></i>

                        </div>

                    </div>

                    <div class="caja-card-footer mt-3">

                        Inicio del período

                    </div>

                </div>

            </div>

        </div>



        {{-- INGRESOS --}}

        <div class="col-xl-3 col-md-6">

            <div class="card caja-summary-card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <span class="caja-label">
                                Ingresos del año
                            </span>

                            <h3 class="caja-value text-success mt-2 mb-0">

                                + S/
                                {{ number_format($totalIngresos, 2) }}

                            </h3>

                        </div>

                        <div class="caja-card-icon caja-icon-green">

                            <i class="cil-arrow-top"></i>

                        </div>

                    </div>

                    <div class="caja-card-footer mt-3">

                        Total recibido

                    </div>

                </div>

            </div>

        </div>



        {{-- EGRESOS --}}

        <div class="col-xl-3 col-md-6">

            <div class="card caja-summary-card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <span class="caja-label">
                                Egresos del año
                            </span>

                            <h3 class="caja-value text-danger mt-2 mb-0">

                                - S/
                                {{ number_format($totalEgresos, 2) }}

                            </h3>

                        </div>

                        <div class="caja-card-icon caja-icon-red">

                            <i class="cil-arrow-bottom"></i>

                        </div>

                    </div>

                    <div class="caja-card-footer mt-3">

                        Total gastado

                    </div>

                </div>

            </div>

        </div>



        {{-- SALDO FINAL --}}

        <div class="col-xl-3 col-md-6">

            <div class="card caja-summary-card caja-final-card h-100 border-0 shadow-sm">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-start">

                        <div>

                            <span class="caja-label">
                                Saldo en caja
                            </span>

                            <h3
                                class="caja-value mt-2 mb-0
                                {{ $saldoFinal >= 0
                                    ? 'text-warning'
                                    : 'text-danger' }}">

                                S/
                                {{ number_format($saldoFinal, 2) }}

                            </h3>

                        </div>

                        <div class="caja-card-icon caja-icon-orange">

                            <i class="cil-home"></i>

                        </div>

                    </div>

                    <div class="caja-card-footer mt-3">

                        Cierre del período

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- =========================================================
         RESUMEN VISUAL
    ========================================================== --}}

    <div class="card border-0 shadow-sm mb-4 caja-overview">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <div>

                    <h5 class="mb-1 fw-bold">
                        Resumen financiero
                    </h5>

                    <small class="text-body-secondary">
                        Distribución de los movimientos registrados en {{ $anio }}
                    </small>

                </div>

                <i class="cil-chart-pie caja-overview-icon"></i>

            </div>


            <div class="caja-progress">

                <div
                    class="caja-progress-income"
                    style="width: {{ $porcentajeIngresos }}%;">

                </div>

                <div
                    class="caja-progress-expense"
                    style="width: {{ $porcentajeEgresos }}%;">

                </div>

            </div>


            <div class="row mt-3">

                <div class="col-md-6">

                    <div class="d-flex align-items-center gap-2">

                        <span class="caja-dot caja-dot-green"></span>

                        <span class="text-body-secondary">
                            Ingresos
                        </span>

                        <strong class="ms-auto">
                            {{ number_format($porcentajeIngresos, 1) }}%
                        </strong>

                    </div>

                </div>


                <div class="col-md-6">

                    <div class="d-flex align-items-center gap-2">

                        <span class="caja-dot caja-dot-red"></span>

                        <span class="text-body-secondary">
                            Egresos
                        </span>

                        <strong class="ms-auto">
                            {{ number_format($porcentajeEgresos, 1) }}%
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- =========================================================
         CONSOLIDADO MENSUAL
    ========================================================== --}}

    <div class="card border-0 shadow-sm caja-table-card">


        {{-- CABECERA --}}

        <div class="card-header caja-table-header">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                <div class="d-flex align-items-center gap-3">

                    <div class="caja-table-icon">

                        <i class="cil-chart-line"></i>

                    </div>

                    <div>

                        <h5 class="mb-0 fw-bold">
                            Consolidado mensual
                        </h5>

                        <small class="text-body-secondary">
                            Evolución de caja durante {{ $anio }}
                        </small>

                    </div>

                </div>


                <span class="caja-year-badge">

                    {{ $anio }}

                </span>

            </div>

        </div>



        <div class="table-responsive">

            <table class="table caja-table align-middle mb-0">

                <thead>

                    <tr>

                        <th>
                            Mes
                        </th>

                        <th class="text-end">
                            Saldo inicial
                        </th>

                        <th class="text-end">
                            Ingresos
                        </th>

                        <th class="text-end">
                            Egresos
                        </th>

                        <th class="text-end">
                            Saldo final
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($consolidado as $fila)

                        <tr>

                            <td>

                                <div class="caja-month">

                                    <span class="caja-month-dot"></span>

                                    <strong>
                                        {{ $meses[$fila->mes] ?? $fila->mes }}
                                    </strong>

                                </div>

                            </td>


                            <td class="text-end">

                                <span class="caja-money">

                                    S/
                                    {{ number_format(
                                        $fila->saldo_inicial,
                                        2
                                    ) }}

                                </span>

                            </td>


                            <td class="text-end">

                                <span class="caja-income">

                                    +
                                    S/
                                    {{ number_format(
                                        $fila->total_ingresos,
                                        2
                                    ) }}

                                </span>

                            </td>


                            <td class="text-end">

                                <span class="caja-expense">

                                    -
                                    S/
                                    {{ number_format(
                                        $fila->total_egresos,
                                        2
                                    ) }}

                                </span>

                            </td>


                            <td class="text-end">

                                <span
                                    class="caja-balance
                                    {{ $fila->saldo_final >= 0
                                        ? 'positive'
                                        : 'negative' }}">

                                    S/
                                    {{ number_format(
                                        $fila->saldo_final,
                                        2
                                    ) }}

                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="text-center py-5">

                                <div class="caja-empty">

                                    <i class="cil-wallet"></i>

                                    <h5 class="mt-3">
                                        No existen datos
                                    </h5>

                                    <p class="text-body-secondary mb-0">
                                        No existen datos de caja
                                        para {{ $anio }}.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>


                @if(count($consolidado) > 0)

                    <tfoot>

                        <tr>

                            <th>
                                TOTAL
                            </th>

                            <th></th>

                            <th class="text-end">

                                <span class="caja-total-income">

                                    S/
                                    {{ number_format(
                                        $totalIngresos,
                                        2
                                    ) }}

                                </span>

                            </th>

                            <th class="text-end">

                                <span class="caja-total-expense">

                                    S/
                                    {{ number_format(
                                        $totalEgresos,
                                        2
                                    ) }}

                                </span>

                            </th>

                            <th class="text-end">

                                <span class="caja-total-balance">

                                    S/
                                    {{ number_format(
                                        $saldoFinal,
                                        2
                                    ) }}

                                </span>

                            </th>

                        </tr>

                    </tfoot>

                @endif

            </table>

        </div>

    </div>

</div>



{{-- =============================================================
     ESTILOS DE CAJA
============================================================= --}}

<style>

.caja-page {
    --caja-border: rgba(255,255,255,.07);
}


/* ENCABEZADO */

.caja-header {
    background:
        linear-gradient(
            135deg,
            rgba(38,51,73,.95),
            rgba(28,35,50,.98)
        );

    border: 1px solid var(--caja-border) !important;
}


.caja-header-icon {
    width: 56px;
    height: 56px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background: rgba(59,130,246,.15);

    color: #60a5fa;

    font-size: 25px;
}


.caja-status {
    font-size: 11px;

    padding: 4px 9px;

    border-radius: 20px;

    background: rgba(34,197,94,.12);

    color: #4ade80;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: .4px;
}


.caja-year-selector {
    display: flex;

    align-items: center;

    gap: 10px;
}


.caja-year-selector label {
    font-size: 13px;

    color: var(--cui-body-color);
}


.caja-year-selector .form-select {
    min-width: 100px;

    border-radius: 10px;

    background-color: rgba(255,255,255,.04);

    border-color: rgba(255,255,255,.10);
}



/* TARJETAS */

.caja-summary-card {
    background: rgba(31,39,54,.88);

    border: 1px solid var(--caja-border) !important;

    transition:
        transform .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;
}


.caja-summary-card:hover {
    transform: translateY(-3px);

    border-color: rgba(96,165,250,.25) !important;

    box-shadow: 0 10px 25px rgba(0,0,0,.18) !important;
}


.caja-final-card {
    border-color: rgba(245,158,11,.15) !important;
}


.caja-label {
    font-size: 13px;

    color: var(--cui-secondary-color);
}


.caja-value {
    font-size: 26px;

    font-weight: 700;

    letter-spacing: -.5px;
}


.caja-card-icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    font-size: 18px;
}


.caja-icon-blue {
    background: rgba(59,130,246,.13);
    color: #60a5fa;
}


.caja-icon-green {
    background: rgba(34,197,94,.13);
    color: #4ade80;
}


.caja-icon-red {
    background: rgba(239,68,68,.13);
    color: #f87171;
}


.caja-icon-orange {
    background: rgba(245,158,11,.13);
    color: #fbbf24;
}


.caja-card-footer {
    font-size: 11px;

    color: var(--cui-secondary-color);

    border-top: 1px solid var(--caja-border);

    padding-top: 10px;
}



/* RESUMEN */

.caja-overview {
    background: rgba(31,39,54,.88);

    border: 1px solid var(--caja-border) !important;
}


.caja-overview-icon {
    font-size: 22px;

    color: #60a5fa;
}


.caja-progress {
    display: flex;

    width: 100%;

    height: 9px;

    overflow: hidden;

    border-radius: 20px;

    background: rgba(255,255,255,.05);
}


.caja-progress-income {
    background: #22c55e;
}


.caja-progress-expense {
    background: #ef4444;
}


.caja-dot {
    width: 9px;
    height: 9px;

    border-radius: 50%;

    display: inline-block;
}


.caja-dot-green {
    background: #22c55e;
}


.caja-dot-red {
    background: #ef4444;
}



/* TABLA */

.caja-table-card {
    overflow: hidden;

    background: rgba(31,39,54,.88);

    border: 1px solid var(--caja-border) !important;
}


.caja-table-header {
    background: rgba(255,255,255,.025);

    border-bottom: 1px solid var(--caja-border);

    padding: 18px 20px;
}


.caja-table-icon {
    width: 42px;
    height: 42px;

    display: flex;
    align-items: center;
    justify-content: center;

    border-radius: 11px;

    background: rgba(59,130,246,.12);

    color: #60a5fa;

    font-size: 18px;
}


.caja-year-badge {
    padding: 6px 11px;

    border-radius: 8px;

    background: rgba(59,130,246,.10);

    color: #93c5fd;

    font-size: 12px;

    font-weight: 600;
}


.caja-table {
    --bs-table-bg: transparent;

    --bs-table-border-color:
        rgba(255,255,255,.07);
}


.caja-table thead th {
    padding: 15px 18px;

    font-size: 12px;

    text-transform: uppercase;

    letter-spacing: .4px;

    color: var(--cui-secondary-color);

    border-bottom: 1px solid rgba(255,255,255,.10);

    white-space: nowrap;
}


.caja-table tbody td {
    padding: 15px 18px;

    border-bottom: 1px solid rgba(255,255,255,.055);

    white-space: nowrap;
}


.caja-table tbody tr {
    transition: background .15s ease;
}


.caja-table tbody tr:hover {
    background: rgba(255,255,255,.025);
}


.caja-month {
    display: flex;

    align-items: center;

    gap: 9px;
}


.caja-month-dot {
    width: 7px;
    height: 7px;

    border-radius: 50%;

    background: #60a5fa;
}


.caja-money {
    color: var(--cui-body-color);
}


.caja-income {
    color: #4ade80;

    font-weight: 600;
}


.caja-expense {
    color: #f87171;

    font-weight: 600;
}


.caja-balance {
    font-weight: 700;
}


.caja-balance.positive {
    color: #fbbf24;
}


.caja-balance.negative {
    color: #f87171;
}


/* TOTAL */

.caja-table tfoot {
    background: rgba(255,255,255,.035);
}


.caja-table tfoot th {
    padding: 17px 18px;

    border-top: 1px solid rgba(255,255,255,.10);

    font-size: 13px;
}


.caja-total-income {
    color: #4ade80;

    font-weight: 700;
}


.caja-total-expense {
    color: #f87171;

    font-weight: 700;
}


.caja-total-balance {
    color: #fbbf24;

    font-weight: 700;
}



/* SIN DATOS */

.caja-empty {
    padding: 20px;
}


.caja-empty i {
    font-size: 40px;

    color: var(--cui-secondary-color);
}



/* RESPONSIVE */

@media (max-width: 767.98px) {

    .caja-header .card-body {
        padding: 20px !important;
    }

    .caja-year-selector {
        width: 100%;
    }

    .caja-year-selector .form-select {
        flex: 1;
    }

    .caja-value {
        font-size: 22px;
    }

    .caja-table {
        min-width: 760px;
    }

}

</style>

@endsection