<div class="mov-topbar">

    {{-- TÍTULO --}}

    <div class="mov-title-area">

        <h1>
            Movimientos
        </h1>

        <p>
            Registro y administración de ingresos y egresos
        </p>

    </div>


    {{-- PERÍODO ACTIVO --}}

    <div class="mov-top-actions">

        <div class="mov-period-active">

            <div class="mov-period-icon">
                <i class="cil-calendar"></i>
            </div>

            <div class="mov-period-info">

                <span>
                    Período activo
                </span>

                <strong>

                    @if(isset($periodo) && $periodo)

                        {{ $periodo->nombre }}
                        {{ $periodo->anio }}

                    @else

                        Sin período

                    @endif

                </strong>

            </div>

            <div>

                @if(isset($periodo) && $periodo)

                    @if($periodo->estado === 'Abierto')

                        <span class="mov-status-open">
                            Abierto
                        </span>

                    @else

                        <span class="mov-status-closed">
                            Cerrado
                        </span>

                    @endif

                @endif

            </div>

        </div>


        {{-- NUEVO MOVIMIENTO --}}

       @can('movimientos.create')

    <a
        href="{{ route('movimientos.create') }}"
        class="btn btn-primary">

        <i class="cil-plus"></i>

        Nuevo Movimiento

    </a>

@endcan

    </div>

</div>