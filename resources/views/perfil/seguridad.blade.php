<x-card
    title="Seguridad"
    icon="cil-lock-locked"
    class="mt-4">

    <form
        action="{{ route('perfil.password') }}"
        method="POST">

        @csrf
        @method('PUT')

        <div class="row">

            <div class="col-md-6">

                <x-input
                    label="Nueva contraseña"
                    name="password"
                    type="password"
                    required />

            </div>

            <div class="col-md-6">

                <x-input
                    label="Confirmar contraseña"
                    name="password_confirmation"
                    type="password"
                    required />

            </div>

        </div>

        <div class="text-end">

            <x-button
                color="success"
                icon="cil-lock-locked">

                Cambiar contraseña

            </x-button>

        </div>

    </form>

</x-card>