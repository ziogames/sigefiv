<div class="dashboard-indicadores">

    {{-- =====================================================
         LIQUIDEZ
    ====================================================== --}}

    <div class="indicador-card liquidez-card">

        <div class="indicador-icon liquidez">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h16v13H4z"/>
                <path d="M7 6V4h10v2"/>
                <path d="M8 11h8"/>
                <path d="M8 15h5"/>
            </svg>

        </div>


        <div class="indicador-content">

            <div class="indicador-label">
                Liquidez
            </div>


            <div
                id="indicadorLiquidez"
                class="indicador-value">

                100%

            </div>


            <div class="indicador-description">

                Caja disponible

            </div>

        </div>

    </div>


    {{-- =====================================================
         RENTABILIDAD
    ====================================================== --}}

    <div class="indicador-card rentabilidad-card">

        <div class="indicador-icon rentabilidad">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 17l5-5 4 3 7-8"/>
                <path d="M15 7h5v5"/>
            </svg>

        </div>


        <div class="indicador-content">

            <div class="indicador-label">
                Rentabilidad
            </div>


            <div
                id="indicadorRentabilidad"
                class="indicador-value">

                0%

            </div>


            <div class="indicador-description">

                Ingresos - Egresos

            </div>

        </div>

    </div>


    {{-- =====================================================
         ESTADO FINANCIERO
    ====================================================== --}}

    <div class="indicador-card estado-card">

        <div class="indicador-icon estado">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="8"/>
                <path d="M8.5 12l2.3 2.3 4.7-5"/>
            </svg>

        </div>


        <div class="indicador-content">

            <div class="indicador-label">
                Estado
            </div>


            <div
                id="estadoFinanciero"
                class="indicador-value">

                Excelente

            </div>


            <div class="indicador-description">

                Salud financiera

            </div>

        </div>

    </div>

</div>


<style>

/* =========================================================
   CONTENEDOR
========================================================= */

.dashboard-indicadores {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 14px;

}


/* =========================================================
   TARJETA
========================================================= */

.indicador-card {

    display: flex;

    align-items: center;

    gap: 13px;

    min-height: 86px;

    padding: 14px 16px;

    background:
        linear-gradient(
            145deg,
            #172033 0%,
            #1b273a 100%
        );

    border:
        1px solid rgba(92, 117, 150, .25);

    border-radius: 8px;

    box-shadow:
        0 5px 18px rgba(0, 0, 0, .14);

    transition:
        transform .2s ease,
        border-color .2s ease;

}


/* =========================================================
   HOVER
========================================================= */

.indicador-card:hover {

    transform: translateY(-2px);

    border-color:
        rgba(120, 150, 190, .38);

}


/* =========================================================
   ICONOS
========================================================= */

.indicador-icon {

    width: 40px;

    height: 40px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    flex-shrink: 0;

}


/* SVG */

.indicador-icon svg {

    width: 23px;

    height: 23px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;

}


/* =========================================================
   LIQUIDEZ
========================================================= */

.indicador-icon.liquidez {

    color: #75a9ff;

    background:
        rgba(67, 126, 221, .16);

}


.liquidez-card {

    border-top:
        2px solid #3b82f6;

}


/* =========================================================
   RENTABILIDAD
========================================================= */

.indicador-icon.rentabilidad {

    color: #36d96d;

    background:
        rgba(32, 201, 90, .14);

}


.rentabilidad-card {

    border-top:
        2px solid #20c95a;

}


/* =========================================================
   ESTADO
========================================================= */

.indicador-icon.estado {

    color: #45d6e8;

    background:
        rgba(13, 202, 240, .13);

}


.estado-card {

    border-top:
        2px solid #0dcaf0;

}


/* =========================================================
   CONTENIDO
========================================================= */

.indicador-content {

    min-width: 0;

}


.indicador-label {

    margin-bottom: 3px;

    color: #77859a;

    font-size: 9px;

    font-weight: 800;

    text-transform: uppercase;

    letter-spacing: .6px;

}


.indicador-value {

    color: #f2f5f9;

    font-size: 18px;

    line-height: 1.15;

    font-weight: 800;

}


.indicador-description {

    margin-top: 3px;

    color: #8d9aab;

    font-size: 9px;

}


/* =========================================================
   COLORES DE LOS VALORES
========================================================= */

.liquidez-card .indicador-value {

    color: #75a9ff;

}


.rentabilidad-card .indicador-value {

    color: #36d96d;

}


.estado-card .indicador-value {

    color: #45d6e8;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 900px) {

    .dashboard-indicadores {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

}


@media (max-width: 576px) {

    .dashboard-indicadores {

        grid-template-columns: 1fr;

    }

}

</style>