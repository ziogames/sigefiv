<div class="dashboard-kpis">

    {{-- =====================================================
         SALDO EN CAJA
    ====================================================== --}}

    <div class="dashboard-kpi-card kpi-caja">

        <div class="kpi-content">

            <div class="kpi-label">
                Saldo en Caja
            </div>

            <div
                id="saldoCaja"
                class="kpi-value">

                {{ $configuracionGlobal->simbolo_moneda }}
                {{ number_format($saldoCaja, 2) }}

            </div>

            <div
                id="saldoTendencia"
                class="kpi-status kpi-status-neutral">

                <span class="kpi-status-dot"></span>

                Disponible

            </div>

        </div>


        <div class="kpi-icon">

    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H20v3H5.5A1.5 1.5 0 0 0 4 9.5v7A2.5 2.5 0 0 0 6.5 19H20V8H6.5A2.5 2.5 0 0 0 4 10.5"/>
        <path d="M20 11h-3.5a2.5 2.5 0 0 0 0 5H20z"/>
        <circle cx="16.5" cy="13.5" r=".8"/>
    </svg>

</div>

    </div>


    {{-- =====================================================
         INGRESOS
    ====================================================== --}}

    <div class="dashboard-kpi-card kpi-ingresos">

        <div class="kpi-content">

            <div class="kpi-label">
                Ingresos (Año)
            </div>

            <div
                id="ingresos"
                class="kpi-value">

                {{ $configuracionGlobal->simbolo_moneda }}
                {{ number_format($ingresos, 2) }}

            </div>

            <div
                id="ingresosVariacion"
                class="kpi-status kpi-status-success">

                <span class="kpi-status-dot"></span>

                Total anual

            </div>

        </div>


     <div class="kpi-icon">

    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 17l6-6 4 4 6-7"/>
        <path d="M15 8h5v5"/>
    </svg>

</div>

    </div>


    {{-- =====================================================
         EGRESOS
    ====================================================== --}}

    <div class="dashboard-kpi-card kpi-egresos">

        <div class="kpi-content">

            <div class="kpi-label">
                Egresos (Año)
            </div>

            <div
                id="egresos"
                class="kpi-value">

                {{ $configuracionGlobal->simbolo_moneda }}
                {{ number_format($egresos, 2) }}

            </div>

            <div
                id="egresosVariacion"
                class="kpi-status kpi-status-danger">

                <span class="kpi-status-dot"></span>

                Total anual

            </div>

        </div>

<div class="kpi-icon">

    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 7l6 6 4-4 6 7"/>
        <path d="M15 16h5v-5"/>
    </svg>

</div>

    </div>


    {{-- =====================================================
         SALDO FINAL
    ====================================================== --}}

    <div class="dashboard-kpi-card kpi-saldo">

        <div class="kpi-content">

            <div class="kpi-label">
                Saldo Final (Año)
            </div>

            <div
                id="saldoFinal"
                class="kpi-value">

                {{ $configuracionGlobal->simbolo_moneda }}
                {{ number_format($saldoCaja, 2) }}

            </div>

            <div class="kpi-status kpi-status-warning">

                <span class="kpi-status-dot"></span>

                Saldo actual

            </div>

        </div>


       <div class="kpi-icon">

    <svg viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 9h18"/>
        <path d="M5 9v9M9 9v9M15 9v9M19 9v9"/>
        <path d="M3 18h18"/>
        <path d="M2 7l10-4 10 4"/>
    </svg>

</div>

    </div>

</div>


<style>

/* =========================================================
   CONTENEDOR
========================================================= */

.dashboard-kpis {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 14px;

}


/* =========================================================
   TARJETA
========================================================= */

.dashboard-kpi-card {

    position: relative;

    min-height: 113px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 18px 20px;

    overflow: hidden;

    background:
        linear-gradient(
            145deg,
            #172033 0%,
            #1b273a 100%
        );

    border: 1px solid rgba(92, 117, 150, .25);

    border-radius: 8px;

    box-shadow:
        0 5px 18px rgba(0, 0, 0, .16);

    transition:
        transform .2s ease,
        border-color .2s ease,
        box-shadow .2s ease;

}


/* =========================================================
   HOVER
========================================================= */

.dashboard-kpi-card:hover {

    transform: translateY(-2px);

    border-color:
        rgba(120, 150, 190, .38);

    box-shadow:
        0 8px 22px rgba(0, 0, 0, .22);

}


/* =========================================================
   CONTENIDO
========================================================= */

.kpi-content {

    min-width: 0;

}


.kpi-label {

    margin-bottom: 6px;

    color: #c7cfdb;

    font-size: 11px;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: .55px;

}


.kpi-value {

    color: #f4f7fb;

    font-size: 25px;

    line-height: 1.1;

    font-weight: 800;

    letter-spacing: -.5px;

    white-space: nowrap;

}


/* =========================================================
   ESTADOS
========================================================= */

.kpi-status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    margin-top: 7px;

    font-size: 10px;

    font-weight: 600;

}


.kpi-status-dot {

    width: 6px;

    height: 6px;

    border-radius: 50%;

    background: currentColor;

}


.kpi-status-neutral {

    color: #4d9cff;

}


.kpi-status-success {

    color: #20c95a;

}


.kpi-status-danger {

    color: #ff5757;

}


.kpi-status-warning {

    color: #e9a51a;

}


/* =========================================================
   COLORES DE LOS VALORES
========================================================= */

.kpi-caja .kpi-value {

    color: #f4f7fb;

}


.kpi-ingresos .kpi-value {

    color: #20c95a;

}


.kpi-egresos .kpi-value {

    color: #ff5757;

}


.kpi-saldo .kpi-value {

    color: #f4f7fb;

}


/* =========================================================
   ICONOS
========================================================= */

.kpi-icon {

    width: 48px;

    height: 48px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 13px;

    flex-shrink: 0;

}


/* Iconos SVG */

.kpi-icon svg {

    width: 27px;

    height: 27px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;

}

/* SALDO */

.kpi-caja .kpi-icon {

    background:
        rgba(67, 126, 221, .20);

    color: #75a9ff;

}


/* INGRESOS */

.kpi-ingresos .kpi-icon {

    background:
        rgba(32, 201, 90, .16);

    color: #36d96d;

}


/* EGRESOS */

.kpi-egresos .kpi-icon {

    background:
        rgba(255, 87, 87, .16);

    color: #ff7777;

}


/* SALDO FINAL */

.kpi-saldo .kpi-icon {

    background:
        rgba(233, 165, 26, .16);

    color: #f1b631;

}


/* =========================================================
   BORDE SUPERIOR
========================================================= */

.kpi-caja {

    border-top:
        2px solid #3b82f6;

}


.kpi-ingresos {

    border-top:
        2px solid #20c95a;

}


.kpi-egresos {

    border-top:
        2px solid #ff5757;

}


.kpi-saldo {

    border-top:
        2px solid #e9a51a;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .dashboard-kpis {

        grid-template-columns:
            repeat(2, 1fr);

    }

}


@media (max-width: 576px) {

    .dashboard-kpis {

        grid-template-columns: 1fr;

    }


    .dashboard-kpi-card {

        min-height: 105px;

    }

}

</style>