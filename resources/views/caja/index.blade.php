@extends('layouts.app')

@section('title', 'Caja')

@section('content')

@php
    $saldoInicial = $consolidado[0]->saldo_inicial ?? 0;
    $totalIngresos = collect($consolidado)->sum('total_ingresos');
    $totalEgresos = collect($consolidado)->sum('total_egresos');
    $saldoFinal = collect($consolidado)->last()->saldo_final ?? 0;

    $totalMovimiento = $totalIngresos + $totalEgresos;

    $porcentajeIngresos = $totalMovimiento > 0
        ? ($totalIngresos / $totalMovimiento) * 100
        : 0;

    $porcentajeEgresos = $totalMovimiento > 0
        ? ($totalEgresos / $totalMovimiento) * 100
        : 0;
@endphp

<div class="caja-container">

    {{-- =========================================================
         1. CABECERA PRINCIPAL
    ========================================================== --}}
    <div class="caja-topbar">
        <div class="caja-title-area">
            <div class="d-flex align-items-center gap-2">
                <i class="cil-wallet title-icon"></i>
                <h1 class="caja-title">CAJA</h1>
                <span class="caja-status-badge">CONSOLIDADO</span>
            </div>
            <p class="caja-subtitle">Balance financiero y consolidado mensual del año {{ $anio }}</p>
        </div>

        {{-- SELECTOR DE AÑO --}}
        <form method="GET" class="caja-year-form">
            <div class="caja-year-selector">
                <label for="selectAnio">AÑO FISCAL</label>
                <div class="select-wrapper">
                    <select name="anio" id="selectAnio" class="caja-select" onchange="this.form.submit()">
                        @foreach($anios as $item)
                            <option value="{{ $item }}" @selected($anio == $item)>
                                {{ $item }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>


    {{-- =========================================================
         2. TARJETAS KPI DE RESUMEN
    ========================================================== --}}
    <div class="caja-kpi-grid">

        {{-- SALDO INICIAL --}}
        <div class="caja-kpi-card kpi-blue">
            <div class="caja-kpi-icon">
                <i class="cil-wallet"></i>
            </div>
            <div class="caja-kpi-content">
                <span class="caja-kpi-label">Saldo Inicial</span>
                <strong class="caja-kpi-value">S/ {{ number_format($saldoInicial, 2) }}</strong>
                <small class="caja-kpi-footer">Inicio del período</small>
            </div>
        </div>

        {{-- INGRESOS --}}
        <div class="caja-kpi-card kpi-green">
            <div class="caja-kpi-icon">
                <i class="cil-arrow-top"></i>
            </div>
            <div class="caja-kpi-content">
                <span class="caja-kpi-label">Ingresos del año</span>
                <strong class="caja-kpi-value text-income">+ S/ {{ number_format($totalIngresos, 2) }}</strong>
                <small class="caja-kpi-footer">Total recibido</small>
            </div>
        </div>

        {{-- EGRESOS --}}
        <div class="caja-kpi-card kpi-red">
            <div class="caja-kpi-icon">
                <i class="cil-arrow-bottom"></i>
            </div>
            <div class="caja-kpi-content">
                <span class="caja-kpi-label">Egresos del año</span>
                <strong class="caja-kpi-value text-expense">- S/ {{ number_format($totalEgresos, 2) }}</strong>
                <small class="caja-kpi-footer">Total gastado</small>
            </div>
        </div>

        {{-- SALDO EN CAJA / FINAL --}}
        <div class="caja-kpi-card {{ $saldoFinal < 0 ? 'kpi-negative' : 'kpi-orange' }}">
            <div class="caja-kpi-icon">
                <i class="cil-home"></i>
            </div>
            <div class="caja-kpi-content">
                <div class="d-flex align-items-center justify-content-between">
                    <span class="caja-kpi-label">Saldo en caja</span>
                    @if($saldoFinal < 0)
                        <span class="kpi-alert-dot" title="Déficit en saldo"></span>
                    @endif
                </div>
                <strong class="caja-kpi-value {{ $saldoFinal >= 0 ? 'text-warning' : 'text-danger' }}">
                    S/ {{ number_format($saldoFinal, 2) }}
                </strong>
                <small class="caja-kpi-footer">Cierre del período</small>
            </div>
        </div>

    </div>


    {{-- =========================================================
         3. RESUMEN DE DISTRIBUCIÓN
    ========================================================== --}}
    <div class="caja-section">
        <div class="caja-section-header">
            <div>
                <span class="caja-section-title">RESUMEN FINANCIERO</span>
                <span class="caja-section-sub">Distribución de los movimientos registrados en {{ $anio }}</span>
            </div>
            <i class="cil-chart-pie section-icon"></i>
        </div>

        <div class="caja-progress-container">
            <div class="caja-progress-bar">
                <div class="caja-progress-income" style="width: {{ $porcentajeIngresos }}%;"></div>
                <div class="caja-progress-expense" style="width: {{ $porcentajeEgresos }}%;"></div>
            </div>

            <div class="caja-progress-legend">
                <div class="legend-item">
                    <span class="legend-dot green"></span>
                    <span class="legend-label">Ingresos</span>
                    <strong class="legend-value">{{ number_format($porcentajeIngresos, 1) }}%</strong>
                </div>

                <div class="legend-item">
                    <span class="legend-dot red"></span>
                    <span class="legend-label">Egresos</span>
                    <strong class="legend-value">{{ number_format($porcentajeEgresos, 1) }}%</strong>
                </div>
            </div>
        </div>
    </div>


    {{-- =========================================================
         4. TABLA CONSOLIDADO MENSUAL
    ========================================================== --}}
    <div class="caja-section p-0 overflow-hidden">

        <div class="caja-table-header">
            <div class="d-flex align-items-center gap-2">
                <i class="cil-chart-line header-icon"></i>
                <div>
                    <span class="caja-section-title">CONSOLIDADO MENSUAL</span>
                    <span class="caja-section-sub">Evolución de caja durante {{ $anio }}</span>
                </div>
            </div>
            <span class="caja-year-badge">{{ $anio }}</span>
        </div>

        <div class="table-responsive">
            <table class="caja-table">
                <thead>
                    <tr>
                        <th>MES</th>
                        <th class="text-end">SALDO INICIAL</th>
                        <th class="text-end">INGRESOS</th>
                        <th class="text-end">EGRESOS</th>
                        <th class="text-end">SALDO FINAL</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consolidado as $fila)
                        <tr>
                            <td>
                                <div class="caja-month-cell">
                                    <span class="month-dot"></span>
                                    <span class="month-name">{{ $meses[$fila->mes] ?? $fila->mes }}</span>
                                </div>
                            </td>

                            <td class="text-end text-money">
                                S/ {{ number_format($fila->saldo_inicial, 2) }}
                            </td>

                            <td class="text-end text-income fw-bold">
                                + S/ {{ number_format($fila->total_ingresos, 2) }}
                            </td>

                            <td class="text-end text-expense fw-bold">
                                - S/ {{ number_format($fila->total_egresos, 2) }}
                            </td>

                            <td class="text-end fw-bold">
                                <span class="{{ $fila->saldo_final >= 0 ? 'text-warning' : 'text-danger' }}">
                                    S/ {{ number_format($fila->saldo_final, 2) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="caja-empty">
                                    <i class="cil-wallet empty-icon"></i>
                                    <h6 class="mt-3 text-main">No existen datos</h6>
                                    <p class="text-muted mb-0">No se encontraron registros de caja para el año {{ $anio }}.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if(count($consolidado) > 0)
                    <tfoot>
                        <tr>
                            <th>TOTAL ACUMULADO</th>
                            <th></th>
                            <th class="text-end text-income">
                                S/ {{ number_format($totalIngresos, 2) }}
                            </th>
                            <th class="text-end text-expense">
                                S/ {{ number_format($totalEgresos, 2) }}
                            </th>
                            <th class="text-end text-warning">
                                S/ {{ number_format($saldoFinal, 2) }}
                            </th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

    </div>

</div>


{{-- =========================================================
     ESTILOS INDUSTRIALES Y TEMA DUAL
========================================================== --}}
<style>
/* VARIABLES LOCALES DE TEMA */
.caja-container {
    width: 100% !important;
    box-sizing: border-box !important;
    margin-bottom: 30px;
}

/* =========================================================
   1. CABECERA PRINCIPAL
========================================================= */
.caja-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
    margin-bottom: 20px;
}

.caja-title-area { min-width: 0; }

.caja-title {
    margin: 0;
    font-size: 22px;
    font-weight: 800;
    color: var(--text-main, #f8fafc);
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.title-icon {
    font-size: 20px;
    color: var(--accent-color, #38bdf8);
}

.caja-subtitle {
    margin: 4px 0 0;
    font-size: 12px;
    color: var(--text-muted, #94a3b8);
}

.caja-status-badge {
    font-size: 9px;
    font-weight: 800;
    padding: 3px 8px;
    background: rgba(52, 211, 153, 0.12);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.25);
    border-radius: 0;
    letter-spacing: 0.05em;
}

/* SELECTOR DE AÑO */
.caja-year-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.caja-year-selector label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: var(--text-muted, #94a3b8);
}

.caja-select {
    height: 38px;
    min-width: 110px;
    padding: 0 12px;
    background-color: var(--input-bg, rgba(15, 23, 42, 0.6));
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.12));
    border-radius: 0 !important;
    color: var(--text-main, #f8fafc);
    font-size: 13px;
    font-weight: 700;
    outline: none;
}

.caja-select option {
    background-color: var(--card-bg, #1e293b);
    color: var(--text-main, #f8fafc);
}

/* =========================================================
   2. TARJETAS KPI (4 COLUMNAS DESKTOP)
========================================================= */
.caja-kpi-grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 16px !important;
    margin-bottom: 20px !important;
}

.caja-kpi-card {
    display: flex !important;
    align-items: flex-start !important;
    gap: 14px;
    padding: 16px;
    background: var(--card-bg, #1e293b) !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
    border-radius: 0 !important;
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.caja-kpi-card:hover {
    transform: translateY(-2px);
}

.caja-kpi-icon {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border-radius: 0 !important;
}

.caja-kpi-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.caja-kpi-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted, #94a3b8);
}

.caja-kpi-value {
    font-size: 18px;
    font-weight: 800;
    color: var(--text-main, #f8fafc);
    margin-top: 4px;
    letter-spacing: -0.02em;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.caja-kpi-footer {
    font-size: 10px;
    color: var(--text-muted, #94a3b8);
    margin-top: 6px;
    padding-top: 6px;
    border-top: 1px solid var(--sb-border, rgba(255, 255, 255, 0.05));
    opacity: 0.8;
}

/* VARIACIONES DE COLORES INDUSTRIALES */
.kpi-blue .caja-kpi-icon { background: rgba(56, 189, 248, 0.12); color: #38bdf8; }
.kpi-blue { border-left: 3px solid #38bdf8 !important; }

.kpi-green .caja-kpi-icon { background: rgba(52, 211, 153, 0.12); color: #34d399; }
.kpi-green { border-left: 3px solid #34d399 !important; }

.kpi-red .caja-kpi-icon { background: rgba(248, 113, 113, 0.12); color: #f87171; }
.kpi-red { border-left: 3px solid #f87171 !important; }

.kpi-orange .caja-kpi-icon { background: rgba(251, 191, 36, 0.12); color: #fbbf24; }
.kpi-orange { border-left: 3px solid #fbbf24 !important; }

.kpi-negative {
    background: rgba(239, 68, 68, 0.06) !important;
    border-left: 3px solid #ef4444 !important;
}
.kpi-negative .caja-kpi-icon { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.kpi-alert-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #ef4444;
    box-shadow: 0 0 6px #ef4444;
    animation: pulseAlert 1.5s infinite;
}

@keyframes pulseAlert { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

/* =========================================================
   3. SECCIONES Y BARRAS DE PROGRESO
========================================================= */
.caja-section {
    background: var(--card-bg, #1e293b) !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
    border-radius: 0 !important;
    padding: 18px;
    margin-bottom: 20px;
}

.caja-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.caja-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted, #94a3b8);
}

.caja-section-sub {
    font-size: 11px;
    color: var(--text-muted, #94a3b8);
    opacity: 0.7;
    margin-left: 8px;
}

.section-icon { font-size: 16px; color: var(--accent-color, #38bdf8); }

.caja-progress-container { margin-top: 10px; }

.caja-progress-bar {
    display: flex;
    width: 100%;
    height: 8px;
    background: var(--input-bg, rgba(15, 23, 42, 0.6));
    border-radius: 0 !important;
    overflow: hidden;
}

.caja-progress-income { background-color: #34d399; }
.caja-progress-expense { background-color: #f87171; }

.caja-progress-legend {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-top: 12px;
}

.legend-item { display: flex; align-items: center; gap: 8px; font-size: 12px; }
.legend-dot { width: 8px; height: 8px; border-radius: 0 !important; }
.legend-dot.green { background-color: #34d399; }
.legend-dot.red { background-color: #f87171; }
.legend-label { color: var(--text-muted, #94a3b8); }
.legend-value { color: var(--text-main, #f8fafc); }

/* =========================================================
   4. TABLA CONSOLIDADO
========================================================= */
.caja-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    background: var(--input-bg, rgba(15, 23, 42, 0.4));
    border-bottom: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
}

.caja-year-badge {
    padding: 3px 8px;
    background: rgba(56, 189, 248, 0.12);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.25);
    font-size: 11px;
    font-weight: 700;
}

.caja-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    color: var(--text-main, #f8fafc);
}

.caja-table th {
    padding: 12px 16px;
    background: rgba(0, 0, 0, 0.15);
    color: var(--text-muted, #94a3b8);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
}

.caja-table td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--sb-border, rgba(255, 255, 255, 0.04));
    vertical-align: middle;
}

.caja-table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.02);
}

.caja-month-cell { display: flex; align-items: center; gap: 8px; }
.month-dot { width: 6px; height: 6px; background-color: var(--accent-color, #38bdf8); border-radius: 0 !important; }
.month-name { font-weight: 700; color: var(--text-main, #f8fafc); }

.text-money { color: var(--text-main, #f8fafc); }
.text-income { color: #34d399 !important; }
.text-expense { color: #f87171 !important; }

.caja-table tfoot th {
    padding: 14px 16px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid var(--card-border, rgba(255, 255, 255, 0.12));
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.05em;
}

.empty-icon { font-size: 32px; opacity: 0.4; color: var(--text-muted); }

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 992px) {
    .caja-kpi-grid { grid-template-columns: repeat(2, 1fr) !important; }
}

@media (max-width: 576px) {
    .caja-topbar { flex-direction: column; align-items: flex-start; }
    .caja-kpi-grid { grid-template-columns: 1fr !important; }
}
</style>

@endsection