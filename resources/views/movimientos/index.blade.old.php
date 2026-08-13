@extends('layouts.app')

@section('title', 'Movimientos')

@section('content')

<style>

    /* =========================================================
       MOVIMIENTOS - DISEÑO
       ========================================================= */

    .mov-page {
        padding: 4px;
    }

    .mov-header {
        background: linear-gradient(135deg, #1f2937, #18202d);
        border: 1px solid #30394a;
        border-radius: 10px;
        padding: 20px 22px;
        margin-bottom: 18px;
    }

    .mov-header h3 {
        color: #f8fafc;
        font-weight: 600;
        margin: 0;
    }

    .mov-header p {
        color: #94a3b8;
        margin: 4px 0 0;
        font-size: 14px;
    }

    .mov-header-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(59,130,246,.15);
        color: #60a5fa;
        margin-right: 10px;
        font-size: 20px;
    }

    .mov-btn-new {
        background: #d97706;
        border: none;
        color: white;
        font-weight: 600;
        padding: 10px 17px;
        border-radius: 7px;
    }

    .mov-btn-new:hover {
        background: #b45309;
        color: white;
    }


    /* =========================================================
       FILTROS
       ========================================================= */

    .mov-filter-card {
        background: #202733;
        border: 1px solid #30394a;
        border-radius: 10px;
        padding: 18px;
        margin-bottom: 18px;
    }

    .mov-filter-title {
        color: #f1f5f9;
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 14px;
    }

    .mov-filter-label {
        display: block;
        color: #cbd5e1;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .mov-filter-card .form-control,
    .mov-filter-card .form-select {
        background-color: #1c2330;
        border: 1px solid #354052;
        color: #e5e7eb;
        min-height: 40px;
    }

    .mov-filter-card .form-control::placeholder {
        color: #718096;
    }

    .mov-filter-card .form-control:focus,
    .mov-filter-card .form-select:focus {
        background-color: #1c2330;
        color: #fff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 .15rem rgba(59,130,246,.12);
    }

    .mov-btn-search {
        background: #d97706;
        border: none;
        color: white;
        font-weight: 600;
        min-height: 40px;
    }

    .mov-btn-search:hover {
        background: #b45309;
        color: white;
    }

    .mov-btn-clear {
        background: #374151;
        border: 1px solid #4b5563;
        color: #e5e7eb;
        min-height: 40px;
    }

    .mov-btn-clear:hover {
        background: #4b5563;
        color: white;
    }


    /* =========================================================
       TARJETAS
       ========================================================= */

    .mov-stat {
        background: #202733;
        border: 1px solid #30394a;
        border-radius: 10px;
        padding: 17px;
        height: 100%;
        position: relative;
        overflow: hidden;
    }

    .mov-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 19px;
        margin-bottom: 12px;
    }

    .mov-stat-title {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 600;
    }

    .mov-stat-value {
        color: #f8fafc;
        font-size: 22px;
        font-weight: 700;
        margin-top: 3px;
    }

    .mov-stat-subtitle {
        color: #64748b;
        font-size: 11px;
        margin-top: 3px;
    }

    .stat-blue .mov-stat-icon {
        background: rgba(59,130,246,.14);
        color: #60a5fa;
    }

    .stat-green .mov-stat-icon {
        background: rgba(34,197,94,.14);
        color: #4ade80;
    }

    .stat-red .mov-stat-icon {
        background: rgba(239,68,68,.14);
        color: #f87171;
    }

    .stat-orange .mov-stat-icon {
        background: rgba(245,158,11,.14);
        color: #fbbf24;
    }


    /* =========================================================
       GRAFICOS
       ========================================================= */

    .mov-section {
        background: #202733;
        border: 1px solid #30394a;
        border-radius: 10px;
        margin-bottom: 18px;
        overflow: hidden;
    }

    .mov-section-header {
        padding: 13px 16px;
        border-bottom: 1px solid #30394a;
        color: #f1f5f9;
        font-weight: 600;
    }

    .mov-chart-area {
        padding: 18px;
    }

    .mov-chart-card {
        background: #1c2330;
        border: 1px solid #30394a;
        border-radius: 9px;
        padding: 15px;
        height: 100%;
    }

    .mov-chart-title {
        color: #e5e7eb;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 12px;
    }

    .mov-chart-box {
        height: 230px;
        position: relative;
    }

    .mov-chart-empty {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 13px;
    }


    /* =========================================================
       TABLA
       ========================================================= */

    .mov-table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 17px;
        border-bottom: 1px solid #30394a;
    }

    .mov-table-title {
        color: #f1f5f9;
        font-weight: 600;
        margin: 0;
    }

    .mov-count {
        background: #d97706;
        color: white;
        padding: 4px 9px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 600;
    }

    .mov-table {
        margin-bottom: 0;
        color: #e5e7eb;
        border-color: #30394a;
    }

    .mov-table thead th {
        background: #1b222d;
        color: #cbd5e1;
        border-color: #30394a;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
        padding: 11px 9px;
    }

    .mov-table tbody td {
        background: #202733;
        border-color: #30394a;
        vertical-align: middle;
        font-size: 12px;
        padding: 9px;
    }

    .mov-table tbody tr:hover td {
        background: #273142;
    }

    .mov-number {
        color: #cbd5e1;
        font-weight: 600;
        white-space: nowrap;
    }

    .mov-date {
        color: #94a3b8;
        white-space: nowrap;
    }

    .mov-concept {
        color: #f1f5f9;
        max-width: 400px;
    }

    .mov-person {
        color: #94a3b8;
    }

    .mov-amount-income {
        color: #22c55e;
        font-weight: 700;
        white-space: nowrap;
    }

    .mov-amount-expense {
        color: #ef4444;
        font-weight: 700;
        white-space: nowrap;
    }

    .mov-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 8px;
        border-radius: 5px;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .mov-badge-income {
        background: rgba(34,197,94,.15);
        color: #4ade80;
    }

    .mov-badge-expense {
        background: rgba(239,68,68,.15);
        color: #f87171;
    }

    .mov-badge-registered {
        background: rgba(217,119,6,.15);
        color: #fbbf24;
    }

    .mov-badge-cancelled {
        background: rgba(100,116,139,.15);
        color: #94a3b8;
    }


    /* =========================================================
       ACCIONES
       ========================================================= */

    .mov-action {
        width: 32px;
        height: 32px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        border: 1px solid transparent;
    }

    .mov-action-edit {
        background: rgba(245,158,11,.15);
        color: #fbbf24;
        border-color: rgba(245,158,11,.2);
    }

    .mov-action-edit:hover {
        background: #d97706;
        color: white;
    }

    .mov-action-delete {
        background: rgba(239,68,68,.15);
        color: #f87171;
        border-color: rgba(239,68,68,.2);
    }

    .mov-action-delete:hover {
        background: #dc2626;
        color: white;
    }


    /* =========================================================
       PIE
       ========================================================= */

    .mov-footer {
        padding: 13px 17px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #64748b;
        font-size: 12px;
    }


    @media (max-width: 768px) {

        .mov-header {
            padding: 15px;
        }

        .mov-header .btn {
            margin-top: 12px;
            width: 100%;
        }

        .mov-concept {
            max-width: 250px;
        }

        .mov-footer {
            flex-direction: column;
            gap: 10px;
        }

    }

