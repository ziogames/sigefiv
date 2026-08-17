@extends('layouts.app')

@section('title', 'Nueva Asamblea')

@section('content')

<div class="container-fluid">

    {{-- Encabezado --}}
    <div class="d-flex align-items-center mb-4">

        <a
            href="{{ route('asambleas.index') }}"
            class="btn btn-light me-3"
        >
            <i class="cil-arrow-left"></i>
        </a>

        <div>
            <h1 class="h3 mb-1">
                <i class="cil-calendar me-2"></i>
                Nueva Asamblea
            </h1>

            <p class="text-body-secondary mb-0">
                Registra los datos de la convocatoria.
            </p>
        </div>

    </div>


    {{-- Errores --}}
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
                <i class="cil-pencil me-2"></i>
                Datos de la convocatoria
            </strong>

        </div>


        <div class="card-body">

            <form
                method="POST"
                action="{{ route('asambleas.store') }}"
            >

                @csrf


                {{-- Tipo y carácter --}}
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
                                {{ old('tipo') === 'ordinaria' ? 'selected' : '' }}
                            >
                                Asamblea ordinaria
                            </option>

                            <option
                                value="extraordinaria"
                                {{ old('tipo') === 'extraordinaria' ? 'selected' : '' }}
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
                                {{ old('importancia', 'normal') === 'normal' ? 'selected' : '' }}
                            >
                                Normal
                            </option>

                            <option
                                value="importante"
                                {{ old('importancia') === 'importante' ? 'selected' : '' }}
                            >
                                Importante
                            </option>

                            <option
                                value="urgente"
                                {{ old('importancia') === 'urgente' ? 'selected' : '' }}
                            >
                                Urgente
                            </option>

                        </select>

                    </div>

                </div>


                {{-- Título --}}
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
                        value="{{ old('titulo') }}"
                        placeholder="Ejemplo: Asamblea General de Vecinos"
                        maxlength="255"
                        required
                    >

                </div>


                {{-- Convoca --}}
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
                        value="{{ old('convoca') }}"
                        placeholder="Ejemplo: Comité Electoral Transitorio"
                        maxlength="255"
                        required
                    >

                </div>


                {{-- Sector / Grupo / Manzana / Lote --}}
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
                            value="{{ old('sector') }}"
                            placeholder="Ej. 2"
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
                            value="{{ old('grupo') }}"
                            placeholder="Ej. 21"
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
                            value="{{ old('manzana') }}"
                            placeholder="Ej. A"
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
                            value="{{ old('lote') }}"
                            placeholder="Ej. 15"
                        >

                    </div>

                </div>


                {{-- Fecha y citaciones --}}
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
                            value="{{ old('fecha') }}"
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
                            value="{{ old('primera_citacion') }}"
                            required
                        >

                    </div>


                    <div class="col-md-4 mb-3">

                        <label
                            for="segunda_citacion"
                            class="form-label"
                        >
                            Segunda citación
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="time"
                            id="segunda_citacion"
                            name="segunda_citacion"
                            class="form-control"
                            value="{{ old('segunda_citacion') }}"
                            required
                        >

                    </div>

                </div>


                {{-- Hora principal y lugar --}}
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
                            value="{{ old('hora') }}"
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
                            value="{{ old('lugar') }}"
                            placeholder="Ejemplo: Local comunal"
                            maxlength="255"
                            required
                        >

                    </div>

                </div>


                {{-- Texto de convocatoria --}}
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
                        placeholder="Escribe aquí el texto de la convocatoria..."
                    >{{ old('descripcion') }}</textarea>

                </div>


                {{-- Agenda --}}
                <div class="card border mb-4">

                    <div class="card-header d-flex justify-content-between align-items-center">

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
                            Agrega los puntos que serán tratados durante
                            la asamblea.
                        </p>


                        <div id="agendaContainer"></div>


                        <div
                            id="agendaVacia"
                            class="text-center py-4 text-body-secondary"
                        >

                            <i
                                class="cil-list"
                                style="font-size: 2rem;"
                            ></i>

                            <div class="mt-2">
                                Todavía no hay puntos de agenda.
                            </div>

                            <small>
                                Haz clic en "Agregar punto".
                            </small>

                        </div>

                    </div>

                </div>


                {{-- Aviso --}}
                <div class="alert alert-info">

                    <i class="cil-info me-2"></i>

                    La convocatoria se guardará como
                    <strong>borrador</strong>.

                    Podrás revisarla antes de enviar la alerta Push.

                </div>


                {{-- Botones --}}
                <div class="d-flex justify-content-end gap-2">

                    <a
                        href="{{ route('asambleas.index') }}"
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
                        Guardar borrador
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    const container =
        document.getElementById('agendaContainer');

    const boton =
        document.getElementById('btnAgregarAgenda');

    const mensajeVacio =
        document.getElementById('agendaVacia');


    let contador = 0;


    function actualizarEstado() {

        const puntos =
            container.querySelectorAll('.agenda-item');

        if (puntos.length === 0) {

            mensajeVacio.style.display = 'block';

        } else {

            mensajeVacio.style.display = 'none';

        }


        puntos.forEach(function (punto, index) {

            const numero =
                punto.querySelector('.agenda-numero');

            if (numero) {

                numero.textContent =
                    index + 1;

            }

        });

    }


    function agregarPunto(valor = '') {

        contador++;


        const item =
            document.createElement('div');

        item.className =
            'agenda-item border rounded p-3 mb-3';


        item.innerHTML = `

            <div class="row align-items-center">

                <div class="col-auto">

                    <div
                        class="agenda-numero badge bg-primary fs-6"
                        style="min-width: 38px;"
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
                    >${valor}</textarea>

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


        item
            .querySelector('.btnEliminarAgenda')
            .addEventListener('click', function () {

                item.remove();

                actualizarEstado();

            });


        actualizarEstado();


        item
            .querySelector('textarea')
            .focus();

    }


    boton.addEventListener(
        'click',
        function () {
            agregarPunto();
        }
    );


    actualizarEstado();

});

</script>

@endsection