<div class="dashboard-header">

    <div class="dashboard-header-left">
        <div class="dashboard-header-icon">
            <i class="fas fa-chart-line"></i>
        </div>

        <div class="dashboard-header-text">
            <h2 class="dashboard-title">Dashboard Financiero</h2>
            <p class="dashboard-subtitle">Sistema Integrado de Gestión Financiera de Villa</p>
        </div>
    </div>

    <div class="dashboard-header-right">

        <div class="dashboard-filter">
            <label for="anioDashboard">
                <i class="far fa-calendar-alt me-1"></i> Año
            </label>
            <select id="anioDashboard" class="form-select dashboard-select">
                @foreach($anios as $anio)
                    <option value="{{ $anio }}" @selected($anio == $anioSeleccionado)>
                        {{ $anio }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="dashboard-filter">
            <label for="mesDashboard">
                <i class="far fa-calendar-minus me-1"></i> Mes
            </label>
            <select id="mesDashboard" class="form-select dashboard-select">
                <option value="0">Todo el año</option>
                @foreach($meses as $numero => $nombre)
                    <option value="{{ $numero }}">
                        {{ $nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="dashboard-clock">
            <span class="dashboard-clock-label">
                <i class="far fa-clock me-1"></i> Hora
            </span>
            <div id="reloj" class="dashboard-clock-value">
                --:--:--
            </div>
        </div>

    </div>

</div>

<style>
/* ---------------------------------
   CONTENEDOR PRINCIPAL
--------------------------------- */
.dashboard-header {
    --bg-primary: #1e293b;
    --bg-secondary: #0f172a;
    --border-color: rgba(255, 255, 255, 0.08);
    --text-primary: #f8fafc;
    --text-muted: #94a3b8;
    --accent-color: #38bdf8;
    --accent-glow: rgba(56, 189, 248, 0.15);

    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 24px;
    padding: 20px 24px;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    color: var(--text-primary);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
}

/* ---------------------------------
   IZQUIERDA
--------------------------------- */
.dashboard-header-left {
    display: flex;
    align-items: center;
    gap: 16px;
    min-width: 0;
}

.dashboard-header-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(56, 189, 248, 0.1);
    color: var(--accent-color);
    font-size: 20px;
    border: 1px solid rgba(56, 189, 248, 0.2);
    flex-shrink: 0;
}

.dashboard-title {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.02em;
    color: var(--text-primary);
    line-height: 1.2;
}

.dashboard-subtitle {
    margin: 4px 0 0;
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 400;
}

/* ---------------------------------
   DERECHA (FILTROS Y RELOJ)
--------------------------------- */
.dashboard-header-right {
    display: flex;
    align-items: flex-end;
    gap: 12px;
}

.dashboard-filter {
    min-width: 130px;
}

.dashboard-filter label,
.dashboard-clock-label {
    display: flex;
    align-items: center;
    margin-bottom: 6px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--text-muted);
}

/* ---------------------------------
   SELECTS PERSONALIZADOS
--------------------------------- */
.dashboard-select {
    width: 100%;
    height: 38px;
    padding: 0 32px 0 12px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    background-color: var(--bg-secondary);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%3c94a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
    color: var(--text-primary);
    font-size: 13px;
    font-weight: 600;
    appearance: none;
    -webkit-appearance: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dashboard-select:hover {
    border-color: rgba(255, 255, 255, 0.2);
    background-color: rgba(15, 23, 42, 0.8);
}

.dashboard-select:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px var(--accent-glow);
}

.dashboard-select option {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}

/* ---------------------------------
   RELOJ
--------------------------------- */
.dashboard-clock {
    min-width: 110px;
}

.dashboard-clock-value {
    height: 38px;
    padding: 0 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    color: var(--accent-color);
    font-size: 13px;
    font-weight: 700;
    font-family: monospace;
    letter-spacing: 0.05em;
}

/* ---------------------------------
   RESPONSIVE
--------------------------------- */
@media (max-width: 992px) {
    .dashboard-header {
        flex-direction: column;
        align-items: stretch;
    }

    .dashboard-header-right {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 576px) {
    .dashboard-header {
        padding: 16px;
    }

    .dashboard-title {
        font-size: 18px;
    }

    .dashboard-header-right {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .dashboard-clock {
        grid-column: span 2;
    }
}
</style>