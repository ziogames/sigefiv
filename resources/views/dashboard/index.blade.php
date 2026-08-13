@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    {{-- Encabezado --}}
    @include('dashboard.components.header')

   <div class="mt-4">
        @include('dashboard.components.kpis')
    </div>

<br>
    {{-- Estado del periodo actual --}}
    @include('dashboard.components.estado_periodo')

    {{-- Indicadores principales --}}
 
    {{-- Resumen financiero --}}
    <div class="mt-4">
        @include('dashboard.components.resumen')
    </div>

    {{-- Análisis y gráficos --}}
    <div class="mt-4">
        @include('dashboard.components.analisis')
    </div>

    {{-- Indicadores adicionales --}}
    <div class="mt-4">
        @include('dashboard.components.indicadores')
    </div>
@endsection

@push('scripts')

@endpush