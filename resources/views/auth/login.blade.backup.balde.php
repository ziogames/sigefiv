@extends('layouts.guest')
@section('content')

<div class="bg-body-tertiary min-vh-100 d-flex flex-row align-items-center">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-md-8">

                <div class="card-group">

                    <div class="card p-4">

                        <div class="card-body">

                            <form method="POST" action="{{ route('login') }}">

                                @csrf

                                <h1>Iniciar sesión</h1>

                                <p class="text-body-secondary">

                                    Ingrese sus credenciales

                                </p>

                                <div class="input-group mb-3">

                                   <span class="input-group-text">

    <i class="cil-user"></i>

</span>

                                    <input
                                        type="email"
                                        class="form-control @error('email') is-invalid @enderror"
                                        name="email"
                                        value="{{ old('email') }}"
                                        placeholder="Correo electrónico"
                                        required
                                        autofocus>

                                    @error('email')

                                        <div class="invalid-feedback">

                                            {{ $message }}

                                        </div>

                                    @enderror

                                </div>

                                <div class="input-group mb-4">

                                    <span class="input-group-text">

    <i class="cil-lock-locked"></i>

</span>

                                    <input
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        name="password"
                                        placeholder="Contraseña"
                                        required>

                                    @error('password')

                                        <div class="invalid-feedback">

                                            {{ $message }}

                                        </div>

                                    @enderror

                                </div>

                                <div class="row">

                                    <div class="col-6">

                                        <button
                                            class="btn btn-primary px-4"
                                            type="submit">

                                            Entrar

                                        </button>

                                    </div>

                                    <div class="col-6 text-end">

                                        @if (Route::has('password.request'))

                                            <a
                                                class="btn btn-link px-0"
                                                href="{{ route('password.request') }}">

                                                ¿Olvidó su contraseña?

                                            </a>

                                        @endif

                                    </div>

                                </div>

                                <div class="form-check mt-3">

                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="remember"
                                        id="remember">

                                    <label
                                        class="form-check-label"
                                        for="remember">

                                        Recordarme

                                    </label>

                                </div>

                            </form>

                        </div>

                    </div>

                    <div class="card text-white bg-primary py-5"
                        style="width:44%">

                        <div class="card-body text-center">

                            <div>

                                <h2>SIGEFIV</h2>

                                <p>

                                    Sistema Integrado de Gestión Financiera

                                </p>

                                <p>

                                    Control de ingresos, egresos y administración
                                    del Grupo.

                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection