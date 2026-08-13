<div class="mov-filters">

    <div class="mov-filter-title">
        <i class="cil-filter"></i>

        <span>
            Buscar y filtrar movimientos
        </span>
    </div>


    <form
        method="GET"
        action="{{ route('movimientos.index') }}"
        class="mov-filter-form"
        id="movimientosFiltros"
    >

        {{-- =====================================================
             BUSCAR
             ===================================================== --}}

        <div class="mov-filter-search">

            <label>
                Buscar por
            </label>

            <div class="mov-search-box">

                <input
                    type="text"
                    name="buscar"
                    value="{{ $buscar ?? '' }}"
                    placeholder="Número, concepto, persona o referencia..."
                >

                <i class="cil-search"></i>

            </div>

        </div>


        {{-- =====================================================
             AÑO
             ===================================================== --}}

        <div class="mov-filter-field">

            <label>
                Año
            </label>

            <select
                name="anio"
                id="filtroAnio"
            >

                <option value="">
                    Todos
                </option>

                @foreach(
                    $periodos
                        ->pluck('anio')
                        ->unique()
                        ->sort()
                    as $anioItem
                )

                    <option
                        value="{{ $anioItem }}"
                        @selected(
                            (string) request('anio') ===
                            (string) $anioItem
                        )
                    >
                        {{ $anioItem }}
                    </option>

                @endforeach

            </select>

        </div>


        {{-- =====================================================
             PERÍODO
             ===================================================== --}}

        <div class="mov-filter-field">

            <label>
                Período
            </label>

            <select
                name="periodo_id"
                id="filtroPeriodo"
            >

                <option
                    value=""
                    data-anio="todos"
                >
                    Todos
                </option>

                @foreach($periodos as $item)

                    <option
                        value="{{ $item->id }}"
                        data-anio="{{ $item->anio }}"
                        data-mes="{{ $item->mes }}"
                        @selected(
                            (string) $periodo_id ===
                            (string) $item->id
                        )
                    >

                        {{ $item->nombre_completo }}

                    </option>

                @endforeach

            </select>

        </div>


        {{-- =====================================================
             TIPO
             ===================================================== --}}

        <div class="mov-filter-field">

            <label>
                Tipo
            </label>

            <select
                name="tipo"
                id="filtroTipo"
            >

                <option value="">
                    Todos
                </option>

                <option
                    value="Ingreso"
                    @selected($tipo === 'Ingreso')
                >
                    Ingresos
                </option>

                <option
                    value="Egreso"
                    @selected($tipo === 'Egreso')
                >
                    Egresos
                </option>

            </select>

        </div>


        {{-- =====================================================
             CATEGORÍA
             ===================================================== --}}

      <div class="mov-filter-field">

    <label>
        Categoría
    </label>

    <select
        name="categoria_id"
        id="filtroCategoria"
    >

        <option value="">
            Todas
        </option>

        @foreach($categoriasIngreso as $categoria)

            <option
                value="{{ $categoria->id }}"
                data-tipo="Ingreso"
                @selected(
                    (string) $categoria_id ===
                    (string) $categoria->id
                )
            >
                {{ $categoria->nombre }}
            </option>

        @endforeach


        @foreach($categoriasEgreso as $categoria)

            <option
                value="{{ $categoria->id }}"
                data-tipo="Egreso"
                @selected(
                    (string) $categoria_id ===
                    (string) $categoria->id
                )
            >
                {{ $categoria->nombre }}
            </option>

        @endforeach

    </select>

</div>


        {{-- =====================================================
             DESDE
             ===================================================== --}}

        <div class="mov-filter-field mov-date-field">

            <label>
                Desde
            </label>

            <div class="mov-date-box">

                <input
                    type="date"
                    name="desde"
                    id="filtroDesde"
                    value="{{ $desde ?? '' }}"
                >

                <i class="cil-calendar"></i>

            </div>

        </div>


        {{-- =====================================================
             HASTA
             ===================================================== --}}

        <div class="mov-filter-field mov-date-field">

            <label>
                Hasta
            </label>

            <div class="mov-date-box">

                <input
                    type="date"
                    name="hasta"
                    id="filtroHasta"
                    value="{{ $hasta ?? '' }}"
                >

                <i class="cil-calendar"></i>

            </div>

        </div>


        {{-- =====================================================
             BOTONES
             ===================================================== --}}

        <div class="mov-filter-buttons">

            <button
                type="submit"
                class="mov-btn-search"
            >

                <i class="cil-search"></i>

                Buscar

            </button>


            <a
                href="{{ route('movimientos.index') }}"
                class="mov-btn-clear"
            >

                <i class="cil-x-circle"></i>

                Limpiar

            </a>

        </div>

    </form>

</div>


