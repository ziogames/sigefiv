<div class="mov-topbar">

    {{-- TÍTULO E INFORMACIÓN --}}
    <div class="mov-title-area">
        <h1 class="mov-title">Movimientos</h1>
        <p class="mov-subtitle">Registro y administración de ingresos y egresos</p>
    </div>

    {{-- ACCIONES Y PERÍODO ACTIVO --}}
    <div class="mov-top-actions">

        {{-- PERÍODO ACTIVO --}}
        <div class="mov-period-active">
            <div class="mov-period-icon">
                <i class="cil-calendar"></i>
            </div>

            <div class="mov-period-info">
                <span class="mov-period-label">Período activo</span>
                <strong class="mov-period-value">
                    @if(isset($periodo) && $periodo)
                        {{ $periodo->nombre }} {{ $periodo->anio }}
                    @else
                        Sin período
                    @endif
                </strong>
            </div>

            @if(isset($periodo) && $periodo)
                <span class="mov-status-badge {{ $periodo->estado === 'Abierto' ? 'abierto' : 'cerrado' }}">
                    <span class="status-dot"></span>
                    {{ $periodo->estado === 'Abierto' ? 'Abierto' : 'Cerrado' }}
                </span>
            @endif
        </div>

        {{-- BOTÓN CREAR --}}
        @can('movimientos.create')
            <a href="{{ route('movimientos.create') }}" class="btn-mov-primary">
                <i class="cil-plus me-1"></i>
                <span>Nuevo Movimiento</span>
            </a>
        @endcan

    </div>

</div>

<style>
/* =========================================================
   CONTENEDOR SUPERIOR
========================================================= */
.mov-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--sb-border, rgba(255, 255, 255, 0.08));
    margin-bottom: 20px;
}

/* =========================================================
   TEXTOS Y TITULO
========================================================= */
.mov-title-area {
    min-width: 0;
}

.mov-title {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
    color: var(--text-main, #f8fafc);
    letter-spacing: -0.02em;
    line-height: 1.2;
}

.mov-subtitle {
    margin: 4px 0 0;
    font-size: 13px;
    color: var(--text-muted, #94a3b8);
}

/* =========================================================
   ACCIONES Y DERECHA
========================================================= */
.mov-top-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* PERIODO ACTIVO */
.mov-period-active {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    background: var(--card-bg, #1e293b);
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
    border-radius: 0; /* Bordes rectos */
}

.mov-period-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--accent-color, #38bdf8);
    font-size: 16px;
}

.mov-period-info {
    display: flex;
    flex-direction: column;
}

.mov-period-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted, #94a3b8);
    line-height: 1;
}

.mov-period-value {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-main, #f8fafc);
    margin-top: 2px;
    line-height: 1;
}

/* STATUS BADGE */
.mov-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    border-radius: 0;
}

.mov-status-badge .status-dot {
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background-color: currentColor;
}

.mov-status-badge.abierto {
    background: rgba(52, 211, 153, 0.12);
    color: #34d399;
    border: 1px solid rgba(52, 211, 153, 0.25);
}

.mov-status-badge.cerrado {
    background: rgba(248, 113, 113, 0.12);
    color: #f87171;
    border: 1px solid rgba(248, 113, 113, 0.25);
}

/* =========================================================
   BOTÓN NUEVO MOVIMIENTO
========================================================= */
.btn-mov-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 42px;
    padding: 0 18px;
    background-color: #f97316; /* Color naranja de acción principal */
    color: #ffffff !important;
    font-size: 13px;
    font-weight: 700;
    text-decoration: none;
    border: 1px solid #ea580c;
    border-radius: 0; /* Bordes rectos industriales */
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 10px rgba(249, 115, 22, 0.2);
}

.btn-mov-primary:hover {
    background-color: #ea580c;
    border-color: #c2410c;
    box-shadow: 0 4px 15px rgba(249, 115, 22, 0.35);
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 768px) {
    .mov-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .mov-top-actions {
        width: 100%;
        justify-content: space-between;
    }
}
</style>