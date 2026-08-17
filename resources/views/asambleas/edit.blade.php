@extends('layouts.app')

@section('title', 'Editar Asamblea')

@section('content')

<div class="container-fluid">

    <div class="d-flex align-items-center mb-4">

        <a
            href="{{ route('asambleas.show', $asamblea) }}"
            class="btn btn-light me-3"
        >
            <i class="cil-arrow-left"></i>
        </a>

        <div>

            <h1 class="h3 mb-1">

                <i class="cil-pencil me-2"></i>

                Editar Asamblea

            </h1>

            <p class="text-body-secondary mb-0">

                Modifica los datos de la convocatoria.

            </p>

        </div>

    </div>


    @if($errors->any())

        <div class="alert alert-danger">

            <strong>

                Revisa los siguientes datos:

            </strong>

            <ul class="mb-0 mt-2">

                @foreach($errors->all() as $error)

                    <li>{{ $error }}</li>

                @endforeach

            </ul>

        </div>

    @endif


    <div class="card shadow-sm">

        <div class="card-header">

            <strong>

                <i class="cil-calendar me-2"></i>

                Datos de la convocatoria

            </strong>

        </div>


        <div class="card-body">

            <form
                method="POST"
                action="{{ route('asambleas.update', $asamblea) }}"
            >

                @csrf

                @method('PUT')


                {{-- ==========================================================
                     Tipo y carácter
                     ========================================================== --}}

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            for="tipo"
                            class="form-label"
                        >

                            Tipo de asamblea

                            <span class="text-danger">*</span>

                        </label>


                        <select
                            id="tipo"
                            name="tipo"
                            class="form-select"
                            required
                        >

                            <option value="">

                                Selecciona el tipo

                            </option>


                            <option
                                value="ordinaria"
                                {{ old('tipo', $asamblea->tipo) === 'ordinaria' ? 'selected' : '' }}
                            >

                                Asamblea ordinaria

                            </option>


                            <option
                                value="extraordinaria"
                                {{ old('tipo', $asamblea->tipo) === 'extraordinaria' ? 'selected' : '' }}
                            >

                                Asamblea extraordinaria

                            </option>

                        </select>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="importancia"
                            class="form-label"
                        >

                            Carácter de la convocatoria

                            <span class="text-danger">*</span>

                        </label>


                        <select
                            id="importancia"
                            name="importancia"
                            class="form-select"
                            required
                        >

                            <option
                                value="normal"
                                {{ old('importancia', $asamblea->importancia) === 'normal' ? 'selected' : '' }}
                            >

                                Normal

                            </option>


                            <option
                                value="importante"
                                {{ old('importancia', $asamblea->importancia) === 'importante' ? 'selected' : '' }}
                            >

                                Importante

                            </option>


                            <option
                                value="urgente"
                                {{ old('importancia', $asamblea->importancia) === 'urgente' ? 'selected' : '' }}
                            >

                                Urgente

                            </option>

                        </select>

                    </div>

                </div>


                {{-- ==========================================================
                     DISEÑO DE CITACIÓN
                     ========================================================== --}}

                <div class="mb-4">

                    <label class="form-label fw-bold">

                        <i class="cil-paint-bucket me-2"></i>

                        Diseño de la citación

                        <span class="text-danger">*</span>

                    </label>


                    <p class="text-body-secondary small mb-3">

                        Selecciona el fondo que se utilizará para mostrar
                        la citación a los vecinos.

                    </p>


                    <div class="row g-3">

                        @for($i = 1; $i <= 7; $i++)

                            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">

                                <label
                                    class="plantilla-card"
                                    for="plantilla_{{ $i }}"
                                >

                                    <input
                                        type="radio"
                                        id="plantilla_{{ $i }}"
                                        name="plantilla_citacion"
                                        value="{{ $i }}"
                                        class="plantilla-radio"

                                        {{ old(
                                            'plantilla_citacion',
                                            $asamblea->plantilla_citacion ?? 1
                                        ) == $i ? 'checked' : '' }}
                                    >


                                    <div
                                        class="plantilla-preview fondo-{{ $i }}"
                                    >

                                        <div class="plantilla-overlay">

                                            <span class="plantilla-check">

                                                ✓

                                            </span>


                                            <strong>

                                                Fondo {{ $i }}

                                            </strong>

                                        </div>

                                    </div>

                                </label>

                            </div>

                        @endfor

                    </div>

                </div>


                {{-- ==========================================================
                     Título
                     ========================================================== --}}

                <div class="mb-3">

                    <label
                        for="titulo"
                        class="form-label"
                    >

                        Título de la convocatoria

                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        id="titulo"
                        name="titulo"
                        class="form-control"
                        value="{{ old('titulo', $asamblea->titulo) }}"
                        maxlength="255"
                        required
                    >

                </div>


                {{-- ==========================================================
                     Convoca
                     ========================================================== --}}

                <div class="mb-3">

                    <label
                        for="convoca"
                        class="form-label"
                    >

                        Convoca

                        <span class="text-danger">*</span>

                    </label>


                    <input
                        type="text"
                        id="convoca"
                        name="convoca"
                        class="form-control"
                        value="{{ old('convoca', $asamblea->convoca) }}"
                        maxlength="255"
                        required
                    >

                </div>


                {{-- ==========================================================
                     Ubicación
                     ========================================================== --}}

                <div class="row">

                    <div class="col-md-3 mb-3">

                        <label
                            for="sector"
                            class="form-label"
                        >

                            Sector

                        </label>


                        <input
                            type="text"
                            id="sector"
                            name="sector"
                            class="form-control"
                            value="{{ old('sector', $asamblea->sector) }}"
                        >

                    </div>


                    <div class="col-md-3 mb-3">

                        <label
                            for="grupo"
                            class="form-label"
                        >

                            Grupo

                        </label>


                        <input
                            type="text"
                            id="grupo"
                            name="grupo"
                            class="form-control"
                            value="{{ old('grupo', $asamblea->grupo) }}"
                        >

                    </div>


                    <div class="col-md-3 mb-3">

                        <label
                            for="manzana"
                            class="form-label"
                        >

                            Manzana

                        </label>


                        <input
                            type="text"
                            id="manzana"
                            name="manzana"
                            class="form-control"
                            value="{{ old('manzana', $asamblea->manzana) }}"
                        >

                    </div>


                    <div class="col-md-3 mb-3">

                        <label
                            for="lote"
                            class="form-label"
                        >

                            Lote

                        </label>


                        <input
                            type="text"
                            id="lote"
                            name="lote"
                            class="form-control"
                            value="{{ old('lote', $asamblea->lote) }}"
                        >

                    </div>

                </div>


                {{-- ==========================================================
                     Fecha y citaciones
                     ========================================================== --}}

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label
                            for="fecha"
                            class="form-label"
                        >

                            Fecha

                            <span class="text-danger">*</span>

                        </label>


                        <input
                            type="date"
                            id="fecha"
                            name="fecha"
                            class="form-control"
                            value="{{ old(
                                'fecha',
                                $asamblea->fecha?->format('Y-m-d')
                            ) }}"
                            required
                        >

                    </div>


                    <div class="col-md-4 mb-3">

                        <label
                            for="primera_citacion"
                            class="form-label"
                        >

                            Primera citación

                            <span class="text-danger">*</span>

                        </label>


                        <input
                            type="time"
                            id="primera_citacion"
                            name="primera_citacion"
                            class="form-control"
                            value="{{ old(
                                'primera_citacion',
                                $asamblea->primera_citacion?->format('H:i')
                            ) }}"
                            required
                        >

                    </div>


                    {{-- ======================================================
                         SEGUNDA CITACIÓN OPCIONAL
                         ====================================================== --}}

                    <div class="col-md-4 mb-3">

                        <label
                            for="segunda_citacion"
                            class="form-label"
                        >

                            Segunda citación

                            <span class="text-body-secondary">
                                (opcional)
                            </span>

                        </label>


                        <input
                            type="time"
                            id="segunda_citacion"
                            name="segunda_citacion"
                            class="form-control"
                            value="{{ old(
                                'segunda_citacion',
                                $asamblea->segunda_citacion?->format('H:i')
                            ) }}"
                        >


                        <div class="form-text">

                            Puedes dejar este campo vacío si la asamblea
                            tendrá una sola citación.

                        </div>

                    </div>

                </div>


                {{-- ==========================================================
                     Hora y lugar
                     ========================================================== --}}

                <div class="row">

                    <div class="col-md-6 mb-3">

                        <label
                            for="hora"
                            class="form-label"
                        >

                            Hora principal de la asamblea

                        </label>


                        <input
                            type="time"
                            id="hora"
                            name="hora"
                            class="form-control"
                            value="{{ old(
                                'hora',
                                $asamblea->hora?->format('H:i')
                            ) }}"
                        >


                        <div class="form-text">

                            Opcional. Las horas oficiales de convocatoria
                            son las de primera y segunda citación.

                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label
                            for="lugar"
                            class="form-label"
                        >

                            Lugar

                            <span class="text-danger">*</span>

                        </label>


                        <input
                            type="text"
                            id="lugar"
                            name="lugar"
                            class="form-control"
                            value="{{ old(
                                'lugar',
                                $asamblea->lugar
                            ) }}"
                            maxlength="255"
                            required
                        >

                    </div>

                </div>


                {{-- ==========================================================
                     Texto
                     ========================================================== --}}

                <div class="mb-4">

                    <label
                        for="descripcion"
                        class="form-label"
                    >

                        Texto de la convocatoria

                    </label>


                    <textarea
                        id="descripcion"
                        name="descripcion"
                        class="form-control"
                        rows="7"
                    >{{ old(
                        'descripcion',
                        $asamblea->descripcion
                    ) }}</textarea>

                </div>


                {{-- ==========================================================
                     AGENDA
                     ========================================================== --}}

                <div class="card border mb-4">

                    <div
                        class="card-header d-flex justify-content-between align-items-center"
                    >

                        <strong>

                            <i class="cil-list me-2"></i>

                            Agenda

                        </strong>


                        <button
                            type="button"
                            id="btnAgregarAgenda"
                            class="btn btn-sm btn-primary"
                        >

                            <i class="cil-plus me-1"></i>

                            Agregar punto

                        </button>

                    </div>


                    <div class="card-body">

                        <p class="text-body-secondary small">

                            Modifica los puntos existentes o agrega nuevos.

                        </p>


                        <div id="agendaContainer">

                            @foreach($asamblea->agendas as $agenda)

                                <div
                                    class="agenda-item border rounded p-3 mb-3"
                                >

                                    <div
                                        class="row align-items-center"
                                    >

                                        <div class="col-auto">

                                            <div
                                                class="agenda-numero badge bg-primary fs-6"
                                                style="min-width:38px;"
                                            >

                                                {{ $loop->iteration }}

                                            </div>

                                        </div>


                                        <div class="col">

                                            <label class="form-label mb-1">

                                                Punto de agenda

                                            </label>


                                            <textarea
                                                name="agenda[]"
                                                class="form-control"
                                                rows="2"
                                                maxlength="1000"
                                            >{{ $agenda->descripcion }}</textarea>

                                        </div>


                                        <div class="col-auto">

                                            <button
                                                type="button"
                                                class="btn btn-outline-danger btnEliminarAgenda"
                                                title="Eliminar punto"
                                            >

                                                <i class="cil-trash"></i>

                                            </button>

                                        </div>

                                    </div>

                                </div>

                            @endforeach

                        </div>


                        <div
                            id="agendaVacia"
                            class="text-center py-4 text-body-secondary"
                            style="{{ $asamblea->agendas->count() ? 'display:none;' : '' }}"
                        >

                            <i
                                class="cil-list"
                                style="font-size:2rem;"
                            ></i>


                            <div class="mt-2">

                                No hay puntos de agenda.

                            </div>


                            <small>

                                Haz clic en "Agregar punto".

                            </small>

                        </div>

                    </div>

                </div>


                {{-- ==========================================================
                     ESTADO
                     ========================================================== --}}

                <div class="mb-4">

                    <label
                        for="estado"
                        class="form-label"
                    >

                        Estado

                        <span class="text-danger">*</span>

                    </label>


                    @if($asamblea->alerta_enviada)

                        <div
                            class="alert alert-success d-flex align-items-center"
                        >

                            <i
                                class="cil-lock-locked me-2"
                            ></i>


                            <div>

                                <strong>

                                    Alerta ya enviada

                                </strong>


                                <div class="small">

                                    Esta asamblea ya fue enviada por Push.

                                    @if($asamblea->alerta_enviada_at)

                                        Fecha:

                                        {{ $asamblea->alerta_enviada_at->format(
                                            'd/m/Y H:i'
                                        ) }}

                                    @endif

                                </div>

                            </div>

                        </div>


                        <input
                            type="hidden"
                            name="estado"
                            value="publicada"
                        >

                    @else

                        <select
                            id="estado"
                            name="estado"
                            class="form-select"
                            required
                        >

                            <option
                                value="borrador"
                                {{ old(
                                    'estado',
                                    $asamblea->estado
                                ) === 'borrador' ? 'selected' : '' }}
                            >

                                Borrador

                            </option>


                            <option
                                value="publicada"
                                {{ old(
                                    'estado',
                                    $asamblea->estado
                                ) === 'publicada' ? 'selected' : '' }}
                            >

                                Publicada

                            </option>


                            <option
                                value="cancelada"
                                {{ old(
                                    'estado',
                                    $asamblea->estado
                                ) === 'cancelada' ? 'selected' : '' }}
                            >

                                Cancelada

                            </option>

                        </select>

                    @endif

                </div>


                {{-- ==========================================================
                     AVISO
                     ========================================================== --}}

                @if($asamblea->alerta_enviada)

                    <div class="alert alert-warning">

                        <i class="cil-warning me-2"></i>

                        <strong>

                            Atención:

                        </strong>

                        esta asamblea ya fue enviada por Push.

                        Los cambios realizados aquí modificarán
                        la información que se muestra al abrir la
                        citación, pero <strong>no enviarán una nueva alerta</strong>.

                    </div>

                @else

                    <div class="alert alert-info">

                        <i class="cil-info me-2"></i>

                        Los cambios se guardarán en la convocatoria.
                        Puedes revisarla antes de enviar la alerta Push.

                    </div>

                @endif


                {{-- ==========================================================
                     BOTONES
                     ========================================================== --}}

                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="{{ route(
                            'asambleas.show',
                            $asamblea
                        ) }}"
                        class="btn btn-secondary"
                    >

                        <i class="cil-x me-1"></i>

                        Cancelar

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        <i class="cil-save me-1"></i>

                        Guardar cambios

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>


