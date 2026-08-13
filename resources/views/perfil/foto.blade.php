<x-card>

    <div class="text-center">

        @if($usuario->foto)

            <img
                src="{{ asset('storage/'.$usuario->foto) }}"
                class="rounded-circle shadow mb-3"
                width="160"
                height="160"
                style="object-fit:cover;">

        @else

            <img
                src="https://ui-avatars.com/api/?name={{ urlencode($usuario->name) }}&background=321fdb&color=ffffff&size=200"
                class="rounded-circle shadow mb-3"
                width="160"
                height="160">

        @endif

        <h4 class="mb-1">

            {{ $usuario->name }}

        </h4>

        <p class="text-body-secondary mb-3">

            {{ $usuario->email }}

        </p>

        @foreach($usuario->roles as $rol)

            <span class="badge bg-primary">

                {{ $rol->name }}

            </span>

        @endforeach

    </div>

    <hr>

    <form
        action="{{ route('perfil.foto') }}"
        method="POST"
        enctype="multipart/form-data">

        @csrf

        <div class="mb-3">

            <label class="form-label">

                Cambiar fotografía

            </label>

            <input
                type="file"
                name="foto"
                class="form-control"
                accept="image/*">

            @error('foto')

                <div class="text-danger small mt-1">

                    {{ $message }}

                </div>

            @enderror

        </div>

        <div class="d-grid">

            <x-button
                color="primary"
                icon="cil-cloud-upload">

                Subir fotografía

            </x-button>

        </div>

    </form>

    <hr>

    <table class="table table-sm mb-0">

        <tr>

            <th>

                Registrado

            </th>

            <td class="text-end">

                {{ $usuario->created_at->format('d/m/Y') }}

            </td>

        </tr>

        <tr>

            <th>

                Última actualización

            </th>

            <td class="text-end">

                {{ $usuario->updated_at->format('d/m/Y') }}

            </td>

        </tr>

    </table>

</x-card>