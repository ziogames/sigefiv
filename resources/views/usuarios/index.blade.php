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

                        <a
                            href="{{ route('usuarios.show',$usuario) }}"
                            class="btn btn-info btn-sm">

                            <i class="cil-magnifying-glass"></i>

                        </a>

                        <a
                            href="{{ route('usuarios.edit',$usuario) }}"
                            class="btn btn-warning btn-sm">

                            <i class="cil-pencil"></i>

                        </a>

                        <form
                            action="{{ route('usuarios.destroy',$usuario) }}"
                            method="POST"
                            class="d-inline formulario-eliminar">

                            @csrf
                            @method('DELETE')

                            <button
                                class="btn btn-danger btn-sm">

                                <i class="cil-trash"></i>

                            </button>

                        </form>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="5"
                        class="text-center py-5">

                        <i
                            class="cil-user"
                            style="font-size:50px">

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

@push('js')

<script>

document.querySelectorAll('.formulario-eliminar').forEach(form=>{

form.addEventListener('submit',function(e){

e.preventDefault();

Swal.fire({

title:'Eliminar usuario',

text:'Esta acción no podrá deshacerse.',

icon:'warning',

showCancelButton:true,

confirmButtonText:'Eliminar',

cancelButtonText:'Cancelar',

confirmButtonColor:'#d33'

}).then((r)=>{

if(r.isConfirmed){

form.submit();

}

});

});

});

</script>

@endpush

@endsection