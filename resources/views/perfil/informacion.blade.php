<x-card
    title="Información Personal"
    icon="cil-user">

    <form
        action="{{ route('perfil.update') }}"
        method="POST">

        @csrf
        @method('PUT')

        <div class="row">

            <div class="col-md-6">

                <x-input
                    label="Nombre"
                    name="name"
                    :value="$usuario->name"
                    required />

            </div>

            <div class="col-md-6">

                <x-input
                    label="Correo Electrónico"
                    name="email"
                    type="email"
                    :value="$usuario->email"
                    required />

            </div>

        </div>

        <div class="row">

            <div class="col-md-6">

                <x-input
                    label="Teléfono"
                    name="telefono"
                    :value="$usuario->telefono ?? ''" />

            </div>

            <div class="col-md-6">

                <x-input
                    label="DNI"
                    name="dni"
                    :value="$usuario->dni ?? ''" />

            </div>

        </div>

        <div class="mb-3">

            <label class="form-label">

                Dirección

            </label>

            <textarea
                name="direccion"
                rows="3"
                class="form-control">{{ old('direccion',$usuario->direccion ?? '') }}</textarea>

            @error('direccion')

                <div class="text-danger small mt-1">

                    {{ $message }}

                </div>

            @enderror

        </div>

        <div class="text-end">

            <x-button
                color="primary"
                icon="cil-save">

                Guardar Información

            </x-button>

        </div>

    </form>

</x-card>