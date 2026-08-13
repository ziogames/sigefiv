@extends('layouts.app')

@section('title','Editar Categoría')

@section('content')

<div class="card shadow-sm">

    <div class="card-header">

        <h4>

            <i class="cil-pencil"></i>

            Editar Categoría

        </h4>

    </div>

    <div class="card-body">

        <form
            action="{{ route('categorias.update',$categoria) }}"
            method="POST">

            @csrf
            @method('PUT')

            @include('categorias._form')

        </form>

    </div>

</div>

@endsection