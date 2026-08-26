@extends('layouts.app')

@section('title', 'Actividad de Usuarios')

@section('content')

<x-page-title
    title="Actividad de Usuarios"
    icon="cil-chart-line">

</x-page-title>


{{-- ================================================================
     ESTADÍSTICAS
================================================================ --}}

<div class="row g-3 mb-4">

    {{-- TOTAL ACTIVIDADES --}}

    <div class="col-sm-6 col-xl-3">

        <x-card>

            <div class="d-flex align-items-center">

                <div
                    class="rounded-circle bg-primary bg-opacity-10
                           text-primary d-flex align-items-center
                           justify-content-center me-3"
                    style="width:52px;height:52px;">

                    <i class="cil-chart-line fs-4"></i>

                </div>

                <div>

                    <div class="text-body-secondary small">
                        Actividades
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ number_format($totalActividades) }}
                    </div>

                </div>

            </div>

        </x-card>

    </div>


    {{-- USUARIOS ACTIVOS --}}

    <div class="col-sm-6 col-xl-3">

        <x-card>

            <div class="d-flex align-items-center">

                <div
                    class="rounded-circle bg-success bg-opacity-10
                           text-success d-flex align-items-center
                           justify-content-center me-3"
                    style="width:52px;height:52px;">

                    <i class="cil-people fs-4"></i>

                </div>

                <div>

                    <div class="text-body-secondary small">
                        Usuarios activos
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ number_format($usuariosActivos) }}
                    </div>

                </div>

            </div>

        </x-card>

    </div>


    {{-- ACTIVIDADES HOY --}}

    <div class="col-sm-6 col-xl-3">

        <x-card>

            <div class="d-flex align-items-center">

                <div
                    class="rounded-circle bg-info bg-opacity-10
                           text-info d-flex align-items-center
                           justify-content-center me-3"
                    style="width:52px;height:52px;">

                    <i class="cil-calendar fs-4"></i>

                </div>

                <div>

                    <div class="text-body-secondary small">
                        Actividades hoy
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ number_format($actividadesHoy) }}
                    </div>

                </div>

            </div>

        </x-card>

    </div>


    {{-- MÓDULO MÁS UTILIZADO --}}

    <div class="col-sm-6 col-xl-3">

        <x-card>

            <div class="d-flex align-items-center">

                <div
                    class="rounded-circle bg-warning bg-opacity-10
                           text-warning d-flex align-items-center
                           justify-content-center me-3"
                    style="width:52px;height:52px;">

                    <i class="cil-star fs-4"></i>

                </div>

                <div class="text-truncate">

                    <div class="text-body-secondary small">
                        Módulo más utilizado
                    </div>

                    <div
                        class="fs-5 fw-bold text-truncate"
                        title="{{ $moduloMasUtilizado?->modulo ?? '—' }}">

                        {{ $moduloMasUtilizado?->modulo ?? '—' }}

                    </div>

                    @if($moduloMasUtilizado)

                        <small class="text-body-secondary">

                            {{ number_format($moduloMasUtilizado->total) }}
                            accesos

                        </small>

                    @endif

                </div>

            </div>

        </x-card>

    </div>

</div>


{{-- ================================================================
     ACTIVIDAD
================================================================ --}}