</style>


<div class="mov-page">

    {{-- =====================================================
         ENCABEZADO
         ===================================================== --}}

    <div class="mov-header">

        <div class="d-flex justify-content-between align-items-center flex-wrap">

            <div class="d-flex align-items-center">

                <div class="mov-header-icon">
                    <i class="cil-transfer"></i>
                </div>

                <div>

                    <h3>
                        Movimientos
                    </h3>

                    <p>
                        Registro y administración de ingresos y egresos
                    </p>

                </div>

            </div>


            <a
                href="{{ route('movimientos.create') }}"
                class="btn mov-btn-new">

                <i class="cil-plus me-1"></i>

                Nuevo Movimiento

            </a>

        </div>

    </div>



    {{-- =====================================================
         FILTROS
         ===================================================== --}}

    <div class="mov-filter-card">

        <div class="mov-filter-title">

            <i class="cil-filter me-1"></i>

            Buscar y filtrar movimientos

        </div>


        <form method="GET">

            <div class="row g-3 align-items-end">

                {{-- BUSCAR --}}

                <div class="col-lg-5 col-md-6">

                    <label class="mov-filter-label">

                        Buscar por

                    </label>

                    <input
                        type="text"
                        name="buscar"
                        class="form-control"
                        placeholder="Número, concepto, persona o referencia..."
                        value="{{ request('buscar') }}">

                </div>


                {{-- TIPO --}}

                <div class="col-lg-3 col-md-3">

                    <label class="mov-filter-label">

                        Tipo de movimiento

                    </label>

                    <select
                        name="tipo"
                        class="form-select">

                        <option value="">
                            Todos
                        </option>

                        <option
                            value="Ingreso"
                            @selected(request('tipo') == 'Ingreso')>

                            Ingresos

                        </option>

                        <option
                            value="Egreso"
                            @selected(request('tipo') == 'Egreso')>

                            Egresos

                        </option>

                    </select>

                </div>


                {{-- BOTONES --}}

                <div class="col-lg-2 col-md-3">

                    <button
                        type="submit"
                        class="btn mov-btn-search w-100">

                        <i class="cil-search me-1"></i>

                        Buscar

                    </button>

                </div>


                <div class="col-lg-2 col-md-3">

                    <a
                        href="{{ route('movimientos.index') }}"
                        class="btn mov-btn-clear w-100">

                        <i class="cil-x me-1"></i>

                        Limpiar

                    </a>

                </div>

            </div>

        </form>

    </div>



    {{-- =====================================================
         ESTADISTICAS
         ===================================================== --}}

    @php

        $totalPagina = $movimientos->count();

        $ingresosPagina = $movimientos
            ->where('tipo', 'Ingreso')
            ->sum('monto');

        $egresosPagina = $movimientos
            ->where('tipo', 'Egreso')
            ->sum('monto');

        $saldoPagina = $ingresosPagina - $egresosPagina;

        $cantidadIngresos = $movimientos
            ->where('tipo', 'Ingreso')
            ->count();

        $cantidadEgresos = $movimientos
            ->where('tipo', 'Egreso')
            ->count();

    @endphp


    <div class="row g-3 mb-3">

        {{-- TOTAL MOVIMIENTOS --}}

        <div class="col-xl-3 col-md-6">

            <div class="mov-stat stat-blue">

                <div class="mov-stat-icon">

                    <i class="cil-list"></i>

                </div>

                <div class="mov-stat-title">
                    Movimientos visibles
                </div>

                <div class="mov-stat-value">
                    {{ $totalPagina }}
                </div>

                <div class="mov-stat-subtitle">
                    En la página actual
                </div>

            </div>

        </div>


        {{-- INGRESOS --}}

        <div class="col-xl-3 col-md-6">

            <div class="mov-stat stat-green">

                <div class="mov-stat-icon">

                    <i class="cil-arrow-top"></i>

                </div>

                <div class="mov-stat-title">
                    Ingresos visibles
                </div>

                <div class="mov-stat-value text-success">

                    S/
                    {{ number_format($ingresosPagina, 2) }}

                </div>

                <div class="mov-stat-subtitle">

                    {{ $cantidadIngresos }} movimiento(s)

                </div>

            </div>

        </div>


        {{-- EGRESOS --}}

        <div class="col-xl-3 col-md-6">

            <div class="mov-stat stat-red">

                <div class="mov-stat-icon">

                    <i class="cil-arrow-bottom"></i>

                </div>

                <div class="mov-stat-title">
                    Egresos visibles
                </div>

                <div class="mov-stat-value text-danger">

                    S/
                    {{ number_format($egresosPagina, 2) }}

                </div>

                <div class="mov-stat-subtitle">

                    {{ $cantidadEgresos }} movimiento(s)

                </div>

            </div>

        </div>


        {{-- SALDO --}}

        <div class="col-xl-3 col-md-6">

            <div class="mov-stat stat-orange">

                <div class="mov-stat-icon">

                    <i class="cil-wallet"></i>

                </div>

                <div class="mov-stat-title">
                    Diferencia visible
                </div>

                <div class="mov-stat-value
                    {{ $saldoPagina >= 0 ? 'text-warning' : 'text-danger' }}">

                    S/
                    {{ number_format($saldoPagina, 2) }}

                </div>

                <div class="mov-stat-subtitle">
                    Ingresos - Egresos
                </div>

            </div>

        </div>

    </div>



    {{-- =====================================================
         GRAFICOS
         ===================================================== --}}

    <div class="mov-section">

        <div class="mov-section-header">

            <i class="cil-chart-line me-1"></i>

            Resumen gráfico

            <span class="text-muted ms-2"
                  style="font-size:11px; font-weight:400;">

                Movimientos visibles en la página actual

            </span>

        </div>


        <div class="mov-chart-area">

            <div class="row g-3">

                {{-- INGRESOS VS EGRESOS --}}

                <div class="col-lg-5">

                    <div class="mov-chart-card">

                        <div class="mov-chart-title">

                            Ingresos vs. Egresos

                        </div>

                        <div class="mov-chart-box">

                            <canvas id="graficoIngresosEgresos"></canvas>

                        </div>

                    </div>

                </div>


                {{-- EGRESOS POR CATEGORIA --}}

                <div class="col-lg-4">

                    <div class="mov-chart-card">

                        <div class="mov-chart-title">

                            Egresos por categoría

                        </div>

                        <div class="mov-chart-box">

                            <canvas id="graficoCategorias"></canvas>

                        </div>

                    </div>

                </div>


                {{-- CANTIDAD --}}

                <div class="col-lg-3">

                    <div class="mov-chart-card">

                        <div class="mov-chart-title">

                            Movimientos por tipo

                        </div>

                        <div class="mov-chart-box">

                            <canvas id="graficoTipos"></canvas>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>



    {{-- =====================================================
         TABLA
         ===================================================== --}}

    <div class="mov-section">

        <div class="mov-table-header">

            <h5 class="mov-table-title">

                <i class="cil-list me-1"></i>

                Detalle de movimientos

            </h5>

            <span class="mov-count">

                {{ $movimientos->total() }} registros

            </span>

        </div>


        <div class="table-responsive">

            <table class="table mov-table align-middle">

                <thead>

                    <tr>

                        <th>N.º</th>

                        <th>Fecha</th>

                        <th>Tipo</th>

                        <th>Categoría</th>

                        <th>Concepto</th>

                        <th>Persona</th>

                        <th class="text-end">
                            Monto
                        </th>

                        <th>Estado</th>

                        <th class="text-center">
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($movimientos as $movimiento)

                        <tr>

                            {{-- NUMERO --}}

                            <td class="mov-number">

                                {{ $movimiento->numero }}

                            </td>


                            {{-- FECHA --}}

                            <td class="mov-date">

                                {{ $movimiento->fecha->format('d/m/Y') }}

                            </td>


                            {{-- TIPO --}}

                            <td>

                                @if($movimiento->tipo == 'Ingreso')

                                    <span class="mov-badge mov-badge-income">

                                        <i class="cil-arrow-top"></i>

                                        Ingreso

                                    </span>

                                @else

                                    <span class="mov-badge mov-badge-expense">

                                        <i class="cil-arrow-bottom"></i>

                                        Egreso

                                    </span>

                                @endif

                            </td>


                            {{-- CATEGORIA --}}

                            <td>

                                {{ $movimiento->categoria->nombre }}

                            </td>


                            {{-- CONCEPTO --}}

                            <td class="mov-concept">

                                {{ $movimiento->concepto }}

                            </td>


                            {{-- PERSONA --}}

                            <td class="mov-person">

                                {{ $movimiento->persona ?: '—' }}

                            </td>


                            {{-- MONTO --}}

                            <td class="text-end">

                                @if($movimiento->tipo == 'Ingreso')

                                    <span class="mov-amount-income">

                                        + S/
                                        {{ number_format($movimiento->monto, 2) }}

                                    </span>

                                @else

                                    <span class="mov-amount-expense">

                                        - S/
                                        {{ number_format($movimiento->monto, 2) }}

                                    </span>

                                @endif

                            </td>


                            {{-- ESTADO --}}

                            <td>

                                @if($movimiento->estado == "Registrado")

                                    <span class="mov-badge mov-badge-registered">

                                        Registrado

                                    </span>

                                @else

                                    <span class="mov-badge mov-badge-cancelled">

                                        Anulado

                                    </span>

                                @endif

                            </td>


                            {{-- ACCIONES --}}

                            <td class="text-center">

                                <div class="d-flex justify-content-center gap-1">

                                    {{-- EDITAR --}}

                                    <a
                                        href="{{ route('movimientos.edit', $movimiento) }}"
                                        class="btn mov-action mov-action-edit"
                                        title="Editar movimiento">

                                        <i class="cil-pencil"></i>

                                    </a>


                                    {{-- ELIMINAR --}}

                                    <form
                                        action="{{ route('movimientos.destroy', $movimiento) }}"
                                        method="POST"
                                        class="d-inline">

                                        @csrf

                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="btn mov-action mov-action-delete"
                                            title="Eliminar movimiento">

                                            <i class="cil-trash"></i>

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5">

                                <div class="text-muted">

                                    <i
                                        class="cil-folder-open"
                                        style="font-size:35px;">
                                    </i>

                                    <div class="mt-2">

                                        No existen movimientos.

                                    </div>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- =================================================
             PIE DE TABLA
             ================================================= --}}

        <div class="mov-footer">

            <div>

                Mostrando

                <strong class="text-light">

                    {{ $movimientos->firstItem() ?? 0 }}

                </strong>

                a

                <strong class="text-light">

                    {{ $movimientos->lastItem() ?? 0 }}

                </strong>

                de

                <strong class="text-light">

                    {{ $movimientos->total() }}

                </strong>

                registros

            </div>


            <div>

                {{ $movimientos->links() }}

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     CHART.JS
     ========================================================= --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
     * ---------------------------------------------------------
     * Datos de los movimientos visibles
     * ---------------------------------------------------------
     */

    const ingresos = @json($cantidadIngresos);
    const egresos = @json($cantidadEgresos);

    const totalIngresos = @json((float) $ingresosPagina);
    const totalEgresos = @json((float) $egresosPagina);


    /*
     * ---------------------------------------------------------
     * Gráfico 1
     * Ingresos vs Egresos
     * ---------------------------------------------------------
     */

    const canvasIngresos =
        document.getElementById('graficoIngresosEgresos');

    if (canvasIngresos) {

        new Chart(canvasIngresos, {

            type: 'bar',

            data: {

                labels: ['Ingresos', 'Egresos'],

                datasets: [{

                    data: [
                        totalIngresos,
                        totalEgresos
                    ],

                    backgroundColor: [
                        'rgba(34,197,94,.75)',
                        'rgba(239,68,68,.75)'
                    ],

                    borderColor: [
                        '#22c55e',
                        '#ef4444'
                    ],

                    borderWidth: 1,

                    borderRadius: 5

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {
                        display: false
                    }

                },

                scales: {

                    x: {

                        ticks: {
                            color: '#94a3b8'
                        },

                        grid: {
                            display: false
                        }

                    },

                    y: {

                        beginAtZero: true,

                        ticks: {

                            color: '#94a3b8',

                            callback: function(value) {

                                return 'S/ ' + value;

                            }

                        },

                        grid: {
                            color: 'rgba(148,163,184,.08)'
                        }

                    }

                }

            }

        });

    }


    /*
     * ---------------------------------------------------------
     * Gráfico 2
     * Egresos por categoría
     * ---------------------------------------------------------
     */

    const categorias = @json(
        $movimientos
            ->where('tipo', 'Egreso')
            ->groupBy(function ($movimiento) {
                return $movimiento->categoria->nombre;
            })
            ->map(function ($items) {
                return $items->sum('monto');
            })
    );


    const canvasCategorias =
        document.getElementById('graficoCategorias');

    if (canvasCategorias) {

        const nombresCategorias =
            Object.keys(categorias);

        const valoresCategorias =
            Object.values(categorias);


        if (nombresCategorias.length > 0) {

            new Chart(canvasCategorias, {

                type: 'doughnut',

                data: {

                    labels: nombresCategorias,

                    datasets: [{

                        data: valoresCategorias,

                        backgroundColor: [
                            '#3b82f6',
                            '#22c55e',
                            '#f59e0b',
                            '#ef4444',
                            '#8b5cf6',
                            '#06b6d4',
                            '#ec4899'
                        ],

                        borderColor: '#1c2330',

                        borderWidth: 3

                    }]

                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    plugins: {

                        legend: {

                            position: 'bottom',

                            labels: {

                                color: '#cbd5e1',

                                boxWidth: 12,

                                padding: 10

                            }

                        }

                    }

                }

            });

        }

    }


    /*
     * ---------------------------------------------------------
     * Gráfico 3
     * Cantidad de movimientos
     * ---------------------------------------------------------
     */

    const canvasTipos =
        document.getElementById('graficoTipos');

    if (canvasTipos) {

        new Chart(canvasTipos, {

            type: 'doughnut',

            data: {

                labels: [
                    'Ingresos',
                    'Egresos'
                ],

                datasets: [{

                    data: [
                        ingresos,
                        egresos
                    ],

                    backgroundColor: [
                        '#22c55e',
                        '#ef4444'
                    ],

                    borderColor: '#1c2330',

                    borderWidth: 3

                }]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                plugins: {

                    legend: {

                        position: 'bottom',

                        labels: {

                            color: '#cbd5e1',

                            boxWidth: 12,

                            padding: 10

                        }

                    }

                }

            }

        });

    }

});

</script>

@endsection