{{-- =========================================================
     LÓGICA DE FILTROS
     ========================================================= --}}

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const filtroAnio =
            document.getElementById(
                'filtroAnio'
            );

        const filtroPeriodo =
            document.getElementById(
                'filtroPeriodo'
            );

        const filtroTipo =
            document.getElementById(
                'filtroTipo'
            );

        const filtroCategoria =
            document.getElementById(
                'filtroCategoria'
            );

        const filtroDesde =
            document.getElementById(
                'filtroDesde'
            );

        const filtroHasta =
            document.getElementById(
                'filtroHasta'
            );


        /*
        |--------------------------------------------------------------------------
        | Formatear fecha
        |--------------------------------------------------------------------------
        */

        function formatearFecha(
            anio,
            mes,
            dia
        ) {

            return String(anio) +
                '-' +
                String(mes).padStart(2, '0') +
                '-' +
                String(dia).padStart(2, '0');

        }


        /*
        |--------------------------------------------------------------------------
        | Último día del mes
        |--------------------------------------------------------------------------
        */

        function ultimoDiaMes(
            anio,
            mes
        ) {

            return new Date(
                anio,
                mes,
                0
            ).getDate();

        }


        /*
        |--------------------------------------------------------------------------
        | Fechas de un año completo
        |--------------------------------------------------------------------------
        */

        function establecerFechasAnio(
            anio
        ) {

            if (!anio) {

                return;

            }

            filtroDesde.value =
                formatearFecha(
                    anio,
                    1,
                    1
                );

            filtroHasta.value =
                formatearFecha(
                    anio,
                    12,
                    31
                );

        }


        /*
        |--------------------------------------------------------------------------
        | Fechas de un período
        |--------------------------------------------------------------------------
        */

        function establecerFechasPeriodo(option) {

    if (
        !option ||
        !option.value
    ) {

        filtroDesde.value = '';
        filtroHasta.value = '';

        return;
    }

    const anio =
        option.dataset.anio;

    const mes =
        parseInt(
            option.dataset.mes
        );

    if (
        !anio ||
        !mes
    ) {

        filtroDesde.value = '';
        filtroHasta.value = '';

        return;
    }

    const ultimoDia =
        ultimoDiaMes(
            parseInt(anio),
            mes
        );

    filtroDesde.value =
        formatearFecha(
            anio,
            mes,
            1
        );

    filtroHasta.value =
        formatearFecha(
            anio,
            mes,
            ultimoDia
        );
}


        /*
        |--------------------------------------------------------------------------
        | Filtrar períodos por año
        |--------------------------------------------------------------------------
        */

        function actualizarPeriodos() {

            const anio =
                filtroAnio.value;


            const periodoActual =
                filtroPeriodo.value;


            Array.from(
                filtroPeriodo.options
            ).forEach(
                function (option) {

                    if (!option.value) {

                        option.hidden = false;

                        return;

                    }


                    const anioPeriodo =
                        option.dataset.anio;


                    option.hidden =
                        anio &&
                        anioPeriodo !== anio;

                }
            );


            /*
            | Si el período seleccionado
            | no pertenece al año elegido,
            | volvemos a Todos.
            */

            const optionSeleccionada =
                filtroPeriodo.options[
                    filtroPeriodo.selectedIndex
                ];


            if (
                anio &&
                optionSeleccionada &&
                optionSeleccionada.value &&
                optionSeleccionada.dataset.anio !== anio
            ) {

                filtroPeriodo.value = '';

            }


            /*
            | Si seleccionamos un año,
            | automáticamente usamos
            | Todos los períodos de ese año.
            */

            if (
                anio &&
                !periodoActual
            ) {

                filtroPeriodo.value = '';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Categorías según Tipo
        |--------------------------------------------------------------------------
        */

        function actualizarCategorias() {

            const tipo =
                filtroTipo.value;


            Array.from(
                filtroCategoria.options
            ).forEach(
                function (option) {

                    /*
                    | La opción "Todas"
                    | siempre está disponible.
                    */

                    if (!option.value) {

                        option.hidden = false;

                        return;

                    }


                    /*
                    | Si la categoría tiene
                    | data-tipo, aplicamos el filtro.
                    */

                    const tipoCategoria =
                        option.dataset.tipo;


                    if (
                        tipo &&
                        tipoCategoria
                    ) {

                        option.hidden =
                            tipoCategoria !== tipo;

                    }

                    else {

                        /*
                        | Mientras las categorías
                        | no tengan clasificación
                        | en el modelo, las dejamos visibles.
                        */

                        option.hidden = false;

                    }

                }
            );


            /*
            | Comprobar si la categoría
            | seleccionada sigue siendo válida.
            */

            const seleccionada =
                filtroCategoria.options[
                    filtroCategoria.selectedIndex
                ];


            if (
                tipo &&
                seleccionada &&
                seleccionada.value &&
                seleccionada.dataset.tipo &&
                seleccionada.dataset.tipo !== tipo
            ) {

                filtroCategoria.value = '';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Cambio de Año
        |--------------------------------------------------------------------------
        */

        filtroAnio.addEventListener(
            'change',
            function () {

                actualizarPeriodos();

                /*
                | Si se selecciona un año,
                | Todos = año completo.
                */

                if (this.value) {

                    filtroPeriodo.value = '';

                    establecerFechasAnio(
                        this.value
                    );

                }

                else {

                    filtroDesde.value = '';

                    filtroHasta.value = '';

                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Cambio de Período
        |--------------------------------------------------------------------------
        */

        filtroPeriodo.addEventListener(
            'change',
            function () {

                const option =
                    this.options[
                        this.selectedIndex
                    ];


                establecerFechasPeriodo(
                    option
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Cambio de Tipo
        |--------------------------------------------------------------------------
        */

        filtroTipo.addEventListener(
            'change',
            function () {

                actualizarCategorias();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Estado inicial
        |--------------------------------------------------------------------------
        */

        actualizarPeriodos();

        actualizarCategorias();


        /*
        |--------------------------------------------------------------------------
        | Si ya existe un período seleccionado,
        | establecer sus fechas si están vacías.
        |--------------------------------------------------------------------------
        */

        if (
            filtroPeriodo.value &&
            (
                !filtroDesde.value ||
                !filtroHasta.value
            )
        ) {

            establecerFechasPeriodo(
                filtroPeriodo.options[
                    filtroPeriodo.selectedIndex
                ]
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Si existe un año pero no período,
        | establecer año completo.
        |--------------------------------------------------------------------------
        */

        else if (
            filtroAnio.value &&
            (
                !filtroDesde.value ||
                !filtroHasta.value
            )
        ) {

            establecerFechasAnio(
                filtroAnio.value
            );

        }

    }
);

</script>