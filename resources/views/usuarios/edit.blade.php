@extends('layouts.app')

@section('title','Editar Usuario')

@section('content')

<x-page-title
    title="Editar Usuario"
    icon="cil-pencil">

</x-page-title>

<x-card
    title="Información del Usuario"
    icon="cil-user">

    <form
        action="{{ route('usuarios.update',$usuario) }}"
        method="POST">

        @csrf
        @method('PUT')

        @include('usuarios._form')

    </form>

</x-card>

@endsection