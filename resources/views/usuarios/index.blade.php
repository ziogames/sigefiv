@extends('layouts.app')

@section('title','Usuarios')

@section('content')

<x-page-title
    title="Usuarios"
    icon="cil-user">

    <x-button
        color="primary"
        icon="cil-plus"
        onclick="location.href='{{ route('usuarios.create') }}'">

        Nuevo Usuario

    </x-button>

</x-page-title>

<x-card>

    <x-search />

    <x-table bordered hover>

        <thead class="table-light">

            <tr>

                <th width="70">
                    ID
                </th>

                <th>
                    Nombre
                </th>

                <th>
                    Correo
                </th>

                <th>
                    Rol
                </th>

                <th width="260"
                    class="text-center">

                    Estado

                </th>

                <th width="180"
                    class="text-center">

                    Acciones

                </th>

            </tr>

        </thead>

        <tbody>

            @forelse($usuarios as $usuario)

                <tr>

                    <td>
                        {{ $usuario->id }}
                    </td>

                    <td>

                        {{ $usuario->name }}

                        @if($usuario->google_id)

                            <div class="mt-1">

                                <span
                                    class="badge bg-light text-dark border">

                                    <i class="cil-cloud-download me-1"></i>

                                    Google

                                </span>

                            </div>

                        @endif

                    </td>

                    <td>
                        {{ $usuario->email }}
                    </td>

                    <td>

                        @forelse($usuario->roles as $rol)

                            <span class="badge bg-primary">

                                {{ $rol->name }}

                            </span>

                        @empty

                            <span class="badge bg-secondary">

                                Sin Rol

                            </span>

                        @endforelse

                    </td>

                    <td class="text-center">

                        <form
                            action="{{ route('usuarios.estado', $usuario) }}"
                            method="POST"
                            class="formulario-estado d-flex align-items-center justify-content-center gap-2"
                        >

                            @csrf

                            @method('PATCH')

                            <select
                                name="estado"
                                class="form-select form-select-sm estado-select"
                                style="width: 135px;"
                            >

                                <option
                                    value="pendiente"
                                    {{ $usuario->estado === 'pendiente' ? 'selected' : '' }}
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="activo"
                                    {{ $usuario->estado === 'activo' ? 'selected' : '' }}
                                >
                                    Activo
                                </option>

                                <option
                                    value="bloqueado"
                                    {{ $usuario->estado === 'bloqueado' ? 'selected' : '' }}
                                >
                                    Bloqueado
                                </option>

                            </select>

                            @if($usuario->id !== 1)

                                <button
                                    type="submit"
                                    class="btn btn-primary btn-sm"
                                    title="Guardar estado"
                                >

                                    <i class="cil-save"></i>

                                </button>

                            @else

                                <span
                                    class="badge bg-success"
                                    title="El Administrador principal está protegido"
                                >

                                    Protegido

                                </span>

                            @endif

                        </form>

                    </td>

                    <td class="text-center">

                        <a
                            href="{{ route('usuarios.show',$usuario) }}"
                            class="btn btn-info btn-sm"
                            title="Ver usuario">

                            <i class="cil-magnifying-glass"></i>

                        </a>

                        <a
                            href="{{ route('usuarios.edit',$usuario) }}"
                            class="btn btn-warning btn-sm"
                            title="Editar usuario">

                            <i class="cil-pencil"></i>

                        </a>

                        @if($usuario->id !== 1)

                            <form
                                action="{{ route('usuarios.destroy',$usuario) }}"
                                method="POST"
                                class="d-inline formulario-eliminar"
                            >

                                @csrf

                                @method('DELETE')

                                <button
                                    type="submit"
                                    class="btn btn-danger btn-sm"
                                    title="Eliminar usuario"
                                >

                                    <i class="cil-trash"></i>

                                </button>

                            </form>

                        @endif

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="6"
                        class="text-center py-5"
                    >

                        <i
                            class="cil-user"
                            style="font-size:50px"
                        >
                        </i>

                        <br>

                        No existen usuarios registrados.

                    </td>

                </tr>

            @endforelse

        </tbody>

    </x-table>

    <div class="mt-4">

        {{ $usuarios->links() }}

    </div>

</x-card>


@push('scripts')

<script>

/*
|--------------------------------------------------------------------------
| ELIMINAR USUARIO
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll('.formulario-eliminar')
    .forEach(form => {

        form.addEventListener('submit', function(e) {

            e.preventDefault();

            Swal.fire({

                title: 'Eliminar usuario',

                text: 'Esta acción no podrá deshacerse.',

                icon: 'warning',

                showCancelButton: true,

                confirmButtonText: 'Eliminar',

                cancelButtonText: 'Cancelar',

                confirmButtonColor: '#d33'

            }).then((r) => {

                if (r.isConfirmed) {

                    form.submit();

                }

            });

        });

    });


/*
|--------------------------------------------------------------------------
| CAMBIAR ESTADO
|--------------------------------------------------------------------------
*/

document
    .querySelectorAll('.formulario-estado')
    .forEach(form => {

        form.addEventListener('submit', function(e) {

            e.preventDefault();

            const select =
                form.querySelector('.estado-select');

            const estado =
                select.value;

            const nombres = {

                pendiente: 'Pendiente',

                activo: 'Activo',

                bloqueado: 'Bloqueado'

            };

            const mensajes = {

                pendiente:
                    'El usuario quedará pendiente de aprobación.',

                activo:
                    'El usuario podrá ingresar al sistema.',

                bloqueado:
                    'El usuario no podrá ingresar al sistema.'

            };

            Swal.fire({

                title: 'Cambiar estado',

                text:
                    mensajes[estado] +
                    ' ¿Deseas continuar?',

                icon: estado === 'bloqueado'
                    ? 'warning'
                    : 'question',

                showCancelButton: true,

                confirmButtonText:
                    estado === 'activo'
                        ? 'Sí, activar'
                        : 'Sí, cambiar',

                cancelButtonText: 'Cancelar',

                confirmButtonColor:
                    estado === 'bloqueado'
                        ? '#d33'
                        : '#0d6efd'

            }).then((r) => {

                if (r.isConfirmed) {

                    form.submit();

                }

            });

        });

    });

</script>

@endpush

@endsection