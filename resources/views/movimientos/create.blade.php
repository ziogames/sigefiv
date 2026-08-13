@extends('layouts.app')

@section('title','Nuevo Movimiento')

@section('content')

<div class="container-fluid">

    {{-- Encabezado --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">
                <i class="cil-transfer"></i>
                Nuevo Movimiento
            </h2>

            <p class="text-body-secondary mb-0">
                Registrar una nueva operación contable
            </p>
        </div>

        <div class="text-end">

            <div class="small text-body-secondary">
                Número de movimiento
            </div>

            <div class="fw-bold fs-5">
                {{ $numero ?? 'Se generará automáticamente' }}
            </div>

        </div>

    </div>


    {{-- Período activo --}}
{{-- Período activo --}}
@if($periodoActual)

    <div class="card border-0 shadow-sm mb-4 overflow-hidden">

        <div class="card-body py-3 px-4">

            <div class="d-flex align-items-center justify-content-between">

                <div class="d-flex align-items-center">

                    <div
                        class="d-flex align-items-center justify-content-center me-3"
                        style="
                            width: 46px;
                            height: 46px;
                            border-radius: 50%;
                            background: rgba(25, 135, 84, 0.15);
                            color: #20c997;
                            font-size: 22px;
                        "
                    >
                        ●
                    </div>

                    <div>

                        <div
                            class="small text-uppercase fw-semibold"
                            style="letter-spacing: .08em;"
                        >
                            Período activo
                        </div>

                        <div class="fs-4 fw-bold">
                            {{ $periodoActual->nombre }}
                            {{ $periodoActual->anio }}
                        </div>

                    </div>

                </div>


                <div class="text-end">

                    <div class="small text-body-secondary">
                        Estado
                    </div>

                    <span class="badge rounded-pill bg-success px-3 py-2">
                        ● Abierto
                    </span>

                </div>

            </div>


            <div class="mt-3 pt-3 border-top">

                <small class="text-body-secondary">
                    <i class="cil-calendar"></i>
                    Los movimientos solo pueden registrarse dentro del período
                    <strong>
                        {{ $periodoActual->nombre }}
                        {{ $periodoActual->anio }}
                    </strong>.
                </small>

            </div>

        </div>

    </div>

@else

    <div class="alert alert-danger shadow-sm">

        <strong>
            ⚠️ No existe un período contable abierto.
        </strong>

        <div>
            No es posible registrar movimientos hasta abrir un período.
        </div>

    </div>

@endif


    {{-- Formulario --}}
    <div class="card shadow-sm border-0">

        <div class="card-header bg-body">

            <h5 class="mb-0 fw-semibold">
                Datos del movimiento
            </h5>

        </div>


        <form
            action="{{ route('movimientos.store') }}"
            method="POST"
            enctype="multipart/form-data">

            @csrf


            <div class="card-body">

                @include('movimientos.partials.form', [
                    'movimiento' => new \App\Models\Movimiento()
                ])

            </div>


            <div class="card-footer bg-body border-top d-flex justify-content-end gap-2">

                <a
                    href="{{ route('movimientos.index') }}"
                    class="btn btn-secondary">

                    <i class="cil-arrow-left"></i>
                    Cancelar

                </a>


                <button
                    type="submit"
                    class="btn btn-primary">

                    <i class="cil-save"></i>
                    Guardar Movimiento

                </button>

            </div>

        </form>

    </div>

</div>

@endsection


@push('js')

<script>

document.addEventListener('DOMContentLoaded', function () {

    const tipo = document.getElementById('tipo');

    const categoria = document.getElementById('categoria');

    if (!tipo || !categoria) {
        return;
    }


    function filtrarCategorias(){

        const seleccionado = tipo.value;

        let primera = true;

        categoria.querySelectorAll('option').forEach(function(opcion){

            if(opcion.dataset.tipo === seleccionado){

                opcion.hidden = false;

                if(primera){

                    opcion.selected = true;

                    primera = false;

                }

            }else{

                opcion.hidden = true;

            }

        });

    }


    filtrarCategorias();


    tipo.addEventListener(
        'change',
        filtrarCategorias
    );


    const info = document.getElementById('infoTipo');


    function actualizarTitulo(){

        if (!info) {
            return;
        }


        if(tipo.value === "Ingreso"){

            info.className = "alert alert-success";

            info.innerHTML =
                "<strong>Ingreso</strong> - Registrará dinero que entra a la organización.";

        }else{

            info.className = "alert alert-danger";

            info.innerHTML =
                "<strong>Egreso</strong> - Registrará dinero que sale de la organización.";

        }

    }


    actualizarTitulo();


    tipo.addEventListener(
        'change',
        actualizarTitulo
    );

});

</script>

@endpush