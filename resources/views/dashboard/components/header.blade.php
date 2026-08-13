<div class="dashboard-header">

    <div class="dashboard-header-left">

        <div class="dashboard-header-icon">
            <i class="fas fa-chart-line"></i>
        </div>

        <div>
            <h2 class="dashboard-title">
                Dashboard Financiero
            </h2>

            <p class="dashboard-subtitle">
                Sistema Integrado de Gestión Financiera de Villa
            </p>
        </div>

    </div>


    <div class="dashboard-header-right">

        <div class="dashboard-filter">

            <label for="anioDashboard">
                Año
            </label>

            <select
                id="anioDashboard"
                class="form-select dashboard-select">

                @foreach($anios as $anio)

                    <option
                        value="{{ $anio }}"
                        @selected($anio == $anioSeleccionado)>

                        {{ $anio }}

                    </option>

                @endforeach

            </select>

        </div>


        <div class="dashboard-filter">

            <label for="mesDashboard">
                Mes
            </label>

            <select
                id="mesDashboard"
                class="form-select dashboard-select">

                <option value="0">
                    Todo el año
                </option>

                @foreach($meses as $numero => $nombre)

                    <option value="{{ $numero }}">
                        {{ $nombre }}
                    </option>

                @endforeach

            </select>

        </div>


        <div class="dashboard-clock">

            <span class="dashboard-clock-label">
                <i class="far fa-clock me-1"></i>
                Hora
            </span>

            <div
                id="reloj"
                class="dashboard-clock-value">
            </div>

        </div>

    </div>

</div>


<style>

.dashboard-header {

    display: flex;

    justify-content: space-between;

    align-items: center;

    gap: 30px;

    padding: 24px 28px;

    background:
        linear-gradient(
            135deg,
            #172033 0%,
            #202b42 100%
        );

    border-radius: 16px;

    color: #ffffff;

    box-shadow:
        0 8px 24px rgba(0, 0, 0, .18);

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

    width: 52px;

    height: 52px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 14px;

    background: rgba(255, 255, 255, .10);

    color: #6ea8fe;

    font-size: 22px;

    flex-shrink: 0;

}


.dashboard-title {

    margin: 0;

    font-size: 26px;

    font-weight: 700;

    letter-spacing: -.3px;

}


.dashboard-subtitle {

    margin: 5px 0 0;

    color: rgba(255, 255, 255, .65);

    font-size: 13px;

}


/* ---------------------------------
   DERECHA
--------------------------------- */

.dashboard-header-right {

    display: flex;

    align-items: end;

    gap: 12px;

}


.dashboard-filter {

    min-width: 125px;

}


.dashboard-filter label {

    display: block;

    margin-bottom: 5px;

    font-size: 11px;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: .5px;

    color: rgba(255, 255, 255, .60);

}


.dashboard-select {

    min-height: 38px;

    border: 1px solid rgba(255, 255, 255, .15);

    border-radius: 9px;

    background-color: rgba(255, 255, 255, .08);

    color: #ffffff;

    font-size: 13px;

    font-weight: 600;

}


.dashboard-select:focus {

    border-color: #6ea8fe;

    box-shadow:
        0 0 0 3px rgba(110, 168, 254, .15);

}


.dashboard-select option {

    color: #212529;

    background: #ffffff;

}


/* ---------------------------------
   RELOJ
--------------------------------- */

.dashboard-clock {

    min-width: 105px;

}


.dashboard-clock-label {

    display: block;

    margin-bottom: 5px;

    font-size: 11px;

    font-weight: 600;

    text-transform: uppercase;

    letter-spacing: .5px;

    color: rgba(255, 255, 255, .60);

}


.dashboard-clock-value {

    min-height: 38px;

    padding: 8px 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 9px;

    background: rgba(255, 255, 255, .08);

    border: 1px solid rgba(255, 255, 255, .15);

    font-size: 13px;

    font-weight: 700;

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

        flex-wrap: wrap;

    }


}


@media (max-width: 576px) {

    .dashboard-header-left {

        align-items: flex-start;

    }


    .dashboard-title {

        font-size: 21px;

    }


    .dashboard-header-right {

        display: grid;

        grid-template-columns: 1fr 1fr;

    }


    .dashboard-clock {

        grid-column: span 2;

    }

}

</style>