<x-card>

    <div class="d-flex flex-column flex-md-row
                justify-content-between
                align-items-md-center
                gap-3
                mb-4">

        <div>

            <h5 class="mb-1">

                <i class="cil-history me-2"></i>

                Actividad reciente

            </h5>

            <p class="text-body-secondary mb-0">

                Registro de navegación y utilización de los módulos
                de SIGEFIV.

            </p>

        </div>

        <div>

            <span class="badge bg-primary">

                {{ number_format($actividades->total()) }}

                resultados

            </span>

        </div>

    </div>


    {{-- ============================================================
         RANKINGS
    ============================================================ --}}

    <div class="row g-4 mb-4">

        {{-- MÓDULOS MÁS UTILIZADOS --}}

        <div class="col-lg-6">

            <div class="card border h-100">

                <div class="card-header bg-body-tertiary">

                    <strong>

                        <i class="cil-chart-line me-2"></i>

                        Módulos más utilizados

                    </strong>

                </div>

                <div class="card-body">

                    @forelse($modulosEstadisticas as $estadistica)

                        @php

                            $maxModulo =
                                $modulosEstadisticas->max('total')
                                ?: 1;

                            $porcentaje =
                                ($estadistica->total / $maxModulo) * 100;

                        @endphp

                        <div class="mb-3">

                            <div class="d-flex justify-content-between mb-1">

                                <span class="fw-semibold">

                                    {{ $estadistica->modulo }}

                                </span>

                                <span class="text-body-secondary">

                                    {{ number_format($estadistica->total) }}

                                </span>

                            </div>

                            <div
                                class="progress"
                                style="height:8px;">

                                <div
                                    class="progress-bar"
                                    role="progressbar"
                                    style="width: {{ $porcentaje }}%;"
                                    aria-valuenow="{{ $porcentaje }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100">
                                </div>

                            </div>

                        </div>

                    @empty

                        <div class="text-center text-body-secondary py-4">

                            No hay datos suficientes.

                        </div>

                    @endforelse

                </div>

            </div>

        </div>


        {{-- USUARIOS MÁS ACTIVOS --}}

        <div class="col-lg-6">

            <div class="card border h-100">

                <div class="card-header bg-body-tertiary">

                    <strong>

                        <i class="cil-people me-2"></i>

                        Usuarios más activos

                    </strong>

                </div>

                <div class="card-body">

                    @forelse($usuariosEstadisticas as $estadistica)

                        @if($estadistica->user)

                            <div
                                class="d-flex align-items-center
                                       justify-content-between
                                       mb-3">

                                <div
                                    class="d-flex align-items-center
                                           min-width-0">

                                    <img
                                        src="{{ $estadistica->user->avatar }}"
                                        alt="{{ $estadistica->user->name }}"
                                        class="rounded-circle me-2"
                                        width="38"
                                        height="38"
                                        style="object-fit:cover;">

                                    <div class="text-truncate">

                                        <div class="fw-semibold text-truncate">

                                            {{ $estadistica->user->name }}

                                        </div>

                                        <small class="text-body-secondary">

                                            {{ $estadistica->user->email }}

                                        </small>

                                    </div>

                                </div>

                                <span
                                    class="badge bg-primary ms-2">

                                    {{ number_format($estadistica->total) }}

                                </span>

                            </div>

                        @endif

                    @empty

                        <div class="text-center text-body-secondary py-4">

                            No hay datos suficientes.

                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         FILTROS
    ============================================================ --}}

    <div class="card border mb-4">

        <div class="card-header bg-body-tertiary">

            <div class="d-flex align-items-center">

                <i class="cil-filter me-2"></i>

                <strong>
                    Filtros de actividad
                </strong>

            </div>

        </div>


        <div class="card-body">

            <form
                method="GET"
                action="{{ route('actividad.index') }}">

                <div class="row g-3">


                    {{-- BÚSQUEDA --}}

                    <div class="col-12">

                        <label class="form-label">
                            Buscar
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">

                                <i class="cil-search"></i>

                            </span>

                            <input
                                type="text"
                                name="buscar"
                                value="{{ $buscar }}"
                                class="form-control"
                                placeholder="Usuario, correo, módulo, acción o ruta..."
                            >

                        </div>

                    </div>


                    {{-- USUARIO --}}

                    <div class="col-md-6 col-lg-3">

                        <label class="form-label">
                            Usuario
                        </label>

                        <select
                            name="usuario"
                            class="form-select">

                            <option value="">
                                Todos los usuarios
                            </option>

                            @foreach($usuarios as $usuario)

                                <option
                                    value="{{ $usuario->id }}"
                                    {{ (string) $usuarioId === (string) $usuario->id ? 'selected' : '' }}
                                >

                                    {{ $usuario->name }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- MÓDULO --}}

                    <div class="col-md-6 col-lg-3">

                        <label class="form-label">
                            Módulo
                        </label>

                        <select
                            name="modulo"
                            class="form-select">

                            <option value="">
                                Todos los módulos
                            </option>

                            @foreach($modulos as $nombreModulo)

                                <option
                                    value="{{ $nombreModulo }}"
                                    {{ $modulo === $nombreModulo ? 'selected' : '' }}
                                >

                                    {{ $nombreModulo }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- ACCIÓN --}}

                    <div class="col-md-6 col-lg-3">

                        <label class="form-label">
                            Acción
                        </label>

                        <select
                            name="accion"
                            class="form-select">

                            <option value="">
                                Todas las acciones
                            </option>

                            @foreach($acciones as $nombreAccion)

                                <option
                                    value="{{ $nombreAccion }}"
                                    {{ $accion === $nombreAccion ? 'selected' : '' }}
                                >

                                    {{ $nombreAccion }}

                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- FECHA DESDE --}}

                    <div class="col-md-6 col-lg-3">

                        <label class="form-label">
                            Desde
                        </label>

                        <input
                            type="date"
                            name="fecha_desde"
                            value="{{ $fechaDesde }}"
                            class="form-control">

                    </div>


                    {{-- FECHA HASTA --}}

                    <div class="col-md-6 col-lg-3">

                        <label class="form-label">
                            Hasta
                        </label>

                        <input
                            type="date"
                            name="fecha_hasta"
                            value="{{ $fechaHasta }}"
                            class="form-control">

                    </div>


                    {{-- BOTONES --}}

                    <div class="col-12">

                        <div class="d-flex flex-wrap gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary">

                                <i class="cil-filter me-1"></i>

                                Aplicar filtros

                            </button>


                            <a
                                href="{{ route('actividad.index') }}"
                                class="btn btn-outline-secondary">

                                <i class="cil-reload me-1"></i>

                                Limpiar filtros

                            </a>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- ============================================================
         RESUMEN DE FILTROS
    ============================================================ --}}

    @if(
        $buscar ||
        $usuarioId ||
        $modulo ||
        $accion ||
        $fechaDesde ||
        $fechaHasta
    )

        <div class="alert alert-info d-flex align-items-center mb-4">

            <i class="cil-info me-2"></i>

            <div>

                <strong>Filtros activos:</strong>

                {{ number_format($actividades->total()) }}

                resultado(s) encontrado(s).

            </div>

        </div>

    @endif


    {{-- ============================================================
         TABLA
    ============================================================ --}}

    <x-table bordered hover responsive>

        <thead class="table-light">

            <tr>

                <th width="70">
                    ID
                </th>

                <th>
                    Usuario
                </th>

                <th>
                    Módulo
                </th>

                <th>
                    Acción
                </th>

                <th>
                    Ruta
                </th>

                <th>
                    IP
                </th>

                <th width="180">
                    Fecha
                </th>

            </tr>

        </thead>

        <tbody>

            @forelse($actividades as $actividad)

                <tr>

                    <td>
                        {{ $actividad->id }}
                    </td>


                    <td>

                        @if($actividad->user)

                            <div class="d-flex align-items-center">

                                <img
                                    src="{{ $actividad->user->avatar }}"
                                    alt="{{ $actividad->user->name }}"
                                    class="rounded-circle me-2"
                                    width="38"
                                    height="38"
                                    style="object-fit:cover;">

                                <div>

                                    <div class="fw-semibold">

                                        {{ $actividad->user->name }}

                                    </div>

                                    <small class="text-body-secondary">

                                        {{ $actividad->user->email }}

                                    </small>

                                </div>

                            </div>

                        @else

                            <span class="text-body-secondary">
                                Usuario eliminado
                            </span>

                        @endif

                    </td>


                    <td>

                        <span class="badge bg-info">

                            {{ $actividad->modulo }}

                        </span>

                    </td>


                    <td>

                        <span
                            class="badge bg-{{ $actividad->color }}">

                            <i
                                class="{{ $actividad->icono }} me-1">
                            </i>

                            {{ $actividad->accion }}

                        </span>

                    </td>


                    <td>

                        <code>
                            /{{ $actividad->ruta }}
                        </code>

                    </td>


                    <td>

                        <span class="text-body-secondary">

                            {{ $actividad->ip ?? '—' }}

                        </span>

                    </td>


                    <td>

                        <div class="fw-semibold">

                            {{ $actividad->created_at->format('d/m/Y') }}

                        </div>

                        <small class="text-body-secondary">

                            {{ $actividad->created_at->format('H:i:s') }}

                        </small>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="7"
                        class="text-center py-5">

                        <i
                            class="cil-chart-line"
                            style="font-size:50px">
                        </i>

                        <div class="mt-3">

                            <h5>
                                No hay actividades registradas
                            </h5>

                            <p class="text-body-secondary mb-0">

                                No existen actividades que coincidan
                                con los filtros seleccionados.

                            </p>

                        </div>

                    </td>

                </tr>

            @endforelse

        </tbody>

    </x-table>


    {{-- PAGINACIÓN --}}

    @if($actividades->hasPages())

        <div class="mt-4">

            {{ $actividades->links() }}

        </div>

    @endif

</x-card>

@endsection