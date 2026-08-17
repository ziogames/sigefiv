@extends('layouts.app')

@section('title','Editar Rol')

@section('content')

<div class="container-fluid">

    <div class="row mb-4">

        <div class="col">

            <h3>

                <i class="cil-shield-alt"></i>

                Editar Rol

            </h3>

            <small class="text-body-secondary">

                Administración de permisos

            </small>

        </div>

    </div>


    <form
        action="{{ route('roles.update',$role) }}"
        method="POST">

        @csrf
        @method('PUT')


        {{-- INFORMACIÓN GENERAL --}}

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header">

                <strong>

                    Información General

                </strong>

            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6">

                        <label class="form-label">

                            Nombre del Rol

                        </label>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name',$role->name) }}"
                            class="form-control">

                    </div>

                </div>

            </div>

        </div>


        {{-- PERMISOS --}}

        <div class="card shadow-sm border-0">

            <div class="card-header">

                <strong>

                    Permisos

                </strong>

            </div>


            <div class="card-body">

                @foreach($grupos as $grupo => $permisos)

                    <div class="card mb-4 border">

                        <div class="card-header">

                            <h5 class="mb-0 text-capitalize">

                                <i class="cil-folder-open me-2"></i>

                                {{ ucfirst($grupo) }}

                            </h5>

                        </div>


                        <div class="card-body">

                            <div class="row">

                                @foreach($permisos as $permission)

                                    <div class="col-md-3 mb-3">

                                        <div class="form-check form-switch">

                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                id="perm_{{ $permission->id }}"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"

                                                @checked(
                                                    $role->hasPermissionTo($permission)
                                                )

                                                @disabled(
                                                    $role->name === 'Administrador'
                                                )
                                            >

                                            <label
                                                class="form-check-label"
                                                for="perm_{{ $permission->id }}">

                                                @php

                                                    $accion = explode(
                                                        '.',
                                                        $permission->name
                                                    )[1]
                                                    ?? $permission->name;


                                                    $texto = match($accion){

                                                        'index' =>
                                                            'Ver',

                                                        'create' =>
                                                            'Crear',

                                                        'edit' =>
                                                            'Editar',

                                                        'delete',
                                                        'destroy' =>
                                                            'Eliminar',

                                                        'enviar' =>
                                                            'Enviar alerta',

                                                        'imprimir' =>
                                                            'Imprimir citación',

                                                        default =>
                                                            ucfirst($accion)

                                                    };

                                                @endphp

                                                {{ $texto }}

                                            </label>

                                        </div>

                                    </div>

                                @endforeach

                            </div>

                        </div>

                    </div>

                @endforeach

            </div>


            {{-- PIE DE PERMISOS --}}

            <div class="card-footer text-end">

                @if($role->name === 'Administrador')

                    <div class="alert alert-info text-start">

                        <i class="cil-lock-locked me-2"></i>

                        El rol
                        <strong>Administrador</strong>
                        siempre conserva todos los permisos y no pueden modificarse.

                    </div>

                @endif


                <a
                    href="{{ route('roles.index') }}"
                    class="btn btn-secondary">

                    <i class="cil-arrow-left"></i>

                    Volver

                </a>


                <button
                    type="submit"
                    class="btn btn-primary">

                    <i class="cil-save"></i>

                    Guardar Cambios

                </button>

            </div>

        </div>

    </form>

</div>

@endsection