@extends('layouts.app')

@section('title', 'Reportes')

@section('content')

<style>
    .reportes-page {
        padding: 10px 0 30px;
    }

    /* Encabezado */
    .reportes-header {
        background: linear-gradient(135deg, #111827, #172554);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 14px;
        padding: 24px 28px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }

    .reportes-header::after {
        content: '';
        position: absolute;
        width: 180px;
        height: 180px;
        right: -50px;
        top: -70px;
        background: rgba(59,130,246,.10);
        border-radius: 50%;
    }

    .reportes-header-icon {
        width: 52px;
        height: 52px;
        border-radius: 13px;
        background: rgba(59,130,246,.15);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #60a5fa;
        font-size: 25px;
        flex-shrink: 0;
    }

    .reportes-header h2 {
        margin: 0;
        font-size: 25px;
        font-weight: 700;
        color: #f8fafc;
    }

    .reportes-header p {
        margin: 4px 0 0;
        color: #94a3b8;
        font-size: 14px;
    }

    /* Tarjetas */
    .report-card {
        background: #182235;
        border: 1px solid rgba(148,163,184,.14);
        border-radius: 14px;
        box-shadow: 0 8px 25px rgba(0,0,0,.12);
    }

    .report-card-header {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(148,163,184,.12);
    }

    .report-card-header h5 {
        margin: 0;
        color: #f1f5f9;
        font-size: 16px;
        font-weight: 600;
    }

    .report-card-header i {
        color: #60a5fa;
        margin-right: 8px;
    }

    .report-card-body {
        padding: 22px;
    }

    /* Formularios */
    .reportes-page .form-label {
        color: #cbd5e1;
        font-size: 13px;
        font-weight: 500;
        margin-bottom: 7px;
    }

    .reportes-page .form-select {
        min-height: 44px;
        background-color: #202c40;
        border: 1px solid #344158;
        color: #e2e8f0;
        border-radius: 8px;
    }

    .reportes-page .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 .2rem rgba(59,130,246,.15);
        background-color: #202c40;
        color: #e2e8f0;
    }

    /* Botón consultar */
    .btn-consultar {
        min-height: 44px;
        padding: 0 22px;
        border: 0;
        border-radius: 8px;
        background: linear-gradient(135deg, #2563eb, #3b82f6);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 5px 15px rgba(37,99,235,.25);
        transition: all .2s ease;
    }

    .btn-consultar:hover {
        transform: translateY(-1px);
        background: linear-gradient(135deg, #1d4ed8, #2563eb);
        color: #fff;
    }

    /* Acciones */
    .report-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .report-actions .btn {
        min-height: 40px;
        border-radius: 8px;
        font-weight: 600;
        padding: 0 17px;
    }

    .btn-pdf {
        background: #dc3545;
        border-color: #dc3545;
        color: #fff;
    }

    .btn-pdf:hover {
        background: #bb2d3b;
        border-color: #bb2d3b;
        color: #fff;
    }

    .btn-excel {
        background: #198754;
        border-color: #198754;
        color: #fff;
    }

    .btn-excel:hover {
        background: #157347;
        border-color: #157347;
        color: #fff;
    }

    .btn-print {
        background: #64748b;
        border-color: #64748b;
        color: #fff;
    }

    .btn-print:hover {
        background: #475569;
        border-color: #475569;
        color: #fff;
    }

    /* Resumen */
    .report-summary {
        background: #182235;
        border: 1px solid rgba(148,163,184,.14);
        border-radius: 14px;
        padding: 22px;
        margin-bottom: 20px;
    }

    .summary-label {
        color: #64748b;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 5px;
    }

    .summary-value {
        color: #f1f5f9;
        font-size: 18px;
        font-weight: 600;
    }

    .summary-divider {
        border-left: 1px solid rgba(148,163,184,.15);
    }

    /* Estado */
    .report-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
    }

    /* Estado vacío */
    .report-empty {
        min-height: 330px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 40px 20px;
    }

    .report-empty-icon {
        width: 76px;
        height: 76px;
        border-radius: 20px;
        background: rgba(59,130,246,.10);
        color: #60a5fa;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        margin-bottom: 20px;
    }

    .report-empty h4 {
        color: #f1f5f9;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .report-empty p {
        max-width: 480px;
        color: #94a3b8;
        margin: 0;
        line-height: 1.6;
    }

    /* Alert */
    .report-alert {
        border-radius: 12px;
        border: 1px solid rgba(245,158,11,.25);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .reportes-header {
            padding: 20px;
        }

        .report-card-body {
            padding: 16px;
        }

        .summary-divider {
            border-left: 0;
            border-top: 1px solid rgba(148,163,184,.15);
            margin-top: 15px;
            padding-top: 15px;
        }

        .report-actions {
            width: 100%;
        }

        .report-actions .btn {
            flex: 1;
        }
    }
</style>


<div class="container-fluid reportes-page">

    {{-- ENCABEZADO --}}
    <div class="reportes-header">

        <div class="d-flex align-items-center gap-3 position-relative" style="z-index: 1;">

            <div class="reportes-header-icon">
                <i class="cil-chart"></i>
            </div>

            <div>
                <h2>Centro de Reportes</h2>

                <p>
                    Consulte y exporte la información financiera del grupo
                </p>
            </div>

        </div>

    </div>


    {{-- FILTROS --}}
    <div class="report-card mb-3">

        <div class="report-card-header">

            <h5>
                <i class="cil-filter"></i>
                Filtros del reporte
            </h5>

        </div>

        <div class="report-card-body">

            <form
                method="GET"
                action="{{ route('reportes.index') }}">

                <div class="row g-3 align-items-end">

                    {{-- REPORTE --}}
                    <div class="col-lg-4 col-md-6">

                        <label class="form-label">
                            Reporte
                        </label>

                        <select
                            name="reporte"
                            class="form-select">

                            <option
                                value="estado"
                                @selected($reporte=='estado')>
                                Estado de Cuenta
                            </option>

                            <option
                                value="ingresos"
                                @selected($reporte=='ingresos')>
                                Ingresos
                            </option>

                            <option
                                value="egresos"
                                @selected($reporte=='egresos')>
                                Egresos
                            </option>

                           

                        </select>

                    </div>


                    {{-- AÑO --}}
                    <div class="col-lg-3 col-md-3">

                        <label class="form-label">
                            Año
                        </label>

                        <select
                            name="anio"
                            class="form-select">

                            @foreach($anios as $anio)

                                <option
                                    value="{{ $anio }}"
                                    @selected(request('anio')==$anio)>

                                    {{ $anio }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- MES --}}
                    <div class="col-lg-3 col-md-3">

                        <label class="form-label">
                            Mes
                        </label>

                        <select
                            name="mes"
                            class="form-select">

                            @foreach($meses as $numero=>$nombre)

                                <option
                                    value="{{ $numero }}"
                                    @selected(request('mes')==$numero)>

                                    {{ $nombre }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- CONSULTAR --}}
                    <div class="col-lg-2 col-md-12">

                        <button
                            type="submit"
                            class="btn btn-consultar w-100">

                            <i class="cil-magnifying-glass me-1"></i>

                            Consultar

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- ACCIONES --}}
    <div class="report-card mb-3">

        <div class="report-card-body py-3">

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

                <div>

                    <span class="text-muted small">
                        Exportar reporte
                    </span>

                </div>

                <div class="report-actions">

                    {{-- PDF --}}
                    <button
                        type="button"
                        class="btn btn-pdf"
                        data-coreui-toggle="modal"
                        data-coreui-target="#modalPdf">

                        <i class="cil-file me-1"></i>

                        PDF

                    </button>


                    {{-- EXCEL --}}
                  <a
    href="{{ route('reportes.excel', request()->query()) }}"
    class="btn btn-success me-2">

    <i class="cil-spreadsheet"></i>

    Excel

