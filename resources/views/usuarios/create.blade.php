@extends('layouts.app')

@section('title','Nuevo Usuario')

@section('content')

<x-page-title
    title="Nuevo Usuario"
    icon="cil-user-follow">

    <x-button
        type="button"
        color="secondary"
        icon="cil-arrow-left"
        onclick="location.href='{{ route('usuarios.index') }}'">

        Volver

    </x-button>

</x-page-title>

<x-card
    title="Información del Usuario"
    icon="cil-user">

    <form
        action="{{ route('usuarios.store') }}"
        method="POST">

        @include('usuarios._form')

    </form>

</x-card>

@endsection