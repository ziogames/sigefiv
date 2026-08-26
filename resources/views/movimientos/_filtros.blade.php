<div class="mov-filters">

    <div class="mov-filter-header">
        <i class="cil-filter me-2"></i>
        <span>BUSCAR Y FILTRAR MOVIMIENTOS</span>
    </div>

    <form method="GET" action="{{ route('movimientos.index') }}" class="mov-filter-form" id="movimientosFiltros">

        {{-- FILA 1: BUSCADOR PRINCIPAL (ANCHO COMPLETO) --}}
        <div class="mov-form-row full-width">
            <div class="mov-field">
                <label for="inputBuscar">Buscar por</label>
                <div class="mov-input-box">
                    <input type="text" id="inputBuscar" name="buscar" value="{{ $buscar ?? '' }}" placeholder="Número, concepto, persona o referencia...">
                    <i class="cil-search"></i>
                </div>
            </div>
        </div>

        {{-- FILA 2: FILTROS DE SELECCIÓN --}}
        <div class="mov-form-row grid-4">

            <div class="mov-field">
                <label for="filtroAnio">Año</label>
                <select name="anio" id="filtroAnio" class="mov-select">
                    <option value="">Todos</option>
                    @foreach($periodos->pluck('anio')->unique()->sort() as $anioItem)
                        <option value="{{ $anioItem }}" @selected((string) request('anio') === (string) $anioItem)>
                            {{ $anioItem }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mov-field">
                <label for="filtroPeriodo">Período</label>
                <select name="periodo_id" id="filtroPeriodo" class="mov-select">
                    <option value="" data-anio="todos">Todos los períodos</option>
                    @foreach($periodos as $item)
                        <option value="{{ $item->id }}" data-anio="{{ $item->anio }}" data-mes="{{ $item->mes }}" @selected((string) $periodo_id === (string) $item->id)>
                            {{ $item->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mov-field">
                <label for="filtroTipo">Tipo de movimiento</label>
                <select name="tipo" id="filtroTipo" class="mov-select">
                    <option value="">Todos los tipos</option>
                    <option value="Ingreso" @selected($tipo === 'Ingreso')>Ingresos</option>
                    <option value="Egreso" @selected($tipo === 'Egreso')>Egresos</option>
                </select>
            </div>

            <div class="mov-field">
                <label for="filtroCategoria">Categoría</label>
                <select name="categoria_id" id="filtroCategoria" class="mov-select">
                    <option value="">Todas las categorías</option>
                    @foreach($categoriasIngreso as $categoria)
                        <option value="{{ $categoria->id }}" data-tipo="Ingreso" @selected((string) $categoria_id === (string) $categoria->id)>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                    @foreach($categoriasEgreso as $categoria)
                        <option value="{{ $categoria->id }}" data-tipo="Egreso" @selected((string) $categoria_id === (string) $categoria->id)>
                            {{ $categoria->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- FILA 3: FECHAS Y BOTONES ALINEADOS A LA DERECHA --}}
        <div class="mov-form-row grid-dates-actions">

            <div class="mov-field">
                <label for="filtroDesde">Desde</label>
                <input type="date" name="desde" id="filtroDesde" class="mov-input-date" value="{{ $desde ?? '' }}">
            </div>

            <div class="mov-field">
                <label for="filtroHasta">Hasta</label>
                <input type="date" name="hasta" id="filtroHasta" class="mov-input-date" value="{{ $hasta ?? '' }}">
            </div>

            <div class="mov-field actions-field">
                <label class="d-none d-md-block">&nbsp;</label>
                <div class="btn-group-actions">
                    <button type="submit" class="btn-filter-submit">
                        <i class="cil-search me-1"></i> Buscar
                    </button>
                    <a href="{{ route('movimientos.index') }}" class="btn-filter-reset">
                        <i class="cil-x-circle me-1"></i> Limpiar
                    </a>
                </div>
            </div>

        </div>

    </form>

</div>

<style>
/* =========================================================
   ESTILOS DE CONTENEDOR (FORCE 100% WIDTH)
========================================================= */
.mov-filters {
    background: var(--card-bg, #1e293b) !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08)) !important;
    border-radius: 0 !important;
    padding: 20px !important;
    margin-bottom: 20px !important;
    color: var(--text-main, #f8fafc) !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.mov-filter-header {
    display: flex;
    align-items: center;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted, #94a3b8);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--sb-border, rgba(255, 255, 255, 0.06));
}

.mov-filter-form {
    width: 100% !important;
    display: block !important;
}

/* =========================================================
   FILAS DE CAMPOS Y ESTRUCTURA
========================================================= */
.mov-form-row {
    width: 100% !important;
    margin-bottom: 14px;
    box-sizing: border-box !important;
}

.mov-form-row.full-width {
    display: block !important;
}

.mov-form-row.grid-4 {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 16px !important;
}

.mov-form-row.grid-dates-actions {
    display: grid !important;
    grid-template-columns: 1fr 1fr 2fr !important;
    gap: 16px !important;
    align-items: flex-end !important;
    margin-bottom: 0 !important;
}

/* =========================================================
   CAMPOS INDIVIDUALES
========================================================= */
.mov-field {
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.mov-field label {
    font-size: 10px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.05em !important;
    color: var(--text-muted, #94a3b8) !important;
    margin-bottom: 6px !important;
}

/* =========================================================
   INPUTS Y SELECTS DE TAMAÑO COMPLETO
========================================================= */
.mov-input-box {
    position: relative !important;
    width: 100% !important;
}

.mov-input-box input,
.mov-select,
.mov-input-date {
    width: 100% !important;
    height: 38px !important;
    padding: 0 12px !important;
    background-color: var(--input-bg, rgba(15, 23, 42, 0.6)) !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.12)) !important;
    border-radius: 0 !important;
    color: var(--text-main, #f8fafc) !important;
    font-size: 12px !important;
    outline: none !important;
    box-sizing: border-box !important;
}

.mov-input-box input {
    padding-right: 32px !important;
}

.mov-input-box i {
    position: absolute !important;
    right: 12px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: var(--text-muted, #94a3b8) !important;
    font-size: 14px !important;
    pointer-events: none !important;
}

.mov-select option {
    background-color: var(--card-bg, #1e293b) !important;
    color: var(--text-main, #f8fafc) !important;
}

/* =========================================================
   BOTONES
========================================================= */
.actions-field {
    display: flex !important;
    align-items: flex-end !important;
}

.btn-group-actions {
    display: flex !important;
    gap: 8px !important;
    justify-content: flex-end !important;
    width: 100% !important;
}

.btn-filter-submit {
    height: 38px !important;
    padding: 0 20px !important;
    background-color: #38bdf8 !important;
    color: #0f172a !important;
    font-size: 12px !important;
    font-weight: 700 !important;
    border: 1px solid #0284c7 !important;
    border-radius: 0 !important;
    cursor: pointer !important;
    white-space: nowrap !important;
}

.btn-filter-reset {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    height: 38px !important;
    padding: 0 16px !important;
    background-color: transparent !important;
    color: var(--text-muted, #94a3b8) !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.12)) !important;
    border-radius: 0 !important;
    white-space: nowrap !important;
}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width: 992px) {
    .mov-form-row.grid-4 {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .mov-form-row.grid-dates-actions {
        grid-template-columns: 1fr 1fr !important;
    }
    
    .actions-field {
        grid-column: span 2 !important;
    }
}

@media (max-width: 576px) {
    .mov-form-row.grid-4,
    .mov-form-row.grid-dates-actions {
        grid-template-columns: 1fr !important;
    }
    
    .actions-field {
        grid-column: span 1 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filtroAnio = document.getElementById('filtroAnio');
    const filtroPeriodo = document.getElementById('filtroPeriodo');
    const filtroTipo = document.getElementById('filtroTipo');
    const filtroCategoria = document.getElementById('filtroCategoria');
    const filtroDesde = document.getElementById('filtroDesde');
    const filtroHasta = document.getElementById('filtroHasta');

    function formatearFecha(anio, mes, dia) {
        return String(anio) + '-' + String(mes).padStart(2, '0') + '-' + String(dia).padStart(2, '0');
    }

    function ultimoDiaMes(anio, mes) {
        return new Date(anio, mes, 0).getDate();
    }

    function establecerFechasAnio(anio) {
        if (!anio) return;
        filtroDesde.value = formatearFecha(anio, 1, 1);
        filtroHasta.value = formatearFecha(anio, 12, 31);
    }

    function establecerFechasPeriodo(option) {
        if (!option || !option.value) {
            filtroDesde.value = '';
            filtroHasta.value = '';
            return;
        }
        const anio = option.dataset.anio;
        const mes = parseInt(option.dataset.mes);
        if (!anio || !mes) {
            filtroDesde.value = '';
            filtroHasta.value = '';
            return;
        }
        const ultimoDia = ultimoDiaMes(parseInt(anio), mes);
        filtroDesde.value = formatearFecha(anio, mes, 1);
        filtroHasta.value = formatearFecha(anio, mes, ultimoDia);
    }

    function actualizarPeriodos() {
        const anio = filtroAnio.value;
        const periodoActual = filtroPeriodo.value;

        Array.from(filtroPeriodo.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            const anioPeriodo = option.dataset.anio;
            option.hidden = anio && anioPeriodo !== anio;
        });

        const optionSeleccionada = filtroPeriodo.options[filtroPeriodo.selectedIndex];
        if (anio && optionSeleccionada && optionSeleccionada.value && optionSeleccionada.dataset.anio !== anio) {
            filtroPeriodo.value = '';
        }

        if (anio && !periodoActual) {
            filtroPeriodo.value = '';
        }
    }

    function actualizarCategorias() {
        const tipo = filtroTipo.value;

        Array.from(filtroCategoria.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            const tipoCategoria = option.dataset.tipo;
            if (tipo && tipoCategoria) {
                option.hidden = tipoCategoria !== tipo;
            } else {
                option.hidden = false;
            }
        });

        const seleccionada = filtroCategoria.options[filtroCategoria.selectedIndex];
        if (tipo && seleccionada && seleccionada.value && seleccionada.dataset.tipo && seleccionada.dataset.tipo !== tipo) {
            filtroCategoria.value = '';
        }
    }

    filtroAnio.addEventListener('change', function () {
        actualizarPeriodos();
        if (this.value) {
            filtroPeriodo.value = '';
            establecerFechasAnio(this.value);
        } else {
            filtroDesde.value = '';
            filtroHasta.value = '';
        }
    });

    filtroPeriodo.addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        establecerFechasPeriodo(option);
    });

    filtroTipo.addEventListener('change', function () {
        actualizarCategorias();
    });

    actualizarPeriodos();
    actualizarCategorias();

    if (filtroPeriodo.value && (!filtroDesde.value || !filtroHasta.value)) {
        establecerFechasPeriodo(filtroPeriodo.options[filtroPeriodo.selectedIndex]);
    } else if (filtroAnio.value && (!filtroDesde.value || !filtroHasta.value)) {
        establecerFechasAnio(filtroAnio.value);
    }
});
</script>