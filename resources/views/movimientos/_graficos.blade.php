<div class="mov-section">

    {{-- ENCABEZADO DE SECCIÓN --}}
    <div class="mov-section-header">
        <div class="d-flex align-items-center">
            <i class="cil-chart-line me-2"></i>
            <span class="mov-section-title">RESUMEN GRÁFICO</span>
            <span class="mov-section-sub ms-2">Estadísticas de los movimientos visibles</span>
        </div>
    </div>

    {{-- ZONA DE GRÁFICOS --}}
    <div class="mov-chart-area">
        <div class="row g-3">

            {{-- INGRESOS VS EGRESOS --}}
            <div class="col-lg-4">
                <div class="mov-chart-card">
                    <div class="mov-chart-title">
                        <i class="cil-chart-bar me-2"></i>
                        <span>Ingresos vs. Egresos</span>
                    </div>
                    <div class="mov-chart-box">
                        <canvas id="graficoIngresosEgresos"></canvas>
                    </div>
                </div>
            </div>

            {{-- EGRESOS POR CATEGORÍA --}}
            <div class="col-lg-4">
                <div class="mov-chart-card">
                    <div class="mov-chart-title">
                        <i class="cil-pie-chart me-2"></i>
                        <span>Egresos por categoría</span>
                    </div>
                    <div class="mov-chart-box">
                        <canvas id="graficoCategorias"></canvas>
                    </div>
                </div>
            </div>

            {{-- MOVIMIENTOS POR TIPO --}}
            <div class="col-lg-4">
                <div class="mov-chart-card">
                    <div class="mov-chart-title">
                        <i class="cil-chart-pie me-2"></i>
                        <span>Movimientos por tipo</span>
                    </div>
                    <div class="mov-chart-box">
                        <canvas id="graficoTipos"></canvas>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<style>
/* =========================================================
   CONTENEDOR DE SECCIÓN
========================================================= */
.mov-section {
    background: var(--card-bg, #1e293b) !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
    border-radius: 0 !important;
    padding: 20px !important;
    margin-bottom: 20px !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

/* ENCABEZADO DE SECCIÓN */
.mov-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--sb-border, rgba(255, 255, 255, 0.06));
    color: var(--text-main, #f8fafc);
}

.mov-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted, #94a3b8);
}

.mov-section-sub {
    font-size: 11px;
    font-weight: 400;
    color: var(--text-muted, #94a3b8);
    opacity: 0.8;
}

/* =========================================================
   TARJETAS DE GRÁFICOS
========================================================= */
.mov-chart-card {
    background: var(--input-bg, rgba(15, 23, 42, 0.4)) !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
    border-radius: 0 !important;
    padding: 14px;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.mov-chart-title {
    display: flex;
    align-items: center;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-main, #f8fafc);
    margin-bottom: 12px;
}

.mov-chart-title i {
    color: var(--accent-color, #38bdf8);
}

/* CONTENEDOR DEL CANVAS CON ALTURA CONTROLADA */
.mov-chart-box {
    position: relative;
    width: 100%;
    height: 240px;
    max-height: 240px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mov-chart-box canvas {
    max-width: 100% !important;
    max-height: 100% !important;
}
</style>