@extends('layouts.app')

@section('title','Bitácora')

@section('content')

<div class="container-fluid">
    <div class="row mb-4">

    <div class="col-md-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-body-secondary">

                    REGISTROS

                </small>

                <h2>

                    {{ $totalRegistros }}

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-body-secondary">

                    USUARIOS

                </small>

                <h2>

                    {{ $totalUsuarios }}

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-body-secondary">

                    MÓDULOS

                </small>

                <h2>

                    {{ $totalModulos }}

                </h2>

            </div>

        </div>

    </div>

    <div class="col-md-3">

        <div class="card border-0 shadow-sm">

            <div class="card-body">

                <small class="text-body-secondary">

                    HOY

                </small>

                <h2>

                    {{ $accionesHoy }}

                </h2>

            </div>

        </div>

    </div>

</div>

    <div class="row mb-4">

        <div class="col">

            <h2>

                <i class="cil-notes"></i>

                Bitácora del Sistema

            </h2>

            <small class="text-body-secondary">

                Registro de actividades de los usuarios

            </small>

        </div>

    </div>

    <div class="card shadow-sm border-0">

        <div class="card-header ">

            <form method="GET">

                <div class="row g-2">

                    <div class="col-md-5">

                        <input
                            type="text"
                            name="buscar"
                            value="{{ request('buscar') }}"
                            class="form-control"
                            placeholder="Buscar descripción...">

                    </div>

                    <div class="col-auto">

                        <button class="btn btn-primary">

                            <i class="cil-search"></i>

                            Buscar

                        </button>

                    </div>


                </div>
                     {{-- debajo del buscarod  --}}
                        <div class="row mt-3">

    <div class="col-md-3">

        <select
            name="usuario"
            class="form-select">

            <option value="">

                Todos los usuarios

            </option>

            @foreach($usuarios as $usuario)

                <option
                    value="{{ $usuario->id }}"
                    @selected(request('usuario')==$usuario->id)>

                    {{ $usuario->name }}

                </option>

            @endforeach

        </select>

    </div>

    <div class="col-md-3">

        <select
            name="modulo"
            class="form-select">

            <option value="">

                Todos los módulos

            </option>

            @foreach($modulos as $modulo)

                <option
                    value="{{ $modulo }}"
                    @selected(request('modulo')==$modulo)>

                    {{ $modulo }}

                </option>

            @endforeach

        </select>

    </div>

    <div class="col-md-2">

        <input
            type="date"
            name="desde"
            value="{{ request('desde') }}"
            class="form-control">

    </div>

    <div class="col-md-2">

        <input
            type="date"
            name="hasta"
            value="{{ request('hasta') }}"
            class="form-control">

    </div>

    <div class="col-md-2">

        <button
            class="btn btn-primary w-100">

            <i class="cil-filter"></i>

            Filtrar

        </button>

    </div>

</div>

            </form>
           

               
        </div>
        

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th>Fecha</th>

                            <th>Usuario</th>

                            <th>Módulo</th>

                            <th>Acción</th>

                            <th>Descripción</th>

                            <th>IP</th>

                        </tr>

                    </thead>

                    <tbody>

                    @forelse($bitacoras as $item)

                        <tr>

                           <td>
    {{ $item->created_at->format('d/m/Y H:i') }}
</td>

<td>
    {{ $item->user->name }}
</td>

<td>
    <span class="badge bg-{{ $item->color }}">
        <i class="{{ $item->icono }}"></i>
        {{ $item->modulo }}
    </span>
</td>

<td>
    <span class="badge bg-{{ $item->color }}">
        {{ $item->accion }}
    </span>
</td>

<td>
    {{ $item->descripcion }}
</td>

<td>
    {{ $item->ip }}
</td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-4">

                                No existen registros.

                            </td>

                        </tr>

                    @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card-footer">

            {{ $bitacoras->links() }}

        </div>

    </div>

</div>

@endsection