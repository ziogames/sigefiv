<x-card
    title="Información Personal"
    icon="cil-user">

    {{-- ============================================================
         INFORMACIÓN DE LA CUENTA
    ============================================================= --}}

    @if($usuario->google_id)

        <div
            class="alert alert-info
                   d-flex align-items-start gap-3 mb-4"
        >

            <i
                class="cil-info"
                style="font-size:22px;"
            ></i>

            <div>

                <div class="fw-semibold mb-1">

                    Cuenta vinculada con Google

                </div>

                <div class="small">

                    Tu correo de acceso está vinculado con Google.
                    Por seguridad, el correo no puede modificarse desde
                    SIGEFIV.

                </div>

            </div>

        </div>

    @endif


    <form
        action="{{ route('perfil.update') }}"
        method="POST"
    >

        @csrf

        @method('PUT')


        {{-- ========================================================
             NOMBRE Y CORREO
        ========================================================= --}}

        <div class="row">

            <div class="col-md-6">

                <x-input
                    label="Nombre"
                    name="name"
                    :value="old('name', $usuario->name)"
                    required
                />

            </div>


            <div class="col-md-6">

                @if($usuario->google_id)

                    <label class="form-label">

                        Correo electrónico

                    </label>


                    <div class="input-group mb-3">

                        <span class="input-group-text">

                            <i class="cil-envelope-closed"></i>

                        </span>

                        <input
                            type="email"
                            class="form-control"
                            value="{{ $usuario->email }}"
                            readonly
                        >

                        <span class="input-group-text">

                            <img
                                src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                                alt="Google"
                                width="18"
                                height="18"
                            >

                        </span>

                    </div>

                    <div class="form-text mt-n2 mb-3">

                        Correo administrado por Google.

                    </div>

                @else

                    <x-input
                        label="Correo electrónico"
                        name="email"
                        type="email"
                        :value="old('email', $usuario->email)"
                        required
                    />

                @endif

            </div>

        </div>


        {{-- ========================================================
             TELÉFONO Y DNI
        ========================================================= --}}

        <div class="row">

            <div class="col-md-6">

                <x-input
                    label="Teléfono"
                    name="telefono"
                    :value="old('telefono', $usuario->telefono ?? '')"
                />

            </div>


            <div class="col-md-6">

                <x-input
                    label="DNI"
                    name="dni"
                    :value="old('dni', $usuario->dni ?? '')"
                />

            </div>

        </div>


        {{-- ========================================================
             DIRECCIÓN
        ========================================================= --}}

        <div class="mb-3">

            <label
                for="direccion"
                class="form-label"
            >

                Dirección

            </label>


            <textarea
                id="direccion"
                name="direccion"
                rows="3"
                class="form-control @error('direccion') is-invalid @enderror"
                placeholder="Ingresa tu dirección"
            >{{ old('direccion', $usuario->direccion ?? '') }}</textarea>


            @error('direccion')

                <div class="invalid-feedback">

                    {{ $message }}

                </div>

            @enderror

        </div>


        {{-- ========================================================
             BOTÓN
        ========================================================= --}}

        <div class="d-flex justify-content-end">

            <x-button
                color="primary"
                icon="cil-save"
            >

                Guardar información

            </x-button>

        </div>

    </form>

</x-card>