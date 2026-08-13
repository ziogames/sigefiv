@extends('layouts.app')

@section('title', 'Movimientos')


{{-- =========================================================
     CSS ESPECÍFICO DE MOVIMIENTOS
     ========================================================= --}}

@push('css')

    <link
        rel="stylesheet"
        href="{{ asset('css/movimientos.css') }}"
    >

@endpush


@section('content')

<div class="container-fluid movimientos-page">


    {{-- =====================================================
         ENCABEZADO
         ===================================================== --}}

    @include('movimientos._header')


    {{-- =====================================================
         FILTROS
         ===================================================== --}}

    @include('movimientos._filtros')


    {{-- =====================================================
         ESTADÍSTICAS
         ===================================================== --}}

    @include('movimientos._estadisticas')


    {{-- =====================================================
         GRÁFICOS
         ===================================================== --}}

    @include('movimientos._graficos')


    {{-- =====================================================
         TABLA
         ===================================================== --}}

    @include('movimientos._tabla')


</div>

@endsection


{{-- =========================================================
     JAVASCRIPT ESPECÍFICO DE MOVIMIENTOS
     ========================================================= --}}

@push('scripts')

    @include('movimientos._scripts')

@endpush