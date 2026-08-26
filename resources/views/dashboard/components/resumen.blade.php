@php
    // Cálculo proporcional de la barra para evitar que siempre esté en 100%
    $maxValor = max($promedioIngresos, $promedioEgresos, 1);
    $porcentajeIngresos = min(100, round(($promedioIngresos / $maxValor) * 100));
    $porcentajeEgresos = min(100, round(($promedioEgresos / $maxValor) * 100));
@endphp

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
                <span class="resumen-kicker">PROMEDIOS</span>
                <h5>Resumen Financiero</h5>
            </div>
        </div>

        <div class="resumen-financiero-body">

            {{-- INGRESOS --}}
            <div class="resumen-line">
                <div class="resumen-line-info">
                    <span class="resumen-line-icon ingreso">
                        <i class="fas fa-arrow-trend-up"></i>
                    </span>
                    <span>Promedio de ingresos</span>
                </div>
                <strong id="promedioIngresos" class="resumen-valor ingreso">
                    {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($promedioIngresos, 2) }}
                </strong>
            </div>

            <div class="resumen-progress">
                <div class="resumen-progress-bar ingreso" style="width: {{ $porcentajeIngresos }}%"></div>
            </div>

            {{-- EGRESOS --}}
            <div class="resumen-line">
                <div class="resumen-line-info">
                    <span class="resumen-line-icon egreso">
                        <i class="fas fa-arrow-trend-down"></i>
                    </span>
                    <span>Promedio de egresos</span>
                </div>
                <strong id="promedioEgresos" class="resumen-valor egreso">
                    {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($promedioEgresos, 2) }}
                </strong>
            </div>

            <div class="resumen-progress">
                <div class="resumen-progress-bar egreso" style="width: {{ $porcentajeEgresos }}%"></div>
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
                <span class="resumen-kicker">RENDIMIENTO</span>
                <h5>Mejor Período</h5>
            </div>
        </div>

        <div class="mejor-periodo-body">
            <div class="trophy-glow">
                <i class="fas fa-award"></i>
            </div>

            <div id="mejorMesResumen" class="mejor-periodo-mes">
                {{ $mejorMes ?? 'N/A' }}
            </div>

            <div class="mejor-periodo-label">
                Mayor superávit neto registrado
            </div>
        </div>

    </div>

</div>


{{-- =========================================================
     ESTILOS CSS
========================================================== --}}
<style>
/* =========================================================
   CONTENEDOR
========================================================= */
.dashboard-resumen {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-top: 20px;
}

/* =========================================================
   TARJETAS
========================================================= */
.resumen-financiero-card,
.mejor-periodo-card {
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    color: #f8fafc;
    display: flex;
    flex-direction: column;
}

/* =========================================================
   HEADER DE TARJETAS
========================================================= */
.resumen-card-header,
.mejor-periodo-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 18px;
    background: rgba(15, 23, 42, 0.4);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.resumen-header-icon,
.mejor-periodo-icon {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 14px;
    flex-shrink: 0;
}

.resumen-header-icon {
    background: rgba(56, 189, 248, 0.1);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.15);
}

.mejor-periodo-icon {
    background: rgba(251, 191, 36, 0.1);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.15);
}

.resumen-kicker {
    display: block;
    color: #94a3b8;
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-bottom: 2px;
}

.resumen-card-header h5,
.mejor-periodo-header h5 {
    margin: 0;
    color: #f8fafc;
    font-size: 14px;
    font-weight: 600;
}

/* =========================================================
   RESUMEN FINANCIERO BODY
========================================================= */
.resumen-financiero-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex: 1;
}

.resumen-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
}

.resumen-line-info {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #cbd5e1;
    font-size: 12px;
    font-weight: 500;
}

.resumen-line-icon {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 12px;
    flex-shrink: 0;
}

.resumen-line-icon.ingreso {
    color: #34d399;
    background: rgba(52, 211, 153, 0.1);
}

.resumen-line-icon.egreso {
    color: #f87171;
    background: rgba(248, 113, 113, 0.1);
}

.resumen-valor {
    font-size: 14px;
    font-weight: 700;
    white-space: nowrap;
}

.resumen-valor.ingreso { color: #34d399; }
.resumen-valor.egreso  { color: #f87171; }

.resumen-progress {
    height: 6px;
    margin: 8px 0 16px;
    overflow: hidden;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.05);
}

.resumen-progress:last-child {
    margin-bottom: 0;
}

.resumen-progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.4s ease;
}

.resumen-progress-bar.ingreso { background: #34d399; }
.resumen-progress-bar.egreso  { background: #f87171; }

/* =========================================================
   MEJOR PERIODO BODY
========================================================= */
.mejor-periodo-body {
    padding: 18px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    flex: 1;
    position: relative;
}

.trophy-glow {
    font-size: 28px;
    color: #fbbf24;
    margin-bottom: 6px;
    opacity: 0.9;
}

.mejor-periodo-mes {
    color: #f8fafc;
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.mejor-periodo-label {
    margin-top: 4px;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 500;
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 768px) {
    .dashboard-resumen {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
</style>