<div class="estado-reporte">

    {{-- ENCABEZADO --}}
    <div class="estado-header">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

            <div class="d-flex align-items-center gap-3">

                <div class="estado-header-icon">
                    <i class="cil-list-rich"></i>
                </div>

                <div>

                    <h5>
                        Detalle del Estado de Cuenta
                    </h5>

                    <small>
                        Movimientos registrados del período seleccionado
                    </small>

                </div>

            </div>


            <div class="movimientos-badge">

                <i class="cil-list-numbered"></i>

                {{ $movimientos->count() }}

                <span>
                    movimiento(s)
                </span>

            </div>

        </div>

    </div>


    {{-- TABLA --}}
    <div class="estado-table-wrapper">

        <div class="table-responsive">

            <table class="table estado-table mb-0">

                <thead>

                    <tr>

                        <th class="text-center">
                            Tipo
                        </th>

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
                            Ingreso
                        </th>

                        <th class="text-end">
                            Egreso
                        </th>

                        <th class="text-end">
                            Saldo
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($movimientos as $movimiento)

                        <tr>

                            {{-- TIPO --}}
                            <td class="text-center">

                                @if($movimiento->tipo=='Ingreso')

                                    <span class="movement-type income">

                                        <i class="cil-arrow-top"></i>

                                        Ingreso

                                    </span>

                                @else

                                    <span class="movement-type expense">

                                        <i class="cil-arrow-bottom"></i>

                                        Egreso

                                    </span>

                                @endif

                            </td>


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


                            {{-- INGRESO --}}
                            <td class="amount-cell">

                                @if($movimiento->tipo=='Ingreso')

                                    <span class="amount income-amount">

                                        +{{ $configuracionGlobal->simbolo_moneda }}

                                        {{ number_format($movimiento->monto,2) }}

                                    </span>

                                @else

                                    <span class="empty-amount">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- EGRESO --}}
                            <td class="amount-cell">

                                @if($movimiento->tipo=='Egreso')

                                    <span class="amount expense-amount">

                                        -{{ $configuracionGlobal->simbolo_moneda }}

                                        {{ number_format($movimiento->monto,2) }}

                                    </span>

                                @else

                                    <span class="empty-amount">
                                        —
                                    </span>

                                @endif

                            </td>


                            {{-- SALDO --}}
                            <td class="amount-cell">

                                @if($movimiento->saldo>=0)

                                    <span class="amount balance-positive">

                                @else

                                    <span class="amount balance-negative">

                                @endif

                                    {{ $configuracionGlobal->simbolo_moneda }}

                                    {{ number_format($movimiento->saldo,2) }}

                                </span>

                            </td>

                        </tr>

                    @endforeach

                </tbody>


                {{-- TOTALES --}}
                <tfoot>

                    <tr>

                        <th colspan="6" class="totals-label">

                            TOTALES

                        </th>


                        <th class="text-end total-income">

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['ingresos'],2) }}

                        </th>


                        <th class="text-end total-expense">

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['egresos'],2) }}

                        </th>


                        <th class="text-end total-balance">

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['saldo_caja'],2) }}

                        </th>

                    </tr>

                </tfoot>

            </table>

        </div>

    </div>


    {{-- RESUMEN INFERIOR --}}
    <div class="estado-summary">

        <div class="row g-3">

            {{-- REGISTROS --}}
            <div class="col-xl-3 col-md-6">

                <div class="summary-box records">

                    <div class="summary-box-icon">
                        <i class="cil-list-numbered"></i>
                    </div>

                    <div>

                        <span>
                            Registros
                        </span>

                        <strong>
                            {{ $movimientos->count() }}
                        </strong>

                    </div>

                </div>

            </div>


            {{-- INGRESOS --}}
            <div class="col-xl-3 col-md-6">

                <div class="summary-box income-box">

                    <div class="summary-box-icon">
                        <i class="cil-arrow-top"></i>
                    </div>

                    <div>

                        <span>
                            Ingresos
                        </span>

                        <strong>

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['ingresos'],2) }}

                        </strong>

                    </div>

                </div>

            </div>


            {{-- EGRESOS --}}
            <div class="col-xl-3 col-md-6">

                <div class="summary-box expense-box">

                    <div class="summary-box-icon">
                        <i class="cil-arrow-bottom"></i>
                    </div>

                    <div>

                        <span>
                            Egresos
                        </span>

                        <strong>

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['egresos'],2) }}

                        </strong>

                    </div>

                </div>

            </div>


            {{-- SALDO CAJA --}}
            <div class="col-xl-3 col-md-6">

                <div class="summary-box balance-box">

                    <div class="summary-box-icon">
                        <i class="cil-wallet"></i>
                    </div>

                    <div>

                        <span>
                            Saldo en Caja
                        </span>

                        <strong>

                            {{ $configuracionGlobal->simbolo_moneda }}

                            {{ number_format($resumen['saldo_caja'],2) }}

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

    .estado-reporte {
        margin-bottom: 24px;
    }


    /* =========================================================
       ENCABEZADO
    ========================================================= */

    .estado-header {
        background: #182235;
        border: 1px solid rgba(148,163,184,.14);
        border-bottom: 0;
        border-radius: 14px 14px 0 0;
        padding: 18px 22px;
    }


    .estado-header-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;

        display: flex;
        align-items: center;
        justify-content: center;

        background: rgba(59,130,246,.12);
        color: #60a5fa;

        font-size: 19px;
    }


    .estado-header h5 {
        margin: 0;
        color: #f1f5f9;
        font-size: 16px;
        font-weight: 600;
    }


    .estado-header small {
        color: #94a3b8;
        font-size: 12px;
    }


    /* =========================================================
       CONTADOR
    ========================================================= */

    .movimientos-badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;

        padding: 8px 13px;

        border-radius: 20px;

        background: rgba(59,130,246,.10);
        border: 1px solid rgba(59,130,246,.20);

        color: #60a5fa;

        font-size: 13px;
        font-weight: 600;
    }


    .movimientos-badge span {
        color: #94a3b8;
        font-weight: 400;
    }


    /* =========================================================
       TABLA
    ========================================================= */

    .estado-table-wrapper {
        background: #182235;

        border-left: 1px solid rgba(148,163,184,.14);
        border-right: 1px solid rgba(148,163,184,.14);

        overflow: hidden;
    }


    .estado-table {
        min-width: 1100px;
        color: #cbd5e1;
    }


    .estado-table thead th {
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


    .estado-table tbody td {
        padding: 13px 12px;

        border-color: rgba(148,163,184,.08);

        vertical-align: middle;

        font-size: 13px;
    }


    .estado-table tbody tr {
        transition: background .15s ease;
    }


    .estado-table tbody tr:hover {
        background: rgba(59,130,246,.055);
    }


    /* =========================================================
       TIPO MOVIMIENTO
    ========================================================= */

    .movement-type {
        display: inline-flex;
        align-items: center;
        gap: 5px;

        padding: 5px 9px;

        border-radius: 6px;

        font-size: 11px;
        font-weight: 600;
    }


    .movement-type.income {
        color: #4ade80;
        background: rgba(34,197,94,.10);
    }


    .movement-type.expense {
        color: #f87171;
        background: rgba(239,68,68,.10);
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
       IMPORTES
    ========================================================= */

    .amount-cell {
        white-space: nowrap;
    }


    .amount {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }


    .income-amount {
        color: #4ade80;
    }


    .expense-amount {
        color: #f87171;
    }


    .balance-positive {
        color: #38bdf8;
    }


    .balance-negative {
        color: #f87171;
    }


    .empty-amount {
        color: #475569;
    }


    /* =========================================================
       TOTALES
    ========================================================= */

    .estado-table tfoot th {
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
    }


    .total-expense {
        color: #f87171 !important;
    }


    .total-balance {
        color: #38bdf8 !important;
    }


    /* =========================================================
       RESUMEN
    ========================================================= */

    .estado-summary {
        background: #111827;

        border: 1px solid rgba(148,163,184,.14);
        border-top: 0;

        border-radius: 0 0 14px 14px;

        padding: 18px;
    }


    .summary-box {
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


    .summary-box:hover {
        transform: translateY(-2px);
        border-color: rgba(148,163,184,.20);
    }


    .summary-box-icon {
        width: 38px;
        height: 38px;

        flex-shrink: 0;

        border-radius: 9px;

        display: flex;
        align-items: center;
        justify-content: center;

        font-size: 17px;
    }


    .summary-box span {
        display: block;

        color: #64748b;

        font-size: 11px;

        text-transform: uppercase;
        letter-spacing: .4px;

        margin-bottom: 3px;
    }


    .summary-box strong {
        display: block;

        color: #f1f5f9;

        font-size: 17px;

        font-weight: 700;
    }


    /* COLORES */

    .records .summary-box-icon {
        background: rgba(59,130,246,.10);
        color: #60a5fa;
    }


    .income-box .summary-box-icon {
        background: rgba(34,197,94,.10);
        color: #4ade80;
    }


    .income-box strong {
        color: #4ade80;
    }


    .expense-box .summary-box-icon {
        background: rgba(239,68,68,.10);
        color: #f87171;
    }


    .expense-box strong {
        color: #f87171;
    }


    .balance-box .summary-box-icon {
        background: rgba(14,165,233,.10);
        color: #38bdf8;
    }


    .balance-box strong {
        color: #38bdf8;
    }


    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 768px) {

        .estado-header {
            padding: 16px;
        }


        .estado-summary {
            padding: 14px;
        }


        .summary-box {
            padding: 13px;
        }

    }

</style>

@endpush