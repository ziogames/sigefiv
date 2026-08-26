<div class="mov-summary">

    {{-- SALDO INICIAL --}}
    <div class="mov-kpi-card kpi-blue">
        <div class="mov-kpi-icon">
            <i class="cil-wallet"></i>
        </div>
        <div class="mov-kpi-content">
            <span class="mov-kpi-label">Saldo Inicial</span>
            <strong class="mov-kpi-value">S/ {{ number_format($saldoInicial, 2) }}</strong>
        </div>
    </div>

    {{-- TOTAL INGRESOS --}}
    <div class="mov-kpi-card kpi-green">
        <div class="mov-kpi-icon">
            <i class="cil-arrow-thick-top"></i>
        </div>
        <div class="mov-kpi-content">
            <span class="mov-kpi-label">Total Ingresos</span>
            <strong class="mov-kpi-value">S/ {{ number_format($totalIngresos, 2) }}</strong>
            <small class="mov-kpi-sub">
                {{ $cantidadIngresos }} {{ $cantidadIngresos == 1 ? 'movimiento' : 'movimientos' }}
            </small>
        </div>
    </div>

    {{-- DISPONIBLE --}}
    <div class="mov-kpi-card {{ $disponible < 0 ? 'kpi-negative' : 'kpi-cyan' }}">
        <div class="mov-kpi-icon">
            <i class="cil-chart-line"></i>
        </div>
        <div class="mov-kpi-content">
            <div class="d-flex align-items-center justify-content-between">
                <span class="mov-kpi-label">Disponible</span>
                @if($disponible < 0)
                    <span class="kpi-alert-dot" title="Saldo negativo"></span>
                @endif
            </div>
            <strong class="mov-kpi-value">S/ {{ number_format($disponible, 2) }}</strong>
        </div>
    </div>

    {{-- TOTAL EGRESOS --}}
    <div class="mov-kpi-card kpi-red">
        <div class="mov-kpi-icon">
            <i class="cil-arrow-thick-bottom"></i>
        </div>
        <div class="mov-kpi-content">
            <span class="mov-kpi-label">Total Egresos</span>
            <strong class="mov-kpi-value">S/ {{ number_format($totalEgresos, 2) }}</strong>
            <small class="mov-kpi-sub">
                {{ $cantidadEgresos }} {{ $cantidadEgresos == 1 ? 'movimiento' : 'movimientos' }}
            </small>
        </div>
    </div>

    {{-- SALDO EN CAJA --}}
    <div class="mov-kpi-card {{ $saldoCaja < 0 ? 'kpi-negative' : 'kpi-purple' }}">
        <div class="mov-kpi-icon">
            <i class="cil-money"></i>
        </div>
        <div class="mov-kpi-content">
            <div class="d-flex align-items-center justify-content-between">
                <span class="mov-kpi-label">Saldo en Caja</span>
                @if($saldoCaja < 0)
                    <span class="kpi-alert-dot" title="Déficit en caja"></span>
                @endif
            </div>
            <strong class="mov-kpi-value">S/ {{ number_format($saldoCaja, 2) }}</strong>
        </div>
    </div>

</div>

<style>
/* =========================================================
   CONTENEDOR GRID DE 5 COLUMNAS
========================================================= */
.mov-summary {
    display: grid !important;
    grid-template-columns: repeat(5, 1fr) !important;
    gap: 16px !important;
    width: 100% !important;
    margin-bottom: 20px !important;
    box-sizing: border-box !important;
}

/* =========================================================
   TARJETA INDUSTRIAL (BORDES RECTOS Y SOBRIOS)
========================================================= */
.mov-kpi-card {
    position: relative;
    display: flex !important;
    align-items: center !important;
    gap: 14px;
    padding: 16px;
    background: var(--card-bg, #1e293b) !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
    border-radius: 0 !important;
    box-sizing: border-box !important;
    transition: transform 0.2s ease, border-color 0.2s ease;
}

.mov-kpi-card:hover {
    transform: translateY(-2px);
}

/* ÍCONOS CONTRASTADOS */
.mov-kpi-icon {
    width: 42px;
    height: 42px;
    flex: 0 0 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border-radius: 0 !important;
}

.mov-kpi-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
    flex: 1;
}

.mov-kpi-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted, #94a3b8);
    line-height: 1.2;
}

.mov-kpi-value {
    font-size: 16px;
    font-weight: 800;
    color: var(--text-main, #f8fafc);
    margin-top: 4px;
    letter-spacing: -0.02em;
    line-height: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mov-kpi-sub {
    font-size: 10px;
    color: var(--text-muted, #94a3b8);
    margin-top: 4px;
    font-weight: 500;
}

/* =========================================================
   VARIANTES DE COLOR TÉCNICO
========================================================= */
.kpi-blue .mov-kpi-icon { background: rgba(56, 189, 248, 0.12); color: #38bdf8; }
.kpi-blue { border-left: 3px solid #38bdf8 !important; }

.kpi-green .mov-kpi-icon { background: rgba(52, 211, 153, 0.12); color: #34d399; }
.kpi-green { border-left: 3px solid #34d399 !important; }

.kpi-cyan .mov-kpi-icon { background: rgba(45, 212, 191, 0.12); color: #2dd4bf; }
.kpi-cyan { border-left: 3px solid #2dd4bf !important; }

.kpi-red .mov-kpi-icon { background: rgba(248, 113, 113, 0.12); color: #f87171; }
.kpi-red { border-left: 3px solid #f87171 !important; }

.kpi-purple .mov-kpi-icon { background: rgba(168, 85, 247, 0.12); color: #a855f7; }
.kpi-purple { border-left: 3px solid #a855f7 !important; }

/* ESTADO ALERTA NEGATIVO */
.kpi-negative {
    background: rgba(239, 68, 68, 0.06) !important;
    border: 1px solid rgba(239, 68, 68, 0.3) !important;
    border-left: 3px solid #ef4444 !important;
}

.kpi-negative .mov-kpi-icon {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.kpi-negative .mov-kpi-value {
    color: #f87171 !important;
}

.kpi-alert-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #ef4444;
    box-shadow: 0 0 6px #ef4444;
    animation: pulseAlert 1.5s infinite;
}

@keyframes pulseAlert {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 1200px) {
    .mov-summary {
        grid-template-columns: repeat(3, 1fr) !important;
    }
}

@media (max-width: 768px) {
    .mov-summary {
        grid-template-columns: repeat(2, 1fr) !important;
    }
}

@media (max-width: 480px) {
    .mov-summary {
        grid-template-columns: 1fr !important;
    }
}
</style>