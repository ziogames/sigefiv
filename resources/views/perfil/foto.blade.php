<x-card>

    {{-- ============================================================
         FOTO Y DATOS DEL USUARIO
    ============================================================= --}}

    <div class="text-center">

        <div class="position-relative d-inline-block mb-3">

            <img
                src="{{ $usuario->avatar }}"
                alt="{{ $usuario->name }}"
                class="rounded-circle shadow"
                width="160"
                height="160"
                style="
                    object-fit:cover;
                    border:4px solid var(--cui-body-bg);
                "
            >

            @if($usuario->google_id)

                <span
                    class="position-absolute bottom-0 end-0
                           bg-body rounded-circle shadow
                           d-flex align-items-center justify-content-center"
                    style="
                        width:38px;
                        height:38px;
                        border:1px solid var(--cui-border-color);
                    "
                    title="Cuenta vinculada con Google"
                >

                    <img
                        src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                        alt="Google"
                        width="20"
                        height="20"
                    >

                </span>

            @endif

        </div>


        <h4 class="mb-1">

            {{ $usuario->name }}

        </h4>


        <p class="text-body-secondary mb-2">

            {{ $usuario->email }}

        </p>


        {{-- Estado --}}

        @if($usuario->estado === 'activo')

            <span class="badge bg-success">

                Activo

            </span>

        @elseif($usuario->estado === 'pendiente')

            <span class="badge bg-warning text-dark">

                Pendiente

            </span>

        @elseif($usuario->estado === 'bloqueado')

            <span class="badge bg-danger">

                Bloqueado

            </span>

        @endif


        {{-- Rol --}}

        <div class="mt-2">

            @forelse($usuario->roles as $rol)

                <span class="badge bg-primary">

                    {{ $rol->name }}

                </span>

            @empty

                <span class="badge bg-secondary">

                    Sin rol

                </span>

            @endforelse

        </div>

    </div>


    <hr>


    {{-- ============================================================
         MÉTODO DE ACCESO
    ============================================================= --}}

    <div class="mb-4">

        <div class="small text-body-secondary mb-2">

            Método de acceso

        </div>


        @if($usuario->google_id)

            <div
                class="d-flex align-items-center gap-3
                       p-3 rounded-3 border
                       bg-body-secondary"
            >

                <div
                    class="rounded-circle bg-body
                           d-flex align-items-center justify-content-center"
                    style="
                        width:38px;
                        height:38px;
                        flex-shrink:0;
                    "
                >

                    <img
                        src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                        alt="Google"
                        width="20"
                        height="20"
                    >

                </div>

                <div>

                    <div class="fw-semibold text-body">

                        Google

                    </div>

                    <div class="small text-body-secondary">

                        Cuenta vinculada

                    </div>

                </div>

            </div>

        @else

            <div
                class="d-flex align-items-center gap-3
                       p-3 rounded-3 border
                       bg-body-secondary"
            >

                <div
                    class="rounded-circle bg-primary text-white
                           d-flex align-items-center justify-content-center"
                    style="
                        width:38px;
                        height:38px;
                        flex-shrink:0;
                    "
                >

                    <i class="cil-lock-locked"></i>

                </div>

                <div>

                    <div class="fw-semibold text-body">

                        SIGEFIV

                    </div>

                    <div class="small text-body-secondary">

                        Correo y contraseña

                    </div>

                </div>

            </div>

        @endif

    </div>


    {{-- ============================================================
         FOTOGRAFÍA
    ============================================================= --}}

    @if(!$usuario->google_id)

        <hr>

        <div class="mb-3">

            <label class="form-label fw-semibold">

                Fotografía de perfil

            </label>


            <form
                action="{{ route('perfil.foto') }}"
                method="POST"
                enctype="multipart/form-data"
            >

                @csrf

                <input
                    type="file"
                    name="foto"
                    class="form-control"
                    accept="image/jpeg,image/png,image/webp"
                >


                <div class="form-text">

                    JPG, PNG o WebP. Tamaño máximo: 2 MB.

                </div>


                @error('foto')

                    <div class="text-danger small mt-1">

                        {{ $message }}

                    </div>

                @enderror


                <div class="d-grid mt-3">

                    <x-button
                        color="primary"
                        icon="cil-cloud-upload"
                    >

                        Actualizar fotografía

                    </x-button>

                </div>

            </form>

        </div>

    @else

        <hr>

        <div
            class="alert alert-info
                   d-flex align-items-start gap-2 mb-0"
        >

            <i
                class="cil-info"
                style="font-size:20px;"
            ></i>

            <div>

                <div class="fw-semibold">

                    Fotografía administrada por Google

                </div>

                <div class="small">

                    La fotografía de tu perfil se obtiene de tu cuenta
                    de Google y se actualizará automáticamente cuando
                    Google proporcione una nueva imagen.

                </div>

            </div>

        </div>

    @endif


    <hr>


    {{-- ============================================================
         INFORMACIÓN DE LA CUENTA
    ============================================================= --}}

    <div class="small text-body-secondary mb-2">

        Información de la cuenta

    </div>


    <table class="table table-sm mb-0">

        <tr>

            <th class="text-body-secondary">

                Registrado

            </th>

            <td class="text-end text-body">

                {{ $usuario->created_at?->format('d/m/Y') }}

            </td>

        </tr>


        <tr>

            <th class="text-body-secondary">

                Última actualización

            </th>

            <td class="text-end text-body">

                {{ $usuario->updated_at?->format('d/m/Y') }}

            </td>

        </tr>

    </table>

</x-card>