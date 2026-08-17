@extends('layouts.app')

@section('title', 'Detalle de Asamblea')

@section('content')

    <div class="container-fluid">

        {{-- Encabezado --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div class="d-flex align-items-center">

                <a href="{{ route('asambleas.index') }}" class="btn btn-light me-3">
                    <i class="cil-arrow-left"></i>
                </a>

                <div>

                    <h1 class="h3 mb-1">
                        <i class="cil-calendar me-2"></i>
                        Detalle de Asamblea
                    </h1>

                    <p class="text-body-secondary mb-0">
                        Revisión de la convocatoria.
                    </p>

                </div>

            </div>


            <div class="d-flex gap-2">

                <form method="POST" action="{{ route('asambleas.enviar', $asamblea) }}"
                    onsubmit="return confirm('¿Deseas enviar esta convocatoria a todos los dispositivos registrados?');"
                    class="d-inline">

                    @csrf

                    <button type="submit" class="btn btn-primary">
                        <i class="cil-send me-1"></i>

                    </button>

                </form>
                {{-- PREVISUALIZAR CITACIÓN --}}
                <a
    href="{{ route('asambleas.citacion', $asamblea) }}"
    class="btn btn-outline-info"
    target="_blank"
    title="Previsualizar citación"
>
    <i class="cil-info"></i>
</a>

                {{-- IMPRIMIR CITACIONES --}}
                <a href="{{ route('asambleas.imprimir', $asamblea) }}" class="btn btn-outline-secondary" target="_blank"  title="Imprimir citación">
                    <i class="cil-print me-1"></i>

                </a>


                @can('asambleas.edit')
                    <a href="{{ route('asambleas.edit', $asamblea) }}" class="btn btn-outline-primary"  title="Editar citación">
                        <i class="cil-pencil me-1"></i>

                    </a>
                @endcan

            </div>

        </div>


        {{-- Mensaje --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">

                <i class="cil-check-circle me-2"></i>

                {{ session('success') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>

            </div>
        @endif


        <div class="row g-4">


            {{-- Información de la convocatoria --}}
            <div class="col-lg-8">

                <div class="card shadow-sm">

                    <div class="card-header">

                        <strong>
                            <i class="cil-info me-2"></i>
                            Datos de la convocatoria
                        </strong>

                    </div>


                    <div class="card-body">


                        {{-- Título --}}
                        <div class="mb-4">

                            <div class="text-body-secondary small">
                                Título
                            </div>

                            <h2 class="h4 mb-0 mt-1">
                                {{ $asamblea->titulo }}
                            </h2>

                        </div>


                        {{-- Tipo --}}
                        <div class="mb-4">

                            <div class="text-body-secondary small">
                                Tipo de asamblea
                            </div>

                            <div class="mt-1">

                                <span class="badge bg-secondary">
                                    {{ ucfirst($asamblea->tipo) }}
                                </span>

                            </div>

                        </div>


                        {{-- Convoca --}}
                        <div class="mb-4">

                            <div class="text-body-secondary small">
                                Convoca
                            </div>

                            <div class="fs-5 fw-semibold mt-1">
                                {{ $asamblea->convoca }}
                            </div>

                        </div>


                        {{-- Sector / Grupo / Mz / Lote --}}
                        @if ($asamblea->sector || $asamblea->grupo || $asamblea->manzana || $asamblea->lote)

                            <div class="mb-4">

                                <div class="text-body-secondary small mb-2">
                                    Ubicación / referencia
                                </div>

                                <div class="d-flex flex-wrap gap-2">

                                    @if ($asamblea->sector)
                                        <span class="badge bg-light text-dark border">
                                            Sector {{ $asamblea->sector }}
                                        </span>
                                    @endif


                                    @if ($asamblea->grupo)
                                        <span class="badge bg-light text-dark border">
                                            Grupo {{ $asamblea->grupo }}
                                        </span>
                                    @endif


                                    @if ($asamblea->manzana)
                                        <span class="badge bg-light text-dark border">
                                            Mz. {{ $asamblea->manzana }}
                                        </span>
                                    @endif


                                    @if ($asamblea->lote)
                                        <span class="badge bg-light text-dark border">
                                            Lote {{ $asamblea->lote }}
                                        </span>
                                    @endif

                                </div>

                            </div>

                        @endif


                        {{-- Fecha y lugar --}}
                        <div class="row">


                            {{-- Fecha --}}
                            <div class="col-md-6 mb-4">

                                <div class="text-body-secondary small">
                                    Fecha
                                </div>

                                <div class="fs-5 mt-1">

                                    <i class="cil-calendar me-2"></i>

                                    {{ $asamblea->fecha->format('d/m/Y') }}

                                </div>

                            </div>


                            {{-- Lugar --}}
                            <div class="col-md-6 mb-4">

                                <div class="text-body-secondary small">
                                    Lugar
                                </div>

                                <div class="fs-5 mt-1">

                                    <i class="cil-location-pin me-2"></i>

                                    {{ $asamblea->lugar }}

                                </div>

                            </div>

                        </div>


                        {{-- Citaciones --}}
                        <div class="mb-4">

                            <div class="text-body-secondary small mb-2">
                                Horarios de citación
                            </div>

                            <div class="row g-3">


                                @if ($asamblea->primera_citacion)
                                    <div class="col-md-6">

                                        <div class="border rounded p-3">

                                            <div class="text-body-secondary small">
                                                Primera citación
                                            </div>

                                            <div class="fs-4 fw-semibold">

                                                <i class="cil-clock me-2"></i>

                                                {{ $asamblea->primera_citacion->format('H:i') }}

                                            </div>

                                        </div>

                                    </div>
                                @endif


                                @if ($asamblea->segunda_citacion)
                                    <div class="col-md-6">

                                        <div class="border rounded p-3">

                                            <div class="text-body-secondary small">
                                                Segunda citación
                                            </div>

                                            <div class="fs-4 fw-semibold">

                                                <i class="cil-clock me-2"></i>

                                                {{ $asamblea->segunda_citacion->format('H:i') }}

                                            </div>

                                        </div>

                                    </div>
                                @endif


                            </div>

                        </div>


                        {{-- Hora principal --}}
                        @if ($asamblea->hora)
                            <div class="mb-4">

                                <div class="text-body-secondary small">
                                    Hora principal
                                </div>

                                <div class="fs-5 mt-1">

                                    <i class="cil-clock me-2"></i>

                                    {{ $asamblea->hora->format('H:i') }}

                                </div>

                            </div>
                        @endif


                        {{-- Texto de convocatoria --}}
                        <div>

                            <div class="text-body-secondary small mb-2">
                                Texto de la convocatoria
                            </div>


                            @if ($asamblea->descripcion)
                                <div class="p-3 bg-body-tertiary rounded">
                                    {!! nl2br(e($asamblea->descripcion)) !!}
                                </div>
                            @else
                                <p class="text-body-secondary mb-0">
                                    No se registró un texto de convocatoria.
                                </p>
                            @endif

                        </div>


                    </div>

                </div>


                {{-- Agenda --}}
                <div class="card shadow-sm mt-4">

                    <div class="card-header">

                        <strong>
                            <i class="cil-list me-2"></i>
                            Agenda de la asamblea
                        </strong>

                    </div>


                    <div class="card-body">

                        @if ($asamblea->agendas->count())

                            <div class="list-group list-group-flush">

                                @foreach ($asamblea->agendas as $agenda)
                                    <div class="list-group-item px-0">

                                        <div class="d-flex align-items-start">

                                            <span class="badge bg-primary me-3" style="min-width:32px;">
                                                {{ $agenda->numero }}
                                            </span>


                                            <div class="flex-grow-1">
                                                {!! nl2br(e($agenda->descripcion)) !!}
                                            </div>

                                        </div>

                                    </div>
                                @endforeach

                            </div>
                        @else
                            <div class="text-center py-4">

                                <i class="cil-list" style="font-size:2.5rem;"></i>

                                <h5 class="mt-3">
                                    No hay puntos de agenda
                                </h5>

                                <p class="text-body-secondary mb-0">
                                    Esta asamblea todavía no tiene una agenda registrada.
                                </p>

                            </div>

                        @endif

                    </div>

                </div>

            </div>


            {{-- Panel lateral --}}
            <div class="col-lg-4">


                {{-- Estado --}}
                <div class="card shadow-sm mb-4">

                    <div class="card-header">

                        <strong>
                            Estado
                        </strong>

                    </div>


                    <div class="card-body text-center">

                        @if ($asamblea->estado === 'publicada')
                            <span class="badge bg-success fs-6">
                                Publicada
                            </span>

                            <p class="text-body-secondary mt-3 mb-0">
                                Esta convocatoria está publicada.
                            </p>
                        @elseif($asamblea->estado === 'cancelada')
                            <span class="badge bg-danger fs-6">
                                Cancelada
                            </span>

                            <p class="text-body-secondary mt-3 mb-0">
                                Esta convocatoria fue cancelada.
                            </p>
                        @else
                            <span class="badge bg-secondary fs-6">
                                Borrador
                            </span>

                            <p class="text-body-secondary mt-3 mb-0">
                                Esta convocatoria todavía no ha sido publicada.
                            </p>
                        @endif

                    </div>

                </div>


                {{-- Importancia --}}
                <div class="card shadow-sm mb-4">

                    <div class="card-header">

                        <strong>
                            Importancia
                        </strong>

                    </div>


                    <div class="card-body text-center">

                        @if ($asamblea->importancia === 'urgente')
                            <span class="badge bg-danger fs-6">
                                Urgente
                            </span>
                        @elseif($asamblea->importancia === 'importante')
                            <span class="badge bg-warning text-dark fs-6">
                                Importante
                            </span>
                        @else
                            <span class="badge bg-info fs-6">
                                Normal
                            </span>
                        @endif

                    </div>

                </div>


                {{-- Notificación --}}
                @can('asambleas.enviar')

                    @if ($asamblea->estado === 'borrador')
                        <div class="card shadow-sm mb-4">

                            <div class="card-header">

                                <strong>
                                    <i class="cil-bell me-2"></i>
                                    Notificación
                                </strong>

                            </div>


                            <div class="card-body">

                                <p class="text-body-secondary">

                                    Cuando la convocatoria esté revisada,
                                    podrás enviarla como alerta Push a los
                                    vecinos con las notificaciones activadas.

                                </p>







                            </div>

                        </div>
                    @endif

                @endcan


                {{-- Creador --}}
                <div class="card shadow-sm">

                    <div class="card-header">

                        <strong>
                            Creada por
                        </strong>

                    </div>


                    <div class="card-body">

                        @if ($asamblea->creador)
                            <div class="fw-semibold">
                                {{ $asamblea->creador->name }}
                            </div>

                            <div class="text-body-secondary small">
                                {{ $asamblea->created_at->format('d/m/Y H:i') }}
                            </div>
                        @else
                            <span class="text-body-secondary">
                                Usuario no disponible
                            </span>
                        @endif

                    </div>

                </div>


            </div>

        </div>

    </div>

@endsection
