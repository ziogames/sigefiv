@extends('layouts.app')

@section('title','Nueva Categoría')

@section('content')

<div class="card shadow-sm">

    <div class="card-header">

        <h4>

            <i class="cil-folder-open"></i>

            Nueva Categoría

        </h4>

    </div>

    <div class="card-body">

        <form
            action="{{ route('categorias.store') }}"
            method="POST">

            @csrf

            @include('categorias._form')

        </form>

    </div>

</div>

@endsection