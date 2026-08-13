<div class="ingresos-reporte">

    {{-- ENCABEZADO --}}
    <div class="ingresos-header">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3">

                <div class="ingresos-header-icon">
                    <i class="cil-arrow-circle-top"></i>
                </div>

                <div>

                    <h5>
                        Reporte de Ingresos
                    </h5>

                    <small>
                        Ingresos registrados durante el período seleccionado
                    </small>

                </div>

            </div>


            <div class="ingresos-count">

                <i class="cil-list-numbered"></i>

                {{ $movimientos->count() }}

                <span>
                    ingreso(s)
                </span>

            </div>

        </div>

    </div>


    {{-- TABLA --}}
    <div class="ingresos-table-wrapper">

        <div class="table-responsive">

            <table class="table ingresos-table mb-0">

                <thead>

                    <tr>

                        <th>
                            Fecha
                        </th>

                        <th>
                            Documento
                        </th>

                        <th>
                            Categoría
                        </th>

                        <th>
                            Concepto
                        </th>

                        <th>
                            Persona
                        </th>

                        <th class="text-end">
                            Importe
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($movimientos as $movimiento)

                        <tr>

                            {{-- FECHA --}}
                            <td class="date-cell">

                                {{ $movimiento->fecha->format('d/m/Y') }}

                            </td>


                            {{-- DOCUMENTO --}}
                            <td>

                                <span class="document-badge">

                                    {{ $movimiento->numero }}

                                </span>

                            </td>


                            {{-- CATEGORÍA --}}
                            <td>

                                <span class="category-text">

                                    {{ $movimiento->categoria->nombre }}

                                </span>

                            </td>


                            {{-- CONCEPTO --}}
                            <td>

                                <span class="concept-text">

                                    {{ $movimiento->concepto }}

                                </span>

                            </td>


                            {{-- PERSONA --}}
                            <td>

                                <span class="person-text">

                                    {{ $movimiento->persona ?: '-' }}

                                </span>

                            </td>


                            {{-- IMPORTE --}}
                            <td class="text-end amount-cell">

                                <span class="income-amount">

                                    +{{ $configuracionGlobal->simbolo_moneda }}

                                    {{ number_format($movimiento->monto,2) }}

                                </span>

                            </td>

                        </tr>

                    @endforeach

                </tbody>


                {{-- TOTAL --}}
                <tfoot>

                    <tr>

                        <th colspan="5" class="totals-label">

                            TOTAL INGRESOS

                        </th>

                        <th class="text-end total-income">

                            +{{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['ingresos'],2) }}

                        </th>

                    </tr>

                </tfoot>

            </table>

        </div>

    </div>


    {{-- RESUMEN --}}
    <div class="ingresos-summary">

        <div class="row g-3">

            {{-- CANTIDAD --}}
            <div class="col-md-6">

                <div class="income-summary-box">

                    <div class="income-summary-icon">

                        <i class="cil-list-numbered"></i>

                    </div>

                    <div>

                        <span>
                            Cantidad de ingresos
                        </span>

                        <strong>
                            {{ $movimientos->count() }}
                        </strong>

                    </div>

                </div>

            </div>


            {{-- TOTAL --}}
            <div class="col-md-6">

                <div class="income-summary-box total-box">

                    <div class="income-summary-icon">

                        <i class="cil-arrow-circle-top"></i>

                    </div>

                    <div>

                        <span>
                            Total del período
                        </span>

                        <strong>

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['ingresos'],2) }}

                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


@push('styles')