</a>


                    {{-- IMPRIMIR --}}
                    <button
                        type="button"
                        class="btn btn-print">

                        <i class="cil-print me-1"></i>

                        Imprimir

                    </button>

                </div>

            </div>

        </div>

    </div>


    {{-- INFORMACIÓN DEL PERÍODO --}}
    @if(request()->filled('anio') && request()->filled('mes'))

        <div class="report-summary">

            <div class="row align-items-center g-3">

                {{-- REPORTE --}}
                <div class="col-md-4">

                    <div class="summary-label">
                        Reporte
                    </div>

                    <div class="summary-value">
                        {{ $titulo }}
                    </div>

                </div>


                {{-- PERÍODO --}}
                <div class="col-md-4 summary-divider">

                    <div class="summary-label">
                        Período
                    </div>

                    <div class="summary-value">

                        {{ $meses[request('mes')] }}

                        {{ request('anio') }}

                    </div>

                </div>


                {{-- ESTADO --}}
                <div class="col-md-4 summary-divider text-md-end">

                    <div class="summary-label">
                        Estado del período
                    </div>

                    <div>

                        @if($periodo)

                            @if($periodo->estado=='Abierto')

                                <span class="report-status bg-success text-white">

                                    <i class="cil-check-circle"></i>

                                    Abierto

                                </span>

                            @else

                                <span class="report-status bg-danger text-white">

                                    <i class="cil-lock-locked"></i>

                                    Cerrado

                                </span>

                            @endif

                        @else

                            <span class="report-status bg-secondary text-white">

                                <i class="cil-warning"></i>

                                Sin período

                            </span>

                        @endif

                    </div>

                </div>

            </div>

        </div>

    @endif


    {{-- REPORTES RESUMEN --}}
    @if(request()->filled('anio'))

        @switch($reporte)

            @case('estado')

                @include('reportes.partials.resumen_estado')

                @break


            @case('caja')

                @include('reportes.partials.resumen_estado')

                @break


            @case('ingresos')

                @include('reportes.partials.resumen_ingresos')

                @break


            @case('egresos')

                @include('reportes.partials.resumen_egresos')

                @break

        @endswitch

    @endif


    {{-- RESULTADO DEL REPORTE --}}
    @if(request()->filled('anio') && request()->filled('mes'))

        @if($movimientos->isEmpty())

            <div class="report-card report-alert mb-4">

                <div class="report-card-body">

                    <div class="d-flex align-items-center">

                        <i class="cil-warning fs-3 me-3 text-warning"></i>

                        <div>

                            <strong class="text-warning">

                                No existen movimientos registrados.

                            </strong>

                            <br>

                            <span class="text-muted">

                                No hay información para el período

                                <strong>

                                    {{ $meses[request('mes')] }}

                                    {{ request('anio') }}

                                </strong>

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        @else

            @switch($reporte)

                @case('estado')

                    @include('reportes.partials.estado')

                    @break


                @case('ingresos')

                    @include('reportes.partials.ingresos')

                    @break


                @case('egresos')

                    @include('reportes.partials.egresos')

                    @break


                @case('caja')

                    @include('reportes.partials.caja')

                    @break

            @endswitch

        @endif

    @else

        {{-- ESTADO INICIAL --}}
        <div class="report-card">

            <div class="report-empty">

                <div class="report-empty-icon">

                    <i class="cil-description"></i>

                </div>

                <h4>
                    Listo para generar su reporte
                </h4>

                <p>
                    Seleccione el tipo de reporte, año y mes.
                    Luego haga clic en <strong>Consultar</strong>
                    para visualizar la información financiera.
                </p>

            </div>

        </div>

    @endif

