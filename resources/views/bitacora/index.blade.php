@extends('layouts.app')

@section('title', 'Bitácora')

@section('content')
<div class="container-fluid px-4">

    <!-- HEADER / TÍTULO -->
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2 class="fw-bold mb-1">
                <i class="cil-notes me-2 text-primary"></i> Bitácora del Sistema
            </h2>
            <p class="text-body-secondary mb-0">
                Registro de actividades y auditoría de los usuarios en tiempo real.
            </p>
        </div>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS (KPIS) -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-body-secondary text-uppercase fw-semibold small mb-1">Registros Totales</div>
                    <div class="fs-3 fw-bold">{{ number_format($totalRegistros) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="text-body-secondary text-uppercase fw-semibold small mb-1">Usuarios Activos</div>
                    <div class="fs-3 fw-bold">{{ number_format($totalUsuarios) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="text-body-secondary text-uppercase fw-semibold small mb-1">Módulos</div>
                    <div class="fs-3 fw-bold">{{ number_format($totalModulos) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="text-body-secondary text-uppercase fw-semibold small mb-1">Acciones Hoy</div>
                    <div class="fs-3 fw-bold">{{ number_format($accionesHoy) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- TARJETA PRINCIPAL CON FILTROS Y TABLA -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header py-3">
            <form method="GET" action="">
                <div class="row g-3 align-items-center">
                    
                    <!-- Búsqueda por descripción -->
                    <div class="col-12 col-md-4 col-xl-3">
                        <div class="input-group">
                            <span class="input-group-text border-end-0"><i class="cil-search"></i></span>
                            <input 
                                type="text" 
                                name="buscar" 
                                value="{{ request('buscar') }}" 
                                class="form-control border-start-0 ps-0" 
                                placeholder="Buscar descripción...">
                        </div>
                    </div>

                    <!-- Filtro por Usuario -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <select name="usuario" class="form-select">
                            <option value="">Todos los usuarios</option>
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" @selected(request('usuario') == $usuario->id)>
                                    {{ $usuario->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtro por Módulo -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <select name="modulo" class="form-select">
                            <option value="">Todos los módulos</option>
                            @foreach($modulos as $modulo)
                                <option value="{{ $modulo }}" @selected(request('modulo') == $modulo)>
                                    {{ $modulo }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Rango de Fechas: Desde -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <input 
                            type="date" 
                            name="desde" 
                            value="{{ request('desde') }}" 
                            class="form-control" 
                            title="Fecha desde">
                    </div>

                    <!-- Rango de Fechas: Hasta -->
                    <div class="col-12 col-md-4 col-xl-2">
                        <input 
                            type="date" 
                            name="hasta" 
                            value="{{ request('hasta') }}" 
                            class="form-control" 
                            title="Fecha hasta">
                    </div>

                    <!-- Botones de Acción -->
                    <div class="col-12 col-md-4 col-xl-1 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1" title="Filtrar resultados">
                            <i class="cil-filter"></i>
                        </button>
                        @if(request()->anyFilled(['buscar', 'usuario', 'modulo', 'desde', 'hasta']))
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary" title="Limpiar filtros">
                                <i class="cil-reload"></i>
                            </a>
                        @endif
                    </div>

                </div>
            </form>
        </div>

        <!-- TABLA DE DATOS -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark text-uppercase small">
                        <tr>
                            <th class="ps-3 border-0">Fecha</th>
                            <th class="border-0">Usuario</th>
                            <th class="border-0">Módulo</th>
                            <th class="border-0">Acción</th>
                            <th class="border-0">Descripción</th>
                            <th class="pe-3 border-0">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($bitacoras as $item)
                            <tr>
                                <td class="ps-3 text-nowrap">
                                    <span class="fw-medium">{{ $item->created_at->format('d/m/Y') }}</span>
                                    <small class="text-body-secondary d-block">{{ $item->created_at->format('H:i:s') }}</small>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $item->user->name ?? 'Sistema' }}</div>
                                    <small class="text-body-secondary">{{ $item->user->email ?? '' }}</small>
                                </td>
                                <td>
                                    <!-- Badge con borde adaptativo en vez de fondo claro forzado -->
                                    <span class="badge border border-secondary text-body px-2 py-1">
                                        <i class="{{ $item->icono ?? 'cil-folder' }} me-1 text-primary"></i>
                                        {{ $item->modulo }}
                                    </span>
                                </td>
                                <td>
                                    <!-- text-bg-* asegura que el texto sea blanco o negro automáticamente según el contraste del fondo -->
                                    <span class="badge text-bg-{{ $item->color ?? 'secondary' }} px-2 py-1">
                                        {{ $item->accion }}
                                    </span>
                                </td>
                                <td class="text-wrap" style="max-width: 350px;">
                                    {{ $item->descripcion }}
                                </td>
                                <td class="pe-3">
                                    <code class="text-body-secondary small">{{ $item->ip }}</code>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-body-secondary">
                                    <div class="mb-2">
                                        <i class="cil-search fs-2 opacity-50"></i>
                                    </div>
                                    <span class="fw-medium">No se encontraron registros de bitácora con los filtros aplicados.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINACIÓN -->
        <div class="card-footer py-3 d-flex justify-content-between align-items-center">
            <div class="text-body-secondary small">
                Mostrando registros del sistema
            </div>
            <div>
                {{ $bitacoras->withQueryString()->links() }}
            </div>
        </div>
    </div>

</div>
@endsection