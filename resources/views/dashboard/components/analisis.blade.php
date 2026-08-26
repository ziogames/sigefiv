<div class="dashboard-analisis">

    <div class="analisis-graficos-fila">

        {{-- INGRESOS VS EGRESOS --}}
        <div class="analisis-card">
            <div class="analisis-card-header">
                <div class="analisis-title-group">
                    <div class="analisis-icon principal">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <span class="analisis-kicker">FINANZAS</span>
                        <h4>Ingresos vs Egresos</h4>
                    </div>
                </div>
                <div class="analisis-year">{{ $anioSeleccionado }}</div>
            </div>

            <div class="analisis-card-body">
                <div class="chart-container">
                    <canvas id="graficoPrincipal"></canvas>
                </div>
            </div>
        </div>

        {{-- GASTOS POR CATEGORÍA --}}
        <div class="analisis-card">
            <div class="analisis-card-header">
                <div class="analisis-title-group">
                    <div class="analisis-icon egreso">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div>
                        <span class="analisis-kicker">DISTRIBUCIÓN</span>
                        <h4>Gastos por Categoría</h4>
                    </div>
                </div>
            </div>

            <div class="analisis-card-body">
                <div class="chart-container">
                    <canvas id="graficoPie"></canvas>
                </div>
            </div>
        </div>

        {{-- EVOLUCIÓN DEL SALDO --}}
        <div class="analisis-card">
            <div class="analisis-card-header">
                <div class="analisis-title-group">
                    <div class="analisis-icon saldo">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <span class="analisis-kicker">DISPONIBILIDAD</span>
                        <h4>Evolución del Saldo</h4>
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

{{-- DATOS PARA EL FRONTEND --}}
<script>
window.dashboard = {
    grafico: @json($graficoAnual),
    simbolo: "{{ $configuracionGlobal->simbolo_moneda ?? 'S/' }}",
    anio: {{ $anioSeleccionado }}
};
</script>

<style>
/* =========================================================
   CONTENEDORES Y GRID
========================================================= */
.dashboard-analisis {
    width: 100%;
    margin-top: 20px;
}

.analisis-graficos-fila {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

/* =========================================================
   TARJETAS
========================================================= */
.analisis-card {
    min-width: 0;
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    color: #f8fafc;
    display: flex;
    flex-direction: column;
}

/* =========================================================
   CABECERA
========================================================= */
.analisis-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    height: 56px;
    padding: 0 16px;
    background: rgba(15, 23, 42, 0.4);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.analisis-title-group {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}

.analisis-icon {
    width: 34px;
    height: 34px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    border-radius: 8px;
    font-size: 13px;
}

.analisis-icon.principal {
    background: rgba(56, 189, 248, 0.1);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.15);
}

.analisis-icon.egreso {
    background: rgba(248, 113, 113, 0.1);
    color: #f87171;
    border: 1px solid rgba(248, 113, 113, 0.15);
}

.analisis-icon.saldo {
    background: rgba(251, 191, 36, 0.1);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.15);
}

.analisis-kicker {
    display: block;
    color: #94a3b8;
    font-size: 8px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.analisis-card-header h4 {
    margin: 0;
    color: #f8fafc;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

.analisis-year {
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.08);
    color: #94a3b8;
    font-size: 10px;
    font-weight: 600;
}

/* =========================================================
   CUERPO Y CANVAS
========================================================= */
.analisis-card-body {
    padding: 16px;
    flex: 1;
}

.chart-container {
    position: relative;
    width: 100%;
    height: 240px;
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 1200px) {
    .analisis-graficos-fila {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .analisis-graficos-fila {
        grid-template-columns: 1fr;
    }

    .chart-container {
        height: 220px;
    }
}
</style>