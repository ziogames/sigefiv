<div
    class="tab-pane fade"
    id="seguridad">

    <div class="row">

        <div class="col-md-6">

            <div class="form-check form-switch mb-3">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="bitacora_activa"
                    @checked(old('bitacora_activa',$configuracion->bitacora_activa ?? true))>

                <label class="form-check-label">

                    Activar Bitácora

                </label>

            </div>

            <div class="form-check form-switch mb-3">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="sesion_unica"
                    @checked(old('sesion_unica',$configuracion->sesion_unica ?? false))>

                <label class="form-check-label">

                    Una sesión por usuario

                </label>

            </div>

            <div class="form-check form-switch mb-3">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="bloqueo_intentos"
                    @checked(old('bloqueo_intentos',$configuracion->bloqueo_intentos ?? true))>

                <label class="form-check-label">

                    Bloquear por intentos fallidos

                </label>

            </div>

            <div class="form-check form-switch">

                <input
                    class="form-check-input"
                    type="checkbox"
                    name="expirar_password"
                    @checked(old('expirar_password',$configuracion->expirar_password ?? false))>

                <label class="form-check-label">

                    Expirar contraseña

                </label>

            </div>

        </div>

        <div class="col-md-6">

            <div class="mb-3">

                <label class="form-label">

                    Intentos permitidos

                </label>

                <input
                    type="number"
                    name="intentos_login"
                    class="form-control"
                    value="{{ old('intentos_login',$configuracion->intentos_login ?? 5) }}">

            </div>

            <div class="mb-3">

                <label class="form-label">

                    Tiempo de sesión (minutos)

                </label>

                <input
                    type="number"
                    name="tiempo_sesion"
                    class="form-control"
                    value="{{ old('tiempo_sesion',$configuracion->tiempo_sesion ?? 30) }}">

            </div>

            <div class="mb-3">

                <label class="form-label">

                    Longitud mínima de contraseña

                </label>

                <input
                    type="number"
                    name="longitud_password"
                    class="form-control"
                    value="{{ old('longitud_password',$configuracion->longitud_password ?? 8) }}">

            </div>

        </div>

    </div>

</div>