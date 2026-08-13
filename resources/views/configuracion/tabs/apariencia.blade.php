<div
    class="tab-pane fade"
    id="apariencia">

    <div class="row">

        <div class="col-md-6 mb-4">

    <label class="form-label">

        Logo del Sistema

    </label>

    <input
        type="file"
        name="logo"
        id="logo"
        class="form-control"
        accept="image/*">

    <div class="mt-3 text-center">

        <img
            id="preview-logo"
            src="{{ !empty($configuracion->logo) ? asset('storage/'.$configuracion->logo) : 'https://placehold.co/220x120?text=Logo' }}"
            class="img-thumbnail"
            style="max-height:140px;">

    </div>

</div>

        <div class="col-md-6 mb-4">

    <label class="form-label">

        Favicon

    </label>

    <input
        type="file"
        name="favicon"
        id="favicon"
        class="form-control"
        accept="image/*">

    <div class="mt-3 text-center">

        <img
            id="preview-favicon"
            src="{{ !empty($configuracion->favicon) ? asset('storage/'.$configuracion->favicon) : 'https://placehold.co/64x64?text=ICO' }}"
            class="img-thumbnail"
            style="max-height:64px;">

    </div>

</div>

        <div class="col-md-6 mb-4">

    <label class="form-label">

        Imagen del Login

    </label>

    <input
        type="file"
        name="imagen_login"
        id="imagen_login"
        class="form-control"
        accept="image/*">

    <div class="mt-3 text-center">

        <img
            id="preview-login"
            src="{{ !empty($configuracion->imagen_login) ? asset('storage/'.$configuracion->imagen_login) : 'https://placehold.co/250x150?text=Login' }}"
            class="img-thumbnail"
            style="max-height:180px;">

    </div>

</div>

        <div class="col-md-6 mb-4">

            <label class="form-label">

                Color Principal

            </label>

            <input
                type="color"
                name="color_principal"
                class="form-control form-control-color"
                value="{{ old('color_principal', $configuracion->color_principal ?? '#321fdb') }}">

            <div class="mt-4">

                <span
                    class="badge fs-6 px-4 py-3"
                    style="background: {{ old('color_principal', $configuracion->color_principal ?? '#321fdb') }};">

                    Vista previa del color

                </span>

            </div>

        </div>

    </div>

</div>