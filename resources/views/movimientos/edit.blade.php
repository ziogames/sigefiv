@extends('layouts.app')

@section('title','Editar Movimiento')

@section('content')

<div class="card shadow-sm border-0">

    <div class="card-header">

        <h4 class="mb-0">

            <i class="cil-pencil"></i>

            Editar Movimiento

        </h4>

    </div>

    <form
        action="{{ route('movimientos.update',$movimiento) }}"
        method="POST"
        enctype="multipart/form-data">

        @csrf

        @method('PUT')

        <div class="card-body">

            @include('movimientos.partials.form')

        </div>

        <div class="card-footer text-end">

            <a
                href="{{ route('movimientos.index') }}"
                class="btn btn-secondary">

                Cancelar

            </a>

            <button
                class="btn btn-primary">

                Actualizar

            </button>

        </div>

    </form>

</div>

@endsection