<style>

    /* =========================================================
       CONTENEDOR
    ========================================================= */

    .ingresos-reporte {
        margin-bottom: 24px;
    }


    /* =========================================================
       ENCABEZADO
    ========================================================= */

    .ingresos-header {
        background: #182235;

        border: 1px solid rgba(148,163,184,.14);
        border-bottom: 0;

        border-radius: 14px 14px 0 0;

        padding: 18px 22px;
    }


    .ingresos-header-icon {
        width: 42px;
        height: 42px;

        border-radius: 10px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: rgba(34,197,94,.10);
        color: #4ade80;

        font-size: 19px;
    }


    .ingresos-header h5 {
        margin: 0;

        color: #f1f5f9;

        font-size: 16px;
        font-weight: 600;
    }


    .ingresos-header small {
        color: #94a3b8;

        font-size: 12px;
    }


    /* =========================================================
       CONTADOR
    ========================================================= */

    .ingresos-count {
        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 8px 13px;

        border-radius: 20px;

        background: rgba(34,197,94,.08);

        border: 1px solid rgba(34,197,94,.18);

        color: #4ade80;

        font-size: 13px;

        font-weight: 600;
    }


    .ingresos-count span {
        color: #94a3b8;

        font-weight: 400;
    }


    /* =========================================================
       TABLA
    ========================================================= */

    .ingresos-table-wrapper {
        background: #182235;

        border-left: 1px solid rgba(148,163,184,.14);
        border-right: 1px solid rgba(148,163,184,.14);

        overflow: hidden;
    }


    .ingresos-table {
        min-width: 950px;

        color: #cbd5e1;
    }


    .ingresos-table thead th {
        background: #111827;

        color: #94a3b8;

        border: 0;

        border-bottom: 1px solid rgba(148,163,184,.14);

        padding: 14px 12px;

        font-size: 11px;

        font-weight: 600;

        text-transform: uppercase;

        letter-spacing: .5px;

        white-space: nowrap;
    }


    .ingresos-table tbody td {
        padding: 13px 12px;

        border-color: rgba(148,163,184,.08);

        vertical-align: middle;

        font-size: 13px;
    }


    .ingresos-table tbody tr {
        transition: background .15s ease;
    }


    .ingresos-table tbody tr:hover {
        background: rgba(34,197,94,.045);
    }


    /* =========================================================
       CAMPOS
    ========================================================= */

    .date-cell {
        color: #cbd5e1;

        white-space: nowrap;
    }


    .document-badge {
        display: inline-block;

        padding: 4px 8px;

        border-radius: 6px;

        background: #202c40;

        border: 1px solid rgba(148,163,184,.12);

        color: #94a3b8;

        font-size: 11px;

        font-weight: 600;
    }


    .category-text {
        color: #cbd5e1;

        white-space: nowrap;
    }


    .concept-text {
        color: #e2e8f0;
    }


    .person-text {
        color: #94a3b8;
    }


    /* =========================================================
       IMPORTE
    ========================================================= */

    .amount-cell {
        white-space: nowrap;
    }


    .income-amount {
        color: #4ade80;

        font-weight: 600;

        font-variant-numeric: tabular-nums;
    }


    /* =========================================================
       TOTAL
    ========================================================= */

    .ingresos-table tfoot th {
        background: #111827;

        border: 0;

        border-top: 1px solid rgba(148,163,184,.18);

        padding: 15px 12px;

        font-size: 13px;
    }


    .totals-label {
        color: #f1f5f9;

        letter-spacing: .4px;
    }


    .total-income {
        color: #4ade80 !important;

        font-weight: 700;
    }


    /* =========================================================
       RESUMEN
    ========================================================= */

    .ingresos-summary {
        background: #111827;

        border: 1px solid rgba(148,163,184,.14);

        border-top: 0;

        border-radius: 0 0 14px 14px;

        padding: 18px;
    }


    .income-summary-box {
        height: 100%;

        display: flex;

        align-items: center;

        gap: 13px;

        padding: 15px;

        border-radius: 11px;

        background: #182235;

        border: 1px solid rgba(148,163,184,.10);

        transition: all .2s ease;
    }


    .income-summary-box:hover {
        transform: translateY(-2px);

        border-color: rgba(34,197,94,.20);
    }


    .income-summary-icon {
        width: 38px;
        height: 38px;

        flex-shrink: 0;

        border-radius: 9px;

        display: flex;

        align-items: center;
        justify-content: center;

        background: rgba(34,197,94,.10);

        color: #4ade80;

        font-size: 17px;
    }


    .income-summary-box span {
        display: block;

        color: #64748b;

        font-size: 11px;

        text-transform: uppercase;

        letter-spacing: .4px;

        margin-bottom: 3px;
    }


    .income-summary-box strong {
        display: block;

        color: #f1f5f9;

        font-size: 17px;

        font-weight: 700;
    }


    .total-box strong {
        color: #4ade80;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 768px) {

        .ingresos-header {
            padding: 16px;
        }


        .ingresos-summary {
            padding: 14px;
        }


        .income-summary-box {
            padding: 13px;
        }

    }

</style>

@endpush