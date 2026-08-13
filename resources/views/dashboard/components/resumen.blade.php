<div class="dashboard-resumen">

    {{-- =====================================================
         RESUMEN FINANCIERO
    ====================================================== --}}

    <div class="resumen-financiero-card">

        <div class="resumen-card-header">

            <div class="resumen-header-icon">

                <i class="fas fa-chart-pie"></i>

            </div>

            <div>

                <div class="resumen-kicker">
                    RESUMEN
                </div>

                <h5>
                    Resumen Financiero
                </h5>

            </div>

        </div>


        <div class="resumen-financiero-body">

            {{-- INGRESOS --}}

            <div class="resumen-line">

                <div class="resumen-line-info">

                    <span class="resumen-line-icon ingreso">

                        <i class="fas fa-arrow-trend-up"></i>

                    </span>

                    <span>
                        Promedio de ingresos
                    </span>

                </div>


                <strong
                    id="promedioIngresos"
                    class="resumen-valor ingreso">

                    {{ $configuracionGlobal->simbolo_moneda }}
                    {{ number_format($promedioIngresos, 2) }}

                </strong>

            </div>


            <div class="resumen-progress">

                <div
                    class="resumen-progress-bar ingreso"
                    style="width:100%">
                </div>

            </div>


            {{-- EGRESOS --}}

            <div class="resumen-line">

                <div class="resumen-line-info">

                    <span class="resumen-line-icon egreso">

                        <i class="fas fa-arrow-trend-down"></i>

                    </span>

                    <span>
                        Promedio de egresos
                    </span>

                </div>


                <strong
                    id="promedioEgresos"
                    class="resumen-valor egreso">

                    {{ $configuracionGlobal->simbolo_moneda }}
                    {{ number_format($promedioEgresos, 2) }}

                </strong>

            </div>


            <div class="resumen-progress">

                <div
                    class="resumen-progress-bar egreso"
                    style="width:100%">
                </div>

            </div>

        </div>

    </div>


    {{-- =====================================================
         MEJOR PERIODO
    ====================================================== --}}

    <div class="mejor-periodo-card">

        <div class="mejor-periodo-header">

            <div class="mejor-periodo-icon">

                <i class="fas fa-trophy"></i>

            </div>

            <div>

                <div class="resumen-kicker">
                    DESTACADO
                </div>

                <h5>
                    Mejor Período
                </h5>

            </div>

        </div>


        <div class="mejor-periodo-body">

            <div
                id="mejorMesResumen"
                class="mejor-periodo-mes">

                {{ $mejorMes }}

            </div>


            <div class="mejor-periodo-label">

                Mayor saldo registrado

            </div>

        </div>

    </div>

</div>


<style>

/* =========================================================
   CONTENEDOR
========================================================= */

.dashboard-resumen {

    display: grid;

    grid-template-columns:
        2fr 1fr;

    gap: 14px;

}


/* =========================================================
   TARJETAS
========================================================= */

.resumen-financiero-card,
.mejor-periodo-card {

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

    overflow: hidden;

    color: #ffffff;

}


/* =========================================================
   HEADER
========================================================= */

.resumen-card-header,
.mejor-periodo-header {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 13px 17px;

    background:
        rgba(10, 18, 31, .18);

    border-bottom:
        1px solid rgba(255,255,255,.08);

}


.resumen-header-icon,
.mejor-periodo-icon {

    width: 34px;

    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    font-size: 14px;

}


/* =========================================================
   ICONO RESUMEN
========================================================= */

.resumen-header-icon {

    background:
        rgba(67, 126, 221, .16);

    color: #75a9ff;

}


/* =========================================================
   ICONO MEJOR PERIODO
========================================================= */

.mejor-periodo-icon {

    background:
        rgba(233, 165, 26, .16);

    color: #f1b631;

}


/* =========================================================
   TEXTOS HEADER
========================================================= */

.resumen-kicker {

    margin-bottom: 2px;

    color: #77859a;

    font-size: 8px;

    font-weight: 800;

    letter-spacing: .8px;

}


.resumen-card-header h5,
.mejor-periodo-header h5 {

    margin: 0;

    color: #f2f5f9;

    font-size: 13px;

    font-weight: 750;

}


/* =========================================================
   RESUMEN FINANCIERO
========================================================= */

.resumen-financiero-body {

    padding: 17px;

}


.resumen-line {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

}


.resumen-line-info {

    display: flex;

    align-items: center;

    gap: 9px;

    color: #c3ccd9;

    font-size: 11px;

    font-weight: 600;

}


.resumen-line-icon {

    width: 28px;

    height: 28px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px;

    flex-shrink: 0;

}


.resumen-line-icon.ingreso {

    color: #24d363;

    background:
        rgba(32, 201, 90, .14);

}


.resumen-line-icon.egreso {

    color: #ff6666;

    background:
        rgba(255, 87, 87, .14);

}


.resumen-valor {

    font-size: 14px;

    font-weight: 800;

    white-space: nowrap;

}


.resumen-valor.ingreso {

    color: #20c95a;

}


.resumen-valor.egreso {

    color: #ff5757;

}


/* =========================================================
   BARRAS
========================================================= */

.resumen-progress {

    height: 4px;

    margin: 8px 0 15px;

    overflow: hidden;

    border-radius: 10px;

    background:
        rgba(255,255,255,.07);

}


.resumen-progress-bar {

    height: 100%;

    border-radius: 10px;

}


.resumen-progress-bar.ingreso {

    background: #20c95a;

}


.resumen-progress-bar.egreso {

    background: #ff5757;

}


/* =========================================================
   MEJOR PERIODO
========================================================= */

.mejor-periodo-body {

    min-height: 142px;

    display: flex;

    flex-direction: column;

    align-items: center;

    justify-content: center;

    padding: 18px;

    text-align: center;

}


.mejor-periodo-mes {

    margin-bottom: 6px;

    color: #f1b631;

    font-size: 23px;

    font-weight: 800;

}


.mejor-periodo-label {

    color: #8d9aab;

    font-size: 10px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .dashboard-resumen {

        grid-template-columns: 1fr;

    }

}

</style>