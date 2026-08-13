<div
    class="tab-pane fade"
    id="organizacion">

    <div class="row">

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Presidente

            </label>

            <input
                type="text"
                name="presidente"
                class="form-control"
                value="{{ old('presidente', $configuracion->presidente ?? '') }}">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Tesorero

            </label>

            <input
                type="text"
                name="tesorero"
                class="form-control"
                value="{{ old('tesorero', $configuracion->tesorero ?? '') }}">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Secretario

            </label>

            <input
                type="text"
                name="secretario"
                class="form-control"
                value="{{ old('secretario', $configuracion->secretario ?? '') }}">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                RUC

            </label>

            <input
                type="text"
                name="ruc"
                class="form-control"
                value="{{ old('ruc', $configuracion->ruc ?? '') }}">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Sitio Web

            </label>

            <input
                type="url"
                name="web"
                class="form-control"
                value="{{ old('web', $configuracion->web ?? '') }}">

        </div>

        <div class="col-md-4 mb-3">

            <label class="form-label">

                Facebook

            </label>

            <input
                type="text"
                name="facebook"
                class="form-control"
                value="{{ old('facebook', $configuracion->facebook ?? '') }}">

        </div>

        <div class="col-md-6 mb-3">

            <label class="form-label">

                Instagram

            </label>

            <input
                type="text"
                name="instagram"
                class="form-control"
                value="{{ old('instagram', $configuracion->instagram ?? '') }}">

        </div>

    </div>

</div>