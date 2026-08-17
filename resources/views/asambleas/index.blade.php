@extends('layouts.app')

@section('title', 'Asambleas')

@section('content')

<div class="container-fluid">


    {{-- ================================================================
         ENCABEZADO
         ================================================================ --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="h3 mb-1">

                <i class="cil-calendar me-2"></i>

                Asambleas

            </h1>


            <p class="text-body-secondary mb-0">

                Gestión de convocatorias y asambleas.

            </p>

        </div>


        @can('asambleas.create')

            <a
                href="{{ route('asambleas.create') }}"
                class="btn btn-primary"
            >

                <i class="cil-plus me-1"></i>

                Nueva asamblea

            </a>

        @endcan

    </div>



    {{-- ================================================================
         MENSAJE DE ÉXITO
         ================================================================ --}}

    @if(session('success'))

        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >

            <i class="cil-check-circle me-2"></i>

            {{ session('success') }}


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif



    {{-- ================================================================
         MENSAJE DE ERROR
         ================================================================ --}}

    @if(session('error'))

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >

            <i class="cil-warning me-2"></i>

            {{ session('error') }}


            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    @endif



    {{-- ================================================================
         TABLA
         ================================================================ --}}

    <div class="card shadow-sm">

        <div class="card-header">

            <strong>

                <i class="cil-list me-2"></i>

                Convocatorias registradas

            </strong>

        </div>


        <div class="card-body p-0">


            @if($asambleas->count())


                <div class="table-responsive">

                    <table
                        class="table table-hover align-middle mb-0"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Fecha
                                </th>

                                <th>
                                    Convocatoria
                                </th>

                                <th>
                                    Convoca
                                </th>

                                <th>
                                    Citaciones
                                </th>

                                <th>
                                    Lugar
                                </th>

                                <th>
                                    Importancia
                                </th>

                                <th>
                                    Estado
                                </th>

                                <th class="text-end">
                                    Acciones
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            @foreach($asambleas as $asamblea)

                                <tr>


                                    {{-- ====================================================
                                         FECHA
                                         ==================================================== --}}

                                    <td>

                                        <div class="fw-semibold">

                                            {{ $asamblea->fecha->format('d/m/Y') }}

                                        </div>


                                        <small
                                            class="text-body-secondary"
                                        >

                                            {{ ucfirst($asamblea->tipo) }}

                                        </small>

                                    </td>



                                    {{-- ====================================================
                                         TÍTULO
                                         ==================================================== --}}

                                    <td>

                                        <div class="fw-semibold">

                                            {{ $asamblea->titulo }}

                                        </div>


                                        @if(
                                            $asamblea->sector ||
                                            $asamblea->grupo
                                        )

                                            <small
                                                class="text-body-secondary"
                                            >

                                                @if($asamblea->sector)

                                                    Sector
                                                    {{ $asamblea->sector }}

                                                @endif


                                                @if(
                                                    $asamblea->sector &&
                                                    $asamblea->grupo
                                                )

                                                    ·

                                                @endif


                                                @if($asamblea->grupo)

                                                    Grupo
                                                    {{ $asamblea->grupo }}

                                                @endif

                                            </small>

                                        @endif

                                    </td>



                                    {{-- ====================================================
                                         CONVOCA
                                         ==================================================== --}}

                                    <td>

                                        <span class="fw-semibold">

                                            {{ $asamblea->convoca }}

                                        </span>


                                        @if(
                                            $asamblea->manzana ||
                                            $asamblea->lote
                                        )

                                            <small
                                                class="d-block text-body-secondary"
                                            >

                                                @if($asamblea->manzana)

                                                    Mz.
                                                    {{ $asamblea->manzana }}

                                                @endif


                                                @if(
                                                    $asamblea->manzana &&
                                                    $asamblea->lote
                                                )

                                                    -

                                                @endif


                                                @if($asamblea->lote)

                                                    Lote
                                                    {{ $asamblea->lote }}

                                                @endif

                                            </small>

                                        @endif

                                    </td>



                                    {{-- ====================================================
                                         CITACIONES
                                         ==================================================== --}}

                                    <td>


                                        @if($asamblea->primera_citacion)

                                            <div>

                                                <small
                                                    class="text-body-secondary"
                                                >
                                                    1ra:
                                                </small>


                                                {{ $asamblea->primera_citacion->format('H:i') }}

                                            </div>

                                        @endif


                                        @if($asamblea->segunda_citacion)

                                            <div>

                                                <small
                                                    class="text-body-secondary"
                                                >
                                                    2da:
                                                </small>


                                                {{ $asamblea->segunda_citacion->format('H:i') }}

                                            </div>

                                        @endif


                                    </td>



                                    {{-- ====================================================
                                         LUGAR
                                         ==================================================== --}}

                                    <td>

                                        <i
                                            class="cil-location-pin me-1"
                                        ></i>

                                        {{ $asamblea->lugar }}

                                    </td>



                                    {{-- ====================================================
                                         IMPORTANCIA
                                         ==================================================== --}}

                                    <td>


                                        @if(
                                            $asamblea->importancia ===
                                            'urgente'
                                        )

                                            <span
                                                class="badge bg-danger"
                                            >

                                                Urgente

                                            </span>


                                        @elseif(
                                            $asamblea->importancia ===
                                            'importante'
                                        )

                                            <span
                                                class="badge bg-warning text-dark"
                                            >

                                                Importante

                                            </span>


                                        @else

                                            <span
                                                class="badge bg-info"
                                            >

                                                Normal

                                            </span>

                                        @endif


                                    </td>



                                    {{-- ====================================================
                                         ESTADO
                                         ==================================================== --}}

                                    <td>


                                        @if($asamblea->alerta_enviada)

                                            <span
                                                class="badge bg-success"
                                            >

                                                <i
                                                    class="cil-check me-1"
                                                ></i>

                                                Alerta enviada

                                            </span>


                                        @elseif(
                                            $asamblea->estado ===
                                            'cancelada'
                                        )

                                            <span
                                                class="badge bg-danger"
                                            >

                                                Cancelada

                                            </span>


                                        @else

                                            <span
                                                class="badge bg-secondary"
                                            >

                                                Borrador

                                            </span>

                                        @endif


                                    </td>



                                    {{-- ====================================================
                                         ACCIONES
                                         ==================================================== --}}

                                    <td class="text-end">


                                        <div
                                            class="btn-group"
                                            role="group"
                                        >


                                            {{-- VER --}}

                                            @can('asambleas.index')

                                                <a
        href="{{ route('asambleas.show', $asamblea) }}"
        class="btn btn-sm btn-outline-primary"
        title="Ver detalles de la citación"
    >
       <i class="cil-find-in-page"></i>
      
    </a>

                                            @endcan



                                            {{-- EDITAR --}}

                                            @can('asambleas.edit')

                                                <a
                                                    href="{{ route(
                                                        'asambleas.edit',
                                                        $asamblea
                                                    ) }}"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    title="Editar asamblea"
                                                >

                                                    <i class="cil-pencil"></i>

                                                </a>

                                            @endcan



                                            {{-- ELIMINAR --}}

                                            @can('asambleas.destroy')

                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'asambleas.destroy',
                                                        $asamblea
                                                    ) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('¿Estás seguro de eliminar esta asamblea? Esta acción no se puede deshacer.');"
                                                >

                                                    @csrf

                                                    @method('DELETE')


                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Eliminar asamblea"
                                                    >

                                                        <i
                                                            class="cil-trash"
                                                        ></i>

                                                    </button>

                                                </form>

                                            @endcan


                                        </div>


                                    </td>


                                </tr>

                            @endforeach


                        </tbody>

                    </table>

                </div>



                {{-- ========================================================
                     PAGINACIÓN
                     ======================================================== --}}

                <div class="p-3">

                    {{ $asambleas->links() }}

                </div>


            @else


                {{-- ========================================================
                     SIN ASAMBLEAS
                     ======================================================== --}}

                <div class="text-center py-5">


                    <i
                        class="cil-calendar"
                        style="font-size: 3rem;"
                    ></i>


                    <h5 class="mt-3">

                        No hay convocatorias registradas

                    </h5>


                    <p class="text-body-secondary">

                        Todavía no se ha creado ninguna asamblea.

                    </p>


                    @can('asambleas.create')

                        <a
                            href="{{ route('asambleas.create') }}"
                            class="btn btn-primary"
                        >

                            <i class="cil-plus me-1"></i>

                            Crear primera asamblea

                        </a>

                    @endcan


                </div>


            @endif


        </div>

    </div>


</div>

@endsection