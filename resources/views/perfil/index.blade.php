@extends('layouts.app')

@section('title','Mi Cuenta')

@section('content')

<x-page-title
    title="Mi Cuenta"
    icon="cil-user">

</x-page-title>

<div class="row">

    <div class="col-lg-4">

        @include('perfil.foto')

        @include('perfil.permisos')

    </div>

    <div class="col-lg-8">

        @include('perfil.informacion')

        @include('perfil.seguridad')

        @include('perfil.actividad')

    </div>

</div>

@endsection