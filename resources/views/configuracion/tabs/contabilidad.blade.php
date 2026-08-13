@php

$bloqueado = $configuracion->contabilidad_iniciada;

@endphp

<div
    class="tab-pane fade"
    id="contabilidad">

    <div class="row">

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Año de inicio

            </label>

            <input
                 @disable($bloqueado) 
                type="number"
                name="anio_inicio"
                class="form-control"
                value="{{ old('anio_inicio',$configuracion->anio_inicio ?? date('Y')) }}">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Mes de inicio

            </label>

            <select
                @disabled($bloqueado)
                name="mes_inicio"
                class="form-select">

                @foreach([
                    1=>'Enero',
                    2=>'Febrero',
                    3=>'Marzo',
                    4=>'Abril',
                    5=>'Mayo',
                    6=>'Junio',
                    7=>'Julio',
                    8=>'Agosto',
                    9=>'Septiembre',
                    10=>'Octubre',
                    11=>'Noviembre',
                    12=>'Diciembre'
                ] as $numero=>$mes)

                    <option
                        value="{{ $numero }}"
                        @selected(old('mes_inicio',$configuracion->mes_inicio ?? date('n'))==$numero)>

                        {{ $mes }}

                    </option>

                @endforeach

            </select>

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Saldo de apertura

            </label>

            <div class="input-group">

                <span class="input-group-text">

                    S/.

                </span>

                <input @disabled($bloqueado)
                    type="number"
                    step="0.01"
                    min="0"
                    name="saldo_apertura"
                    class="form-control text-end"
                    value="{{ old('saldo_apertura',$configuracion->saldo_apertura ?? 0) }}">

            </div>

        </div>

    </div>

    <div class="alert alert-info">

        <strong>Importante:</strong>

        El saldo de apertura se registra una sola vez.
        Después el sistema calculará automáticamente
        el saldo inicial de cada período.

    </div>
    @if($bloqueado)

<div class="alert alert-success">

    <i class="cil-check-circle"></i>

    <strong>

        Contabilidad inicializada.

    </strong>

    <br>

    El saldo de apertura ya fue utilizado para crear el primer período.

</div>

@endif

</div>