</div>


{{-- ========================================================= --}}
{{-- MODAL PDF                                                 --}}
{{-- ========================================================= --}}

<div
    class="modal fade"
    id="modalPdf"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <div class="modal-content">

            <form
                method="GET"
                action="{{ route('reportes.pdf') }}"
                target="_blank">

                <div class="modal-header">

                    <h5 class="modal-title">

                        <i class="cil-file me-2"></i>

                        Generar Reporte PDF

                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-coreui-dismiss="modal"
                        aria-label="Cerrar">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="row mb-3">

                        <div class="col-md-6">

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="estado"
                                    value="1"
                                    checked>

                                <label class="form-check-label">

                                    Estado Financiero

                                </label>

                            </div>

                        </div>


                        <div class="col-md-6">

                            <div class="form-check">

                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="consolidado"
                                    value="1"
                                    checked>

                                <label class="form-check-label">

                                    Consolidado del Período

                                </label>

                            </div>

                        </div>

                    </div>

                    <hr>


                    <div class="row">

                        {{-- AÑO --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Año
                            </label>

                            <select
                                name="anio"
                                class="form-select">

                                @foreach($anios as $anio)

                                    <option value="{{ $anio }}">

                                        {{ $anio }}

                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- DESDE --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Desde
                            </label>

                            <select
                                name="desde"
                                class="form-select">

                                @foreach($meses as $numero=>$nombre)

                                    <option value="{{ $numero }}">

                                        {{ $nombre }}

                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- HASTA --}}
                        <div class="col-md-4">

                            <label class="form-label">
                                Hasta
                            </label>

                            <select
                                name="hasta"
                                class="form-select">

                                @foreach($meses as $numero=>$nombre)

                                    <option
                                        value="{{ $numero }}"
                                        @if($numero==12)
                                            selected
                                        @endif>

                                        {{ $nombre }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-coreui-dismiss="modal">

                        Cerrar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-danger">

                        <i class="cil-file me-1"></i>

                        Generar PDF

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


@endsection