<style>

    /*
    |--------------------------------------------------------------------------
    | SELECCIÓN DE PLANTILLAS
    |--------------------------------------------------------------------------
    */

    .plantilla-card {

        display: block;

        position: relative;

        width: 100%;

        cursor: pointer;

    }


    .plantilla-radio {

        position: absolute;

        opacity: 0;

        pointer-events: none;

    }


    /*
    |--------------------------------------------------------------------------
    | VISTA PREVIA
    |--------------------------------------------------------------------------
    */

    .plantilla-preview {

        position: relative;

        width: 100%;

        height: 220px;

        overflow: hidden;

        border-radius: 16px;

        border: 3px solid transparent;

        background-color: #f1f3f5;

        background-position: center;

        background-repeat: no-repeat;

        background-size: cover;

        box-shadow:
            0 4px 12px
            rgba(0, 0, 0, .12);

        transition:
            transform .2s ease,
            border-color .2s ease,
            box-shadow .2s ease;

    }


    .plantilla-card:hover
    .plantilla-preview {

        transform: translateY(-3px);

        box-shadow:
            0 8px 20px
            rgba(0, 0, 0, .18);

    }


    /*
    |--------------------------------------------------------------------------
    | IMÁGENES DE LOS FONDOS
    |--------------------------------------------------------------------------
    */

    .fondo-1 {

        background-image:
            url('/assets/asambleas/fondos/fondo-1.jpg');

    }


    .fondo-2 {

        background-image:
            url('/assets/asambleas/fondos/fondo-2.jpg');

    }


    .fondo-3 {

        background-image:
            url('/assets/asambleas/fondos/fondo-3.jpg');

    }


    .fondo-4 {

        background-image:
            url('/assets/asambleas/fondos/fondo-4.jpg');

    }


    .fondo-5 {

        background-image:
            url('/assets/asambleas/fondos/fondo-5.jpg');

    }


    .fondo-6 {

        background-image:
            url('/assets/asambleas/fondos/fondo-6.jpg');

    }


    .fondo-7 {

        background-image:
            url('/assets/asambleas/fondos/fondo-7.jpg');

    }


    /*
    |--------------------------------------------------------------------------
    | CAPA INFERIOR DEL NOMBRE
    |--------------------------------------------------------------------------
    */

    .plantilla-overlay {

        position: absolute;

        left: 0;

        right: 0;

        bottom: 0;

        display: flex;

        align-items: center;

        gap: 8px;

        padding: 12px;

        color: #ffffff;

        background:
            linear-gradient(
                to top,
                rgba(0, 0, 0, .75),
                rgba(0, 0, 0, 0)
            );

        pointer-events: none;

    }


    /*
    |--------------------------------------------------------------------------
    | CHECK
    |--------------------------------------------------------------------------
    */

    .plantilla-check {

        display: none;

        align-items: center;

        justify-content: center;

        width: 28px;

        height: 28px;

        border-radius: 50%;

        background: #198754;

        color: #ffffff;

        font-weight: 800;

    }


    .plantilla-radio:checked
    + .plantilla-preview {

        border-color: #0d6efd;

        box-shadow:
            0 0 0 4px
            rgba(13, 110, 253, .18),
            0 8px 20px
            rgba(0, 0, 0, .18);

    }


    .plantilla-radio:checked
    + .plantilla-preview
    .plantilla-check {

        display: flex;

    }

