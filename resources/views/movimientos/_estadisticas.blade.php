<div class="mov-summary">

    {{-- =====================================================
         SALDO INICIAL
         ===================================================== --}}

    <div class="mov-summary-card mov-card-blue">

        <div class="mov-summary-icon">
            <i class="cil-wallet"></i>
        </div>

        <div class="mov-summary-content">

            <span class="mov-summary-label">
                Saldo Inicial
            </span>

            <strong class="mov-summary-value">
                S/ {{ number_format($saldoInicial, 2) }}
            </strong>

        </div>

    </div>


    {{-- =====================================================
         TOTAL INGRESOS
         ===================================================== --}}

    <div class="mov-summary-card mov-card-green">

        <div class="mov-summary-icon">
            <i class="cil-arrow-thick-top"></i>
        </div>

        <div class="mov-summary-content">

            <span class="mov-summary-label">
                Total Ingresos
            </span>

            <strong class="mov-summary-value">
                S/ {{ number_format($totalIngresos, 2) }}
            </strong>

            <small>
                {{ $cantidadIngresos }}
                {{ $cantidadIngresos == 1 ? 'movimiento' : 'movimientos' }}
            </small>

        </div>

    </div>


    {{-- =====================================================
         DISPONIBLE
         ===================================================== --}}

    <div class="mov-summary-card mov-card-yellow">

        <div class="mov-summary-icon">
            <i class="cil-chart-line"></i>
        </div>

        <div class="mov-summary-content">

            <span class="mov-summary-label">
                Disponible
            </span>

            <strong class="mov-summary-value">
                S/ {{ number_format($disponible, 2) }}
            </strong>

        </div>

    </div>


    {{-- =====================================================
         TOTAL EGRESOS
         ===================================================== --}}

    <div class="mov-summary-card mov-card-red">

        <div class="mov-summary-icon">
            <i class="cil-arrow-thick-bottom"></i>
        </div>

        <div class="mov-summary-content">

            <span class="mov-summary-label">
                Total Egresos
            </span>

            <strong class="mov-summary-value">
                S/ {{ number_format($totalEgresos, 2) }}
            </strong>

            <small>
                {{ $cantidadEgresos }}
                {{ $cantidadEgresos == 1 ? 'movimiento' : 'movimientos' }}
            </small>

        </div>

    </div>


    {{-- =====================================================
         SALDO EN CAJA
         ===================================================== --}}

    <div class="mov-summary-card mov-card-cyan">

        <div class="mov-summary-icon">
            <i class="cil-money"></i>
        </div>

        <div class="mov-summary-content">

            <span class="mov-summary-label">
                Saldo en Caja
            </span>

            <strong class="mov-summary-value">
                S/ {{ number_format($saldoCaja, 2) }}
            </strong>

        </div>

    </div>

</div>