<div
    class="tab-pane fade show active"
    id="general">

    <div class="row">

        <div class="col-md-6 mb-3">

            <label class="form-label">

                Nombre del Sistema

            </label>

            <input
                type="text"
                name="nombre_sistema"
                class="form-control"
                value="{{ old('nombre_sistema', $configuracion->nombre_sistema ?? 'SIGEFIV') }}">

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">

                Organización

            </label>

            <input
                type="text"
                name="organizacion"
                class="form-control"
                value="{{ old('organizacion', $configuracion->organizacion ?? '') }}">

        </div>

        <div class="col-md-12 mb-3">

            <label class="form-label">

                Dirección

            </label>

            <input
                type="text"
                name="direccion"
                class="form-control"
                value="{{ old('direccion', $configuracion->direccion ?? '') }}">

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">

                Teléfono

            </label>

            <input
                type="text"
                name="telefono"
                class="form-control"
                value="{{ old('telefono', $configuracion->telefono ?? '') }}">

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">

                Correo Electrónico

            </label>

            <input
                type="email"
                name="correo"
                class="form-control"
                value="{{ old('correo', $configuracion->correo ?? '') }}">

        </div>

    </div>

</div>