<div class="dashboard-analisis">

    <div class="analisis-graficos-fila">

        {{-- =================================================
             INGRESOS VS EGRESOS
        ================================================== --}}

        <div class="analisis-card">

            <div class="analisis-card-header">

                <div class="analisis-title-group">

                    <div class="analisis-icon principal">
                        <i class="fas fa-chart-line"></i>
                    </div>

                    <div>

                        <div class="analisis-kicker">
                            FINANZAS
                        </div>

                        <h4>
                            Ingresos vs Egresos
                        </h4>

                    </div>

                </div>


                <div class="analisis-year">

                    {{ $anioSeleccionado }}

                </div>

            </div>


            <div class="analisis-card-body">

                <div class="chart-container">

                    <canvas id="graficoPrincipal"></canvas>

                </div>

            </div>

        </div>


        {{-- =================================================
             GASTOS POR CATEGORÍA
        ================================================== --}}

        <div class="analisis-card">

            <div class="analisis-card-header">

                <div class="analisis-title-group">

                    <div class="analisis-icon egreso">
                        <i class="fas fa-chart-pie"></i>
                    </div>

                    <div>

                        <div class="analisis-kicker">
                            DISTRIBUCIÓN
                        </div>

                        <h4>
                            Gastos por Categoría
                        </h4>

                    </div>

                </div>

            </div>


            <div class="analisis-card-body">

                <div class="chart-container">

                    <canvas id="graficoPie"></canvas>

                </div>

            </div>

        </div>


        {{-- =================================================
             EVOLUCIÓN DEL SALDO
        ================================================== --}}

        <div class="analisis-card">

            <div class="analisis-card-header">

                <div class="analisis-title-group">

                    <div class="analisis-icon saldo">
                        <i class="fas fa-wallet"></i>
                    </div>

                    <div>

                        <div class="analisis-kicker">
                            DISPONIBILIDAD
                        </div>

                        <h4>
                            Evolución del Saldo
                        </h4>

                    </div>

                </div>

            </div>


            <div class="analisis-card-body">

                <div class="chart-container">

                    <canvas id="graficoSaldo"></canvas>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     DATOS PARA DASHBOARD.JS
========================================================= --}}

<script>

window.dashboard = {

    grafico: @json($graficoAnual),

    simbolo: "{{ $configuracionGlobal->simbolo_moneda }}",

    anio: {{ $anioSeleccionado }}

};

</script>


<style>

/* =========================================================
   CONTENEDOR
========================================================= */

.dashboard-analisis {

    width: 100%;

}


/* =========================================================
   TRES GRÁFICOS
========================================================= */

.analisis-graficos-fila {

    display: grid;

    grid-template-columns:
        repeat(3, minmax(0, 1fr));

    gap: 14px;

}


/* =========================================================
   TARJETA
========================================================= */

.analisis-card {

    min-width: 0;

    background:
        linear-gradient(
            145deg,
            #172033 0%,
            #1b273a 100%
        );

    border:
        1px solid rgba(92, 117, 150, .25);

    border-radius: 8px;

    overflow: hidden;

    box-shadow:
        0 5px 18px rgba(0, 0, 0, .16);

    color: #ffffff;

}


/* =========================================================
   CABECERA
========================================================= */

.analisis-card-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 10px;

    min-height: 58px;

    padding: 10px 13px;

    background:
        rgba(10, 18, 31, .18);

    border-bottom:
        1px solid rgba(255,255,255,.08);

}


.analisis-title-group {

    display: flex;

    align-items: center;

    gap: 9px;

    min-width: 0;

}


.analisis-icon {

    width: 32px;

    height: 32px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    border-radius: 8px;

    font-size: 13px;

}


.analisis-icon.principal {

    background:
        rgba(67,126,221,.16);

    color: #75a9ff;

}


.analisis-icon.egreso {

    background:
        rgba(255,87,87,.14);

    color: #ff7070;

}


.analisis-icon.saldo {

    background:
        rgba(233,165,26,.14);

    color: #f1b631;

}


.analisis-kicker {

    margin-bottom: 2px;

    color: #77859a;

    font-size: 7px;

    font-weight: 800;

    letter-spacing: .7px;

}


.analisis-card-header h4 {

    margin: 0;

    color: #f2f5f9;

    font-size: 12px;

    font-weight: 750;

    white-space: nowrap;

}


/* =========================================================
   AÑO
========================================================= */

.analisis-year {

    padding: 4px 7px;

    border-radius: 6px;

    background:
        rgba(255,255,255,.07);

    color: #aeb9c8;

    font-size: 9px;

    font-weight: 700;

    flex-shrink: 0;

}


/* =========================================================
   CUERPO
========================================================= */

.analisis-card-body {

    padding: 10px 12px 12px;

}


/* =========================================================
   GRÁFICO
========================================================= */

.chart-container {

    position: relative;

    width: 100%;

    height: 245px;

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .analisis-graficos-fila {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));

    }

}


@media (max-width: 700px) {

    .analisis-graficos-fila {

        grid-template-columns: 1fr;

    }


    .chart-container {

        height: 260px;

    }

}

</style>