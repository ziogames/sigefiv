<x-card
    title="Seguridad"
    icon="cil-lock-locked"
    class="mt-4">

    @if($usuario->google_id)

        <div
            class="alert alert-info
                   d-flex align-items-start gap-3 mb-0"
        >

            <i
                class="cil-info"
                style="font-size:22px;"
            ></i>

            <div>

                <div class="fw-semibold mb-1">

                    Contraseña administrada por Google

                </div>

                <div class="small">

                    Tu cuenta está vinculada con Google.
                    La contraseña utilizada para iniciar sesión
                    se administra directamente desde tu cuenta
                    de Google.

                    SIGEFIV no puede modificarla.

                </div>

            </div>

        </div>

    @else

        <form
            action="{{ route('perfil.password') }}"
            method="POST"
        >

            @csrf

            @method('PUT')


            <div class="row">

                <div class="col-md-6">

                    <x-input
                        label="Nueva contraseña"
                        name="password"
                        type="password"
                        required
                    />

                </div>


                <div class="col-md-6">

                    <x-input
                        label="Confirmar contraseña"
                        name="password_confirmation"
                        type="password"
                        required
                    />

                </div>

            </div>


            <div class="text-end">

                <x-button
                    color="success"
                    icon="cil-lock-locked"
                >

                    Cambiar contraseña

                </x-button>

            </div>

        </form>

    @endif

</x-card>