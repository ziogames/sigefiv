<div class="egresos-reporte">

    {{-- ENCABEZADO --}}
    <div class="egresos-header">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3">

                <div class="egresos-header-icon">
                    <i class="cil-arrow-circle-bottom"></i>
                </div>

                <div>

                    <h5>
                        Reporte de Egresos
                    </h5>

                    <small>
                        Egresos registrados durante el período seleccionado
                    </small>

                </div>

            </div>


            <div class="egresos-count">

                <i class="cil-list-numbered"></i>

                {{ $movimientos->count() }}

                <span>
                    egreso(s)
                </span>

            </div>

        </div>

    </div>


    {{-- TABLA --}}
    <div class="egresos-table-wrapper">

        <div class="table-responsive">

            <table class="table egresos-table mb-0">

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

                                <span class="expense-amount">

                                    -{{ $configuracionGlobal->simbolo_moneda }}

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

                            TOTAL EGRESOS

                        </th>

                        <th class="text-end total-expense">

                            -{{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['egresos'],2) }}

                        </th>

                    </tr>

                </tfoot>

            </table>

        </div>

    </div>


    {{-- RESUMEN --}}
    <div class="egresos-summary">

        <div class="row g-3">

            {{-- CANTIDAD --}}
            <div class="col-md-6">

                <div class="expense-summary-box">

                    <div class="expense-summary-icon">

                        <i class="cil-list-numbered"></i>

                    </div>

                    <div>

                        <span>
                            Cantidad de egresos
                        </span>

                        <strong>
                            {{ $movimientos->count() }}
                        </strong>

                    </div>

                </div>

            </div>


            {{-- TOTAL --}}
            <div class="col-md-6">

                <div class="expense-summary-box total-box">

                    <div class="expense-summary-icon">

                        <i class="cil-arrow-circle-bottom"></i>

                    </div>

                    <div>

                        <span>
                            Total del período
                        </span>

                        <strong>

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['egresos'],2) }}

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

    .egresos-reporte {
        margin-bottom: 24px;
    }


    /* =========================================================
       ENCABEZADO
    ========================================================= */

    .egresos-header {
        background: #182235;

        border: 1px solid rgba(148,163,184,.14);
        border-bottom: 0;

        border-radius: 14px 14px 0 0;

        padding: 18px 22px;
    }


    .egresos-header-icon {
        width: 42px;
        height: 42px;

        border-radius: 10px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: rgba(239,68,68,.10);
        color: #f87171;

        font-size: 19px;
    }


    .egresos-header h5 {
        margin: 0;

        color: #f1f5f9;

        font-size: 16px;
        font-weight: 600;
    }


    .egresos-header small {
        color: #94a3b8;

        font-size: 12px;
    }


    /* =========================================================
       CONTADOR
    ========================================================= */

    .egresos-count {
        display: inline-flex;

        align-items: center;

        gap: 7px;

        padding: 8px 13px;

        border-radius: 20px;

        background: rgba(239,68,68,.08);

        border: 1px solid rgba(239,68,68,.18);

        color: #f87171;

        font-size: 13px;

        font-weight: 600;
    }


    .egresos-count span {
        color: #94a3b8;

        font-weight: 400;
    }


    /* =========================================================
       TABLA
    ========================================================= */

    .egresos-table-wrapper {
        background: #182235;

        border-left: 1px solid rgba(148,163,184,.14);
        border-right: 1px solid rgba(148,163,184,.14);

        overflow: hidden;
    }


    .egresos-table {
        min-width: 950px;

        color: #cbd5e1;
    }


    .egresos-table thead th {
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


    .egresos-table tbody td {
        padding: 13px 12px;

        border-color: rgba(148,163,184,.08);

        vertical-align: middle;

        font-size: 13px;
    }


    .egresos-table tbody tr {
        transition: background .15s ease;
    }


    .egresos-table tbody tr:hover {
        background: rgba(239,68,68,.045);
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


    .expense-amount {
        color: #f87171;

        font-weight: 600;

        font-variant-numeric: tabular-nums;
    }


    /* =========================================================
       TOTAL
    ========================================================= */

    .egresos-table tfoot th {
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


    .total-expense {
        color: #f87171 !important;

        font-weight: 700;
    }


    /* =========================================================
       RESUMEN
    ========================================================= */

    .egresos-summary {
        background: #111827;

        border: 1px solid rgba(148,163,184,.14);

        border-top: 0;

        border-radius: 0 0 14px 14px;

        padding: 18px;
    }


    .expense-summary-box {
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


    .expense-summary-box:hover {
        transform: translateY(-2px);

        border-color: rgba(239,68,68,.20);
    }


    .expense-summary-icon {
        width: 38px;
        height: 38px;

        flex-shrink: 0;

        border-radius: 9px;

        display: flex;

        align-items: center;
        justify-content: center;

        background: rgba(239,68,68,.10);

        color: #f87171;

        font-size: 17px;
    }


    .expense-summary-box span {
        display: block;

        color: #64748b;

        font-size: 11px;

        text-transform: uppercase;

        letter-spacing: .4px;

        margin-bottom: 3px;
    }


    .expense-summary-box strong {
        display: block;

        color: #f1f5f9;

        font-size: 17px;

        font-weight: 700;
    }


    .total-box strong {
        color: #f87171;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 768px) {

        .egresos-header {
            padding: 16px;
        }


        .egresos-summary {
            padding: 14px;
        }


        .expense-summary-box {
            padding: 13px;
        }

    }

</style>

@endpush