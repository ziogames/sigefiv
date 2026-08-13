<div class="mov-section">

    {{-- =====================================================
         ENCABEZADO
         ===================================================== --}}

    <div class="mov-section-header">

        <i class="cil-chart-line me-1"></i>

        Resumen gráfico

        <span
            class="text-muted ms-2"
            style="font-size:11px; font-weight:400;">

            Estadísticas de los movimientos visibles

        </span>

    </div>


    {{-- =====================================================
         GRÁFICOS
         ===================================================== --}}

    <div class="mov-chart-area">

        <div class="row g-3">


            {{-- =================================================
                 INGRESOS VS EGRESOS
                 ================================================= --}}

            <div class="col-lg-4">

                <div class="mov-chart-card">

                    <div class="mov-chart-title">

                        <i class="cil-chart-bar me-1"></i>

                        Ingresos vs. Egresos

                    </div>

                    <div class="mov-chart-box">

                        <canvas
                            id="graficoIngresosEgresos">
                        </canvas>

                    </div>

                </div>

            </div>



            {{-- =================================================
                 EGRESOS POR CATEGORÍA
                 ================================================= --}}

            <div class="col-lg-4">

                <div class="mov-chart-card">

                    <div class="mov-chart-title">

                        <i class="cil-pie-chart me-1"></i>

                        Egresos por categoría

                    </div>

                    <div class="mov-chart-box">

                        <canvas
                            id="graficoCategorias">
                        </canvas>

                    </div>

                </div>

            </div>



            {{-- =================================================
                 MOVIMIENTOS POR TIPO
                 ================================================= --}}

            <div class="col-lg-4">

                <div class="mov-chart-card">

                    <div class="mov-chart-title">

                        <i class="cil-chart-pie me-1"></i>

                        Movimientos por tipo

                    </div>

                    <div class="mov-chart-box"
     style="position: relative; overflow: visible;">
    <canvas id="graficoTipos"></canvas>
</div>

                </div>

            </div>

        </div>

    </div>

</div>