<div class="report-summary-card mb-4">

    <div class="report-summary-title">

        <div>
            <span class="report-summary-icon">
                <i class="cil-chart"></i>
            </span>
        </div>

        <div>
            <h5>
                Resumen Financiero
            </h5>

            <small>
                Estado de cuenta del período seleccionado
            </small>
        </div>

    </div>


    <div class="row g-3">

        {{-- SALDO INICIAL --}}
        <div class="col-xl col-lg-4 col-md-6">

            <div class="financial-card">

                <div class="financial-card-top">

                    <span class="financial-icon initial">
                        <i class="cil-wallet"></i>
                    </span>

                    <span class="financial-label">
                        Saldo Inicial
                    </span>

                </div>

                <div class="financial-value">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['saldo_inicial'],2) }}

                </div>

            </div>

        </div>


        {{-- INGRESOS --}}
        <div class="col-xl col-lg-4 col-md-6">

            <div class="financial-card">

                <div class="financial-card-top">

                    <span class="financial-icon income">
                        <i class="cil-arrow-top"></i>
                    </span>

                    <span class="financial-label">
                        Total Ingresos
                    </span>

                </div>

                <div class="financial-value income-text">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['ingresos'],2) }}

                </div>

            </div>

        </div>


        {{-- DISPONIBLE --}}
        <div class="col-xl col-lg-4 col-md-6">

            <div class="financial-card featured">

                <div class="financial-card-top">

                    <span class="financial-icon available">
                        <i class="cil-money"></i>
                    </span>

                    <span class="financial-label">
                        Disponible
                    </span>

                </div>

                <div class="financial-value available-text">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['disponible'],2) }}

                </div>

            </div>

        </div>


        {{-- EGRESOS --}}
        <div class="col-xl col-lg-4 col-md-6">

            <div class="financial-card">

                <div class="financial-card-top">

                    <span class="financial-icon expense">
                        <i class="cil-arrow-bottom"></i>
                    </span>

                    <span class="financial-label">
                        Total Egresos
                    </span>

                </div>

                <div class="financial-value expense-text">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['egresos'],2) }}

                </div>

            </div>

        </div>


        {{-- SALDO CAJA --}}
        <div class="col-xl col-lg-4 col-md-6">

            <div class="financial-card">

                <div class="financial-card-top">

                    <span class="financial-icon cash">
                        <i class="cil-home"></i>
                    </span>

                    <span class="financial-label">
                        Saldo en Caja
                    </span>

                </div>

                <div class="financial-value cash-text">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['saldo_caja'],2) }}

                </div>

            </div>

        </div>

    </div>

</div>


<style>

    .report-summary-card {
        background: #182235;
        border: 1px solid rgba(148,163,184,.14);
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 8px 25px rgba(0,0,0,.10);
    }


    /* ENCABEZADO */

    .report-summary-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }


    .report-summary-title h5 {
        margin: 0;
        color: #f1f5f9;
        font-size: 16px;
        font-weight: 600;
    }


    .report-summary-title small {
        color: #94a3b8;
        font-size: 12px;
    }


    .report-summary-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(59,130,246,.12);
        color: #60a5fa;
        font-size: 18px;
    }


    /* TARJETAS */

    .financial-card {
        height: 100%;
        padding: 18px;
        border-radius: 12px;
        background: #202c40;
        border: 1px solid rgba(148,163,184,.12);
        transition: all .2s ease;
    }


    .financial-card:hover {
        transform: translateY(-2px);
        border-color: rgba(96,165,250,.30);
        box-shadow: 0 8px 20px rgba(0,0,0,.12);
    }


    .financial-card.featured {
        background: linear-gradient(
            145deg,
            #1e3a5f,
            #202c40
        );

        border-color: rgba(96,165,250,.28);
    }


    /* PARTE SUPERIOR */

    .financial-card-top {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 16px;
    }


    .financial-label {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
    }


    /* ICONOS */

    .financial-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }


    .financial-icon.initial {
        background: rgba(59,130,246,.12);
        color: #60a5fa;
    }


    .financial-icon.income {
        background: rgba(34,197,94,.12);
        color: #4ade80;
    }


    .financial-icon.available {
        background: rgba(14,165,233,.12);
        color: #38bdf8;
    }


    .financial-icon.expense {
        background: rgba(239,68,68,.12);
        color: #f87171;
    }


    .financial-icon.cash {
        background: rgba(245,158,11,.12);
        color: #fbbf24;
    }


    /* VALORES */

    .financial-value {
        color: #f1f5f9;
        font-size: 21px;
        font-weight: 700;
        letter-spacing: -.3px;
    }


    .income-text {
        color: #4ade80;
    }


    .available-text {
        color: #38bdf8;
    }


    .expense-text {
        color: #f87171;
    }


    .cash-text {
        color: #fbbf24;
    }


    @media (max-width: 768px) {

        .report-summary-card {
            padding: 16px;
        }

        .financial-card {
            padding: 16px;
        }

        .financial-value {
            font-size: 19px;
        }

    }

</style>