@extends('layouts.guest')

@section('content')

<div class="container py-4">

    <div class="row justify-content-center align-items-center min-vh-100">

        <div class="col-lg-10">

            <div class="card border-0 shadow-lg overflow-hidden">

                <div class="row g-0">

                    <!-- PANEL IZQUIERDO -->

                    <div class="col-lg-6 d-none d-lg-flex bg-primary text-white">

                        <div class="d-flex flex-column justify-content-center align-items-center p-5 w-100">

                            <i class="cil-bank display-1 mb-4"></i>

                            <h1 class="fw-bold">

                                SIGEFIV

                            </h1>

                            <h5 class="text-center mt-3">

                                Sistema Integrado de Gestión Financiera

                            </h5>

                            <p class="text-center opacity-75 mt-4">

                                Administra los ingresos, egresos y la
                                información financiera de tu grupo residencial
                                desde cualquier dispositivo.

                            </p>

                        </div>

                    </div>


                    <!-- FORMULARIO -->

                    <div class="col-lg-6">

                        <div class="card-body p-5">

                            <h2 class="fw-bold mb-2">

                                Bienvenido

                            </h2>

                            <p class="text-body-secondary mb-4">

                                Inicia sesión para continuar.

                            </p>


                            {{-- MENSAJE DE ERROR GENERAL --}}

                            @if(session('error'))

                                <div class="alert alert-danger">

                                    {{ session('error') }}

                                </div>

                            @endif


                            <!-- LOGIN SIGEFIV -->

                            <form
                                id="formLogin"
                                method="POST"
                                action="{{ route('login') }}"
                            >

                                @csrf


                                <!-- CORREO -->

                                <div class="mb-3">

                                    <label class="form-label">

                                        Correo electrónico

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="cil-user"></i>

                                        </span>

                                        <input
                                            type="email"
                                            name="email"
                                            value="{{ old('email') }}"
                                            class="form-control @error('email') is-invalid @enderror"
                                            required
                                            autofocus
                                        >

                                        @error('email')

                                            <div class="invalid-feedback">

                                                {{ $message }}

                                            </div>

                                        @enderror

                                    </div>

                                </div>


                                <!-- CONTRASEÑA -->

                                <div class="mb-3">

                                    <label class="form-label">

                                        Contraseña

                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">

                                            <i class="cil-lock-locked"></i>

                                        </span>

                                        <input
                                            id="password"
                                            type="password"
                                            name="password"
                                            class="form-control @error('password') is-invalid @enderror"
                                            required
                                        >

                                        <button
                                            class="btn border"
                                            type="button"
                                            id="mostrarPassword"
                                        >

                                            <i class="bi bi-eye"></i>

                                        </button>

                                        @error('password')

                                            <div class="invalid-feedback">

                                                {{ $message }}

                                            </div>

                                        @enderror

                                    </div>

                                </div>


                                <!-- RECORDAR / RECUPERAR -->

                                <div class="d-flex justify-content-between align-items-center mb-4">

                                    <div class="form-check">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="remember"
                                            id="remember"
                                        >

                                        <label
                                            class="form-check-label"
                                            for="remember"
                                        >

                                            Recordarme

                                        </label>

                                    </div>


                                    @if(Route::has('password.request'))

                                        <a
                                            href="{{ route('password.request') }}"
                                        >

                                            ¿Olvidó su contraseña?

                                        </a>

                                    @endif

                                </div>


                                <!-- BOTÓN INGRESAR -->

                                <button
                                    id="btnLogin"
                                    type="submit"
                                    class="btn btn-primary w-100 py-2"
                                >

                                    <i class="bi bi-box-arrow-in-right me-2"></i>

                                    <span id="textoBoton">

                                        Ingresar

                                    </span>

                                </button>

                            </form>


                            <!-- SEPARADOR -->

                            <div class="d-flex align-items-center my-4">

                                <hr class="flex-grow-1">

                                <span class="mx-3 text-body-secondary">

                                    o

                                </span>

                                <hr class="flex-grow-1">

                            </div>


                            <!-- GOOGLE -->

                            <a
                                href="{{ route('google.redirect') }}"
                                class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center gap-2"
                                style="text-decoration:none;"
                            >

                                <img
                                    src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg"
                                    alt="Google"
                                    width="20"
                                    height="20"
                                >

                                <span>

                                    Continuar con Google

                                </span>

                            </a>


                            <!-- INFORMACIÓN GOOGLE -->

                            <p class="text-body-secondary text-center small mt-3 mb-0">

                                Puedes ingresar con tu cuenta de SIGEFIV
                                o utilizar una cuenta de Google.

                            </p>


                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<script>

/*
|--------------------------------------------------------------------------
| MOSTRAR / OCULTAR CONTRASEÑA
|--------------------------------------------------------------------------
*/

const boton = document.getElementById('mostrarPassword');

const password = document.getElementById('password');

if (boton && password) {

    boton.addEventListener('click', () => {

        if (password.type === 'password') {

            password.type = 'text';

            boton.innerHTML =
                '<i class="bi bi-eye-slash"></i>';

        } else {

            password.type = 'password';

            boton.innerHTML =
                '<i class="bi bi-eye"></i>';

        }

    });

}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

const formLogin =
    document.getElementById('formLogin');

const btnLogin =
    document.getElementById('btnLogin');

const textoBoton =
    document.getElementById('textoBoton');

if (formLogin && btnLogin && textoBoton) {

    formLogin.addEventListener('submit', () => {

        btnLogin.disabled = true;

        textoBoton.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2"></span>
            Ingresando...
        `;

    });

}


/*
|--------------------------------------------------------------------------
| RESTAURAR BOTÓN AL VOLVER CON EL NAVEGADOR
|--------------------------------------------------------------------------
*/

window.addEventListener('pageshow', function () {

    const btnLogin =
        document.getElementById('btnLogin');

    const textoBoton =
        document.getElementById('textoBoton');

    if (btnLogin && textoBoton) {

        btnLogin.disabled = false;

        textoBoton.innerHTML = `
            Ingresar
        `;

    }

});

</script>

@endsection