<div class="dashboard-indicadores">

    {{-- LIQUIDEZ --}}
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
            <span class="indicador-label">Liquidez</span>
            <div id="indicadorLiquidez" class="indicador-value liquidez">
                100%
            </div>
            <span class="indicador-description">Caja disponible</span>
        </div>
    </div>

    {{-- RENTABILIDAD --}}
    <div class="indicador-card rentabilidad-card">
        <div class="indicador-icon rentabilidad">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 17l5-5 4 3 7-8"/>
                <path d="M15 7h5v5"/>
            </svg>
        </div>

        <div class="indicador-content">
            <span class="indicador-label">Rentabilidad</span>
            <div id="indicadorRentabilidad" class="indicador-value rentabilidad">
                0%
            </div>
            <span class="indicador-description">Ingresos - Egresos</span>
        </div>
    </div>

    {{-- ESTADO FINANCIERO --}}
    <div class="indicador-card estado-card">
        <div class="indicador-icon estado">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="8"/>
                <path d="M8.5 12l2.3 2.3 4.7-5"/>
            </svg>
        </div>

        <div class="indicador-content">
            <span class="indicador-label">Estado</span>
            <div id="estadoFinanciero" class="indicador-value estado">
                Excelente
            </div>
            <span class="indicador-description">Salud financiera</span>
        </div>
    </div>

</div>

<style>
/* =========================================================
   CONTENEDOR
========================================================= */
.dashboard-indicadores {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-top: 20px;
}

/* =========================================================
   TARJETA INDIVIDUAL
========================================================= */
.indicador-card {
    display: flex;
    align-items: center;
    gap: 14px;
    min-height: 82px;
    padding: 16px 18px;
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.indicador-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
}

/* =========================================================
   ÍCONOS
========================================================= */
.indicador-icon {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    flex-shrink: 0;
}

.indicador-icon svg {
    width: 22px;
    height: 22px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

/* Colores e identidades de los íconos */
.indicador-icon.liquidez {
    background: rgba(56, 189, 248, 0.1);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.15);
}

.indicador-icon.rentabilidad {
    background: rgba(52, 211, 153, 0.1);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.15);
}

.indicador-icon.estado {
    background: rgba(45, 212, 191, 0.1);
    color: #2dd4bf;
    border: 1px solid rgba(45, 212, 191, 0.15);
}

/* =========================================================
   CONTENIDO Y TEXTOS
========================================================= */
.indicador-content {
    min-width: 0;
}

.indicador-label {
    display: block;
    margin-bottom: 2px;
    color: #94a3b8;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

.indicador-value {
    font-size: 18px;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: -0.01em;
}

.indicador-value.liquidez    { color: #38bdf8; }
.indicador-value.rentabilidad { color: #34d399; }
.indicador-value.estado       { color: #2dd4bf; }

.indicador-description {
    display: block;
    margin-top: 2px;
    color: #64748b;
    font-size: 11px;
    font-weight: 500;
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 900px) {
    .dashboard-indicadores {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 576px) {
    .dashboard-indicadores {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}
</style>