</style>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {


        const container =
            document.getElementById(
                'agendaContainer'
            );


        const boton =
            document.getElementById(
                'btnAgregarAgenda'
            );


        const mensajeVacio =
            document.getElementById(
                'agendaVacia'
            );


        function actualizarNumeros() {


            const puntos =
                container.querySelectorAll(
                    '.agenda-item'
                );


            puntos.forEach(
                function (punto, index) {


                    const numero =
                        punto.querySelector(
                            '.agenda-numero'
                        );


                    if (numero) {

                        numero.textContent =
                            index + 1;

                    }

                }
            );


            mensajeVacio.style.display =
                puntos.length === 0
                    ? 'block'
                    : 'none';

        }


        function prepararEliminar(item) {


            const botonEliminar =
                item.querySelector(
                    '.btnEliminarAgenda'
                );


            botonEliminar.addEventListener(
                'click',
                function () {


                    item.remove();


                    actualizarNumeros();

                }
            );

        }


        container
            .querySelectorAll('.agenda-item')
            .forEach(
                function (item) {

                    prepararEliminar(item);

                }
            );


        boton.addEventListener(
            'click',
            function () {


                const item =
                    document.createElement(
                        'div'
                    );


                item.className =
                    'agenda-item border rounded p-3 mb-3';


                item.innerHTML = `

                    <div class="row align-items-center">

                        <div class="col-auto">

                            <div
                                class="agenda-numero badge bg-primary fs-6"
                                style="min-width:38px;"
                            >

                                1

                            </div>

                        </div>


                        <div class="col">

                            <label class="form-label mb-1">

                                Punto de agenda

                            </label>


                            <textarea
                                name="agenda[]"
                                class="form-control"
                                rows="2"
                                maxlength="1000"
                                placeholder="Escribe el punto que se tratará..."
                            ></textarea>

                        </div>


                        <div class="col-auto">

                            <button
                                type="button"
                                class="btn btn-outline-danger btnEliminarAgenda"
                                title="Eliminar punto"
                            >

                                <i class="cil-trash"></i>

                            </button>

                        </div>

                    </div>

                `;


                container.appendChild(item);


                prepararEliminar(item);


                actualizarNumeros();


                item
                    .querySelector('textarea')
                    .focus();

            }
        );


        actualizarNumeros();

    }
);

</script>

@endsection