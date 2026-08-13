<div
    class="tab-pane fade"
    id="sistema">

    <div class="row">

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Moneda

            </label>

            <input
                type="text"
                name="moneda"
                class="form-control"
                value="{{ old('moneda', $configuracion->moneda ?? 'Sol Peruano') }}">

        </div>

        <div class="col-md-2 mb-3">

            <label class="form-label">

                Símbolo

            </label>

            <input
                type="text"
                name="simbolo_moneda"
                class="form-control"
                value="{{ old('simbolo_moneda', $configuracion->simbolo_moneda ?? 'S/') }}">

        </div>

        <div class="col-md-2 mb-3">

            <label class="form-label">

                Decimales

            </label>

            <input
                type="number"
                min="0"
                max="4"
                name="decimales"
                class="form-control"
                value="{{ old('decimales', $configuracion->decimales ?? 2) }}">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Zona Horaria

            </label>

            <input
                type="text"
                name="zona_horaria"
                class="form-control"
                value="{{ old('zona_horaria', $configuracion->zona_horaria ?? 'America/Lima') }}">

        </div>

    </div>

</div>