<div class="row">

    <div class="col-md-6 mb-3">

        <label class="form-label">

            Nombre

        </label>

        <input
            type="text"
            name="nombre"
            class="form-control @error('nombre') is-invalid @enderror"
            value="{{ old('nombre',$categoria->nombre ?? '') }}"
            autofocus>

        @error('nombre')

            <div class="invalid-feedback">

                {{ $message }}

            </div>

        @enderror

    </div>

    <div class="col-md-6 mb-3">

        <label class="form-label">

            Tipo

        </label>

        <select
            name="tipo"
            class="form-select">

            <option value="Ingreso"
                @selected(old('tipo',$categoria->tipo ?? '')=='Ingreso')>

                Ingreso

            </option>

            <option value="Egreso"
                @selected(old('tipo',$categoria->tipo ?? '')=='Egreso')>

                Egreso

            </option>

        </select>

    </div>

    <div class="col-md-4 mb-3">

        <label class="form-label">

            Icono

        </label>

        <input
            type="text"
            name="icono"
            class="form-control"
            value="{{ old('icono',$categoria->icono ?? 'cil-folder') }}">

    </div>

    <div class="col-md-4 mb-3">

        <label class="form-label">

            Color

        </label>

        <select
            name="color"
            class="form-select">

            @foreach([
                'primary',
                'success',
                'danger',
                'warning',
                'info',
                'secondary',
                'dark'
            ] as $color)

                <option
                    value="{{ $color }}"
                    @selected(old('color',$categoria->color ?? 'primary')==$color)>

                    {{ ucfirst($color) }}

                </option>

            @endforeach

        </select>

    </div>

    <div class="col-md-4 mb-3">

        <label class="form-label">

            Orden

        </label>

        <input
            type="number"
            name="orden"
            class="form-control"
            value="{{ old('orden',$categoria->orden ?? 0) }}">

    </div>

    <div class="col-md-12 mb-4">

        <div class="form-check form-switch">

            <input
                class="form-check-input"
                type="checkbox"
                name="activo"
                value="1"
                @checked(old('activo',$categoria->activo ?? true))>

            <label class="form-check-label">

                Categoría activa

            </label>

        </div>

    </div>

</div>

<div class="text-end">

    <a
        href="{{ route('categorias.index') }}"
        class="btn btn-secondary">

        Cancelar

    </a>

    <button
        class="btn btn-primary">

        <i class="cil-save"></i>

        Guardar

    </button>

</div>