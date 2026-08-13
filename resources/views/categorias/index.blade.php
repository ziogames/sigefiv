@extends('layouts.app')

@section('title','Categorías')

@section('content')

<div class="card shadow-sm border-0">

    <div class="card-header d-flex justify-content-between align-items-center">

        <h4 class="mb-0">

            <i class="cil-folder-open"></i>

            Categorías

        </h4>

        <a
            href="{{ route('categorias.create') }}"
            class="btn btn-primary">

            <i class="cil-plus"></i>

            Nueva Categoría

        </a>

    </div>

    <div class="card-body">

        @if(session('success'))

            <div class="alert alert-success">

                {{ session('success') }}

            </div>

        @endif

        <form
            method="GET"
            class="row g-3 mb-4">

            <div class="col-md-5">

                <input
                    type="text"
                    name="buscar"
                    class="form-control"
                    placeholder="Buscar categoría..."
                    value="{{ $buscar }}">

            </div>

            <div class="col-md-3">

                <select
                    name="tipo"
                    class="form-select">

                    <option value="">

                        Todos

                    </option>

                    <option
                        value="Ingreso"
                        @selected($tipo=='Ingreso')>

                        Ingresos

                    </option>

                    <option
                        value="Egreso"
                        @selected($tipo=='Egreso')>

                        Egresos

                    </option>

                </select>

            </div>

            <div class="col-md-2">

                <button
                    class="btn btn-primary w-100">

                    <i class="cil-search"></i>

                    Buscar

                </button>

            </div>

            <div class="col-md-2">

                <a
                    href="{{ route('categorias.index') }}"
                    class="btn btn-secondary w-100">

                    Limpiar

                </a>

            </div>

        </form>

        <div class="table-responsive">

            <table class="table table-hover align-middle">

                <thead>

                    <tr>

                        <th>Icono</th>

                        <th>Nombre</th>

                        <th>Tipo</th>

                        <th>Estado</th>

                        <th>Orden</th>

                        <th width="220">

                            Acciones

                        </th>

                    </tr>

                </thead>

                <tbody>

                @forelse($categorias as $categoria)

                    <tr>

                        <td>

                            <i class="{{ $categoria->icono }}"></i>

                        </td>

                        <td>

                            {{ $categoria->nombre }}

                        </td>

                        <td>

                            <span class="badge bg-{{ $categoria->color }}">

                                {{ $categoria->tipo }}

                            </span>

                        </td>

                        <td>

                            @if($categoria->activo)

                                <span class="badge bg-success">

                                    Activo

                                </span>

                            @else

                                <span class="badge bg-danger">

                                    Inactivo

                                </span>

                            @endif

                        </td>

                        <td>

                            {{ $categoria->orden }}

                        </td>

                        <td>

                            <a
                                href="{{ route('categorias.show',$categoria) }}"
                                class="btn btn-info btn-sm">

                                <i class="cil-magnifying-glass"></i>

                            </a>

                            <a
                                href="{{ route('categorias.edit',$categoria) }}"
                                class="btn btn-warning btn-sm">

                                <i class="cil-pencil"></i>

                            </a>

                            <form
                                action="{{ route('categorias.destroy',$categoria) }}"
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
                            colspan="6"
                            class="text-center">

                            No existen categorías.

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

        {{ $categorias->links() }}

    </div>

</div>

@push('js')

<script>

document.querySelectorAll('.formulario-eliminar').forEach(form=>{

    form.addEventListener('submit',function(e){

        e.preventDefault();

        Swal.fire({

            title:'¿Eliminar categoría?',

            text:'Esta acción no se puede deshacer.',

            icon:'warning',

            showCancelButton:true,

            confirmButtonText:'Eliminar',

            cancelButtonText:'Cancelar',

            confirmButtonColor:'#d33'

        }).then((result)=>{

            if(result.isConfirmed){

                form.submit();

            }

        });

    });

});

</script>

@endpush

@endsection