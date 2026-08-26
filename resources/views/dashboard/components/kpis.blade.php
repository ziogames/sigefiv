<div class="dashboard-kpis">

    {{-- SALDO EN CAJA --}}
    <div class="dashboard-kpi-card kpi-caja">
        <div class="kpi-content">
            <span class="kpi-label">Saldo en Caja</span>
            <div id="saldoCaja" class="kpi-value">
                {{ $configuracionGlobal->simbolo_moneda }} {{ number_format($saldoCaja, 2) }}
            </div>
            <div id="saldoTendencia" class="kpi-status kpi-status-neutral">
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

    {{-- INGRESOS --}}
    <div class="dashboard-kpi-card kpi-ingresos">
        <div class="kpi-content">
            <span class="kpi-label">Ingresos (Año)</span>
            <div id="ingresos" class="kpi-value">
                {{ $configuracionGlobal->simbolo_moneda }} {{ number_format($ingresos, 2) }}
            </div>
            <div id="ingresosVariacion" class="kpi-status kpi-status-success">
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

    {{-- EGRESOS --}}
    <div class="dashboard-kpi-card kpi-egresos">
        <div class="kpi-content">
            <span class="kpi-label">Egresos (Año)</span>
            <div id="egresos" class="kpi-value">
                {{ $configuracionGlobal->simbolo_moneda }} {{ number_format($egresos, 2) }}
            </div>
            <div id="egresosVariacion" class="kpi-status kpi-status-danger">
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

    {{-- FLUJO NETO / SALDO FINAL --}}
    <div class="dashboard-kpi-card kpi-saldo">
        <div class="kpi-content">
            <span class="kpi-label">Saldo Final (Año)</span>
            <div id="saldoFinal" class="kpi-value">
                {{ $configuracionGlobal->simbolo_moneda }} {{ number_format($saldoCaja, 2) }}
            </div>
            <div class="kpi-status kpi-status-warning">
                <span class="kpi-status-dot"></span>
                Saldo acumulado
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
   CONTENEDOR KPIs
========================================================= */
.dashboard-kpis {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-top: 20px;
}

/* =========================================================
   TARJETA INDIVIDUAL
========================================================= */
.dashboard-kpi-card {
    --card-bg: #1e293b;
    --card-border: rgba(255, 255, 255, 0.08);

    position: relative;
    min-height: 105px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.dashboard-kpi-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
}

/* =========================================================
   CONTENIDO TEXTO Y MÉTRICAS
========================================================= */
.kpi-content {
    min-width: 0;
}

.kpi-label {
    display: block;
    margin-bottom: 6px;
    color: #94a3b8;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.kpi-value {
    color: #f8fafc;
    font-size: 24px;
    line-height: 1.1;
    font-weight: 700;
    letter-spacing: -0.02em;
    white-space: nowrap;
}

/* Colores específicos de valores si se prefiere destacar la cifra */
.kpi-ingresos .kpi-value { color: #34d399; }
.kpi-egresos .kpi-value { color: #f87171; }

/* =========================================================
   ESTADOS E INDICADORES
========================================================= */
.kpi-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    font-size: 11px;
    font-weight: 500;
}

.kpi-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: currentColor;
}

.kpi-status-neutral { color: #38bdf8; }
.kpi-status-success { color: #34d399; }
.kpi-status-danger  { color: #f87171; }
.kpi-status-warning { color: #fbbf24; }

/* =========================================================
   ÍCONOS Y SVG
========================================================= */
.kpi-icon {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    flex-shrink: 0;
}

.kpi-icon svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Variaciones de color en íconos */
.kpi-caja .kpi-icon {
    background: rgba(56, 189, 248, 0.1);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.15);
}

.kpi-ingresos .kpi-icon {
    background: rgba(52, 211, 153, 0.1);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.15);
}

.kpi-egresos .kpi-icon {
    background: rgba(248, 113, 113, 0.1);
    color: #f87171;
    border: 1px solid rgba(248, 113, 113, 0.15);
}

.kpi-saldo .kpi-icon {
    background: rgba(251, 191, 36, 0.1);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.15);
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 1100px) {
    .dashboard-kpis {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .dashboard-kpis {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .dashboard-kpi-card {
        padding: 16px;
    }

    .kpi-value {
        font-size: 20px;
    }
}
</style>