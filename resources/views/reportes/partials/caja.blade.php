<div class="card shadow-sm border-0">

    {{-- ENCABEZADO --}}
    <div class="card-header py-3">

        <div class="d-flex justify-content-between align-items-center">

            <div>
                <h4 class="mb-1">
                    <i class="cil-wallet me-2"></i>
                    Estado de Cuenta y Balance de Caja
                </h4>

                <small class="text-muted">
                    Consolidado de Ingresos y Egresos
                </small>
            </div>

            <div class="text-end">

                <div class="fw-semibold">
                    Período {{ request('anio') }}
                </div>

                <small class="text-muted">
                    Enero — Diciembre
                </small>

            </div>

        </div>

    </div>


    {{-- RESUMEN --}}
    <div class="card-body">

        @php

            $saldoInicialPeriodo =
                $consolidado[0]->saldo_inicial ?? 0;

            $totalIngresosPeriodo =
                collect($consolidado)->sum('total_ingresos');

            $totalEgresosPeriodo =
                collect($consolidado)->sum('total_egresos');

            $saldoFinalPeriodo =
                collect($consolidado)->last()->saldo_final ?? 0;

        @endphp


        <div class="row g-3 mb-4">

            {{-- SALDO INICIAL --}}
            <div class="col-md-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body">

                        <div class="text-muted small mb-1">
                            Saldo inicial del período
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ $configuracionGlobal->simbolo_moneda }}
                            {{ number_format($saldoInicialPeriodo, 2) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- INGRESOS --}}
            <div class="col-md-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body">

                        <div class="text-muted small mb-1">
                            Total ingresos
                        </div>

                        <div class="fs-4 fw-bold text-success">

                            +
                            {{ $configuracionGlobal->simbolo_moneda }}
                            {{ number_format($totalIngresosPeriodo, 2) }}

                        </div>

                    </div>

                </div>

            </div>


            {{-- EGRESOS --}}
            <div class="col-md-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body">

                        <div class="text-muted small mb-1">
                            Total egresos
                        </div>

                        <div class="fs-4 fw-bold text-danger">

                            -
                            {{ $configuracionGlobal->simbolo_moneda }}
                            {{ number_format($totalEgresosPeriodo, 2) }}

                        </div>

                    </div>

                </div>

            </div>


            {{-- SALDO FINAL --}}
            <div class="col-md-3">

                <div class="card h-100 border-0 shadow-sm">

                    <div class="card-body">

                        <div class="text-muted small mb-1">
                            Saldo en caja al cierre
                        </div>

                        <div
                            class="fs-4 fw-bold
                            {{ $saldoFinalPeriodo >= 0
                                ? 'text-primary'
                                : 'text-danger' }}">

                            {{ $configuracionGlobal->simbolo_moneda }}
                            {{ number_format($saldoFinalPeriodo, 2) }}

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- TABLA MENSUAL --}}
        <div class="card border-0 shadow-sm">

            <div class="card-header">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-0">
                            <i class="cil-chart-line me-2"></i>
                            Consolidado mensual
                        </h5>

                    </div>

                    <span class="badge bg-secondary">

                        {{ count($consolidado) }} mes(es)

                    </span>

                </div>

            </div>


            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-dark">

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

                            @php

                                $nombreMes = $meses[$fila->mes]
                                    ?? $fila->mes;

                            @endphp

                            <tr>

                                <td>

                                    <div class="fw-semibold">
                                        {{ $nombreMes }}
                                    </div>

                                    <small class="text-muted">
                                        {{ $fila->anio }}
                                    </small>

                                </td>


                                <td class="text-end">

                                    {{ $configuracionGlobal->simbolo_moneda }}
                                    {{ number_format($fila->saldo_inicial, 2) }}

                                </td>


                                <td class="text-end">

                                    @if($fila->total_ingresos > 0)

                                        <span class="text-success fw-semibold">

                                            +
                                            {{ $configuracionGlobal->simbolo_moneda }}
                                            {{ number_format($fila->total_ingresos, 2) }}

                                        </span>

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                <td class="text-end">

                                    @if($fila->total_egresos > 0)

                                        <span class="text-danger fw-semibold">

                                            -
                                            {{ $configuracionGlobal->simbolo_moneda }}
                                            {{ number_format($fila->total_egresos, 2) }}

                                        </span>

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                <td class="text-end">

                                    <span
                                        class="fw-bold
                                        {{ $fila->saldo_final >= 0
                                            ? 'text-primary'
                                            : 'text-danger' }}">

                                        {{ $configuracionGlobal->simbolo_moneda }}
                                        {{ number_format($fila->saldo_final, 2) }}

                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center py-5">

                                    <i class="cil-wallet fs-1 text-muted"></i>

                                    <div class="mt-2 text-muted">

                                        No existen datos de caja
                                        para el año seleccionado.

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>


                    {{-- TOTALES --}}
                    @if(count($consolidado) > 0)

                        <tfoot class="table-dark">

                            <tr>

                                <th>
                                    TOTAL
                                </th>

                                <th></th>

                                <th class="text-end">

                                    {{ $configuracionGlobal->simbolo_moneda }}
                                    {{ number_format($totalIngresosPeriodo, 2) }}

                                </th>

                                <th class="text-end">

                                    {{ $configuracionGlobal->simbolo_moneda }}
                                    {{ number_format($totalEgresosPeriodo, 2) }}

                                </th>

                                <th class="text-end">

                                    {{ $configuracionGlobal->simbolo_moneda }}
                                    {{ number_format($saldoFinalPeriodo, 2) }}

                                </th>

                            </tr>

                        </tfoot>

                    @endif

                </table>

            </div>

        </div>


        {{-- BALANCE FINAL --}}
        @if(count($consolidado) > 0)

            <div class="card border-0 shadow-sm mt-4">

                <div class="card-body">

                    <div class="row align-items-center">

                        <div class="col-md-8">

                            <h5 class="mb-1">
                                Balance del período
                            </h5>

                            <small class="text-muted">

                                Saldo inicial + ingresos − egresos

                            </small>

                        </div>


                        <div class="col-md-4 text-md-end">

                            <div class="fs-3 fw-bold
                                {{ $saldoFinalPeriodo >= 0
                                    ? 'text-primary'
                                    : 'text-danger' }}">

                                {{ $configuracionGlobal->simbolo_moneda }}
                                {{ number_format($saldoFinalPeriodo, 2) }}

                            </div>

                            <small class="text-muted">
                                Saldo en caja
                            </small>

                        </div>

                    </div>

                </div>

            </div>

        @endif

    </div>

</div>