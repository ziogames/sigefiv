<div class="row">

    {{-- Número --}}
    <div class="col-md-3 mb-3">

        <label class="form-label">

            Número

        </label>

        <div class="form-control  fw-bold">

            {{ $numero ?? ($movimiento->numero ?? 'Se generará automáticamente') }}

        </div>

    </div>

    {{-- Fecha --}}
    {{-- Fecha --}}
    {{-- Fecha --}}
    <div class="col-md-3 mb-3">

        <label class="form-label fw-semibold">
            Fecha del movimiento
        </label>

        @php
            $fechaMinima = $periodoActual
                ? \Carbon\Carbon::create($periodoActual->anio, $periodoActual->mes, 1)->startOfMonth()
                : now()->startOfMonth();

            $fechaMaxima = $periodoActual
                ? \Carbon\Carbon::create($periodoActual->anio, $periodoActual->mes, 1)->endOfMonth()
                : now()->endOfMonth();

            $fechaActual = old('fecha', optional($movimiento->fecha)->format('Y-m-d') ?? $fechaMinima->format('Y-m-d'));
        @endphp

        <input type="date" name="fecha" class="form-control" value="{{ $fechaActual }}"
            min="{{ $fechaMinima->format('Y-m-d') }}" max="{{ $fechaMaxima->format('Y-m-d') }}" required>

        <div class="form-text mt-1">
            <i class="cil-calendar"></i>

            Solo días de
            <strong>
                {{ $periodoActual->nombre }}
                {{ $periodoActual->anio }}
            </strong>

            · Del
            {{ $fechaMinima->format('d/m/Y') }}
            al
            {{ $fechaMaxima->format('d/m/Y') }}
        </div>

    </div>

</div>

<hr>

<div class="row">

    {{-- Información financiera --}}
    <div class="row g-3">

        {{-- Categoría --}}
        <div class="col-lg-6">

            <div class="form-section-field">

                <label class="form-label fw-semibold">
                    <i class="cil-list-rich"></i>
                    Categoría
                </label>

                @php
                    $ingresos = $categorias->where('tipo', 'Ingreso');
                    $egresos = $categorias->where('tipo', 'Egreso');
                @endphp

                <select id="categoria" name="categoria_id" class="form-select" required>

                    <option value="">
                        -- Seleccione una categoría --
                    </option>

                    <optgroup label="💰 INGRESOS">

                        @foreach ($ingresos as $categoria)
                            <option value="{{ $categoria->id }}"
                                {{ old('categoria_id', $movimiento->categoria_id) == $categoria->id ? 'selected' : '' }}>

                                {{ $categoria->codigo }} -
                                {{ $categoria->nombre }}

                            </option>
                        @endforeach

                    </optgroup>


                    <optgroup label="💸 EGRESOS">

                        @foreach ($egresos as $categoria)
                            <option value="{{ $categoria->id }}"
                                {{ old('categoria_id', $movimiento->categoria_id) == $categoria->id ? 'selected' : '' }}>

                                {{ $categoria->codigo }} -
                                {{ $categoria->nombre }}

                            </option>
                        @endforeach

                    </optgroup>

                </select>

                <div class="form-text">
                    Seleccione la categoría correspondiente al movimiento.
                </div>

            </div>

        </div>


        {{-- Forma de pago --}}
        <div class="col-lg-3">

            <div class="form-section-field">

                <label class="form-label fw-semibold">
                    <i class="cil-credit-card"></i>
                    Forma de pago
                </label>

                <select name="forma_pago" class="form-select">

                    <option {{ old('forma_pago', $movimiento->forma_pago) == 'Efectivo' ? 'selected' : '' }}>
                        Efectivo
                    </option>

                    <option {{ old('forma_pago', $movimiento->forma_pago) == 'Yape' ? 'selected' : '' }}>
                        Yape
                    </option>

                    <option {{ old('forma_pago', $movimiento->forma_pago) == 'Plin' ? 'selected' : '' }}>
                        Plin
                    </option>

                    <option {{ old('forma_pago', $movimiento->forma_pago) == 'Transferencia' ? 'selected' : '' }}>
                        Transferencia
                    </option>

                    <option {{ old('forma_pago', $movimiento->forma_pago) == 'Depósito' ? 'selected' : '' }}>
                        Depósito
                    </option>

                    <option {{ old('forma_pago', $movimiento->forma_pago) == 'Otro' ? 'selected' : '' }}>
                        Otro
                    </option>

                </select>

            </div>

        </div>


        {{-- Monto --}}
        <div class="col-lg-3">

            <div class="form-section-field">

                <label class="form-label fw-semibold">
                    <i class="cil-money"></i>
                    Monto
                </label>

                <div class="input-group">

                    <span class="input-group-text fw-semibold">
                        S/.
                    </span>

                    <input type="number" step="0.01" min="0.01" name="monto"
                        class="form-control text-end fw-semibold" placeholder="0.00"
                        value="{{ old('monto', $movimiento->monto) }}" required>

                </div>

                <div class="form-text">
                    Importe de la operación.
                </div>

            </div>

        </div>

    </div>

</div>

<div class="row">

    {{-- Persona y referencia --}}
    <div class="row g-3">

        {{-- Persona / Proveedor --}}
        <div class="col-lg-6">

            <div class="form-section-field">

                <label class="form-label fw-semibold">
                    <i class="cil-user"></i>
                    Persona / Proveedor
                </label>

                <input type="text" name="persona" class="form-control" placeholder="Nombre de la persona o proveedor"
                    value="{{ old('persona', $movimiento->persona) }}">

                <div class="form-text">
                    Persona relacionada con la operación.
                </div>

            </div>

        </div>


        {{-- Referencia --}}
        <div class="col-lg-6">

            <div class="form-section-field">

                <label class="form-label fw-semibold">
                    <i class="cil-paperclip"></i>
                    Referencia
                </label>

                <input type="text" name="referencia" class="form-control" placeholder="Ej.: R.I. N° 430"
                    value="{{ old('referencia', $movimiento->referencia) }}">

                <div class="form-text">
                    Número de recibo, documento u otra referencia.
                </div>

            </div>

        </div>

    </div>

</div>
{{-- Detalle de la operación --}}
<div class="mt-4 pt-4 border-top">

    <div class="mb-4">

        <div class="d-flex align-items-center mb-1">

            <i class="cil-description me-2 fs-5"></i>

            <h5 class="fw-bold mb-0">
                Detalle de la operación
            </h5>

        </div>

        <small class="text-body-secondary">
            Información que describe el movimiento contable.
        </small>

    </div>


    {{-- Concepto --}}
    <div class="mb-4">

        <label class="form-label fw-semibold">
            Concepto
        </label>

        <input
            type="text"
            name="concepto"
            class="form-control"
            placeholder="Ej.: Pago de servicio de agua"
            value="{{ old('concepto', $movimiento->concepto) }}"
            required>

        <div class="form-text">
            Describa brevemente el motivo de la operación.
        </div>

    </div>


    {{-- Observaciones --}}
    <div class="mb-4">

        <label class="form-label fw-semibold">
            Observaciones
        </label>

        <textarea
            class="form-control"
            rows="3"
            name="observaciones"
            placeholder="Información adicional sobre el movimiento...">{{ old('observaciones', $movimiento->observaciones) }}</textarea>

    </div>


    {{-- Comprobante --}}
    <div class="mb-2">

        <label class="form-label fw-semibold">
            Comprobante
        </label>

        <input
            type="file"
            name="comprobante"
            class="form-control">

        <div class="form-text">
            Formatos permitidos: PDF, JPG, JPEG, PNG o WEBP. Máximo 4 MB.
        </div>

    </div>

</div>