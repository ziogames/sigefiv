<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <base href="/">

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'SIGEFIV'))</title>

    {{-- Favicon --}}
    <link rel="apple-touch-icon" sizes="180x180"
        href="{{ asset('assets/favicon/apple-icon-180x180.png') }}">

    <link rel="icon"
        type="image/png"
        sizes="32x32"
        href="{{ asset('assets/favicon/favicon-32x32.png') }}">

    {{-- Vendors --}}
    <link rel="stylesheet"
        href="{{ asset('vendors/simplebar/css/simplebar.css') }}">

    <link rel="stylesheet"
        href="{{ asset('css/vendors/simplebar.css') }}">

    {{-- CoreUI --}}
    <link rel="stylesheet"
        href="{{ asset('css/style.css') }}">

    <link rel="stylesheet"
        href="{{ asset('css/examples.css') }}">

    <link rel="stylesheet"
      href="{{ asset('css/sigefiv.css') }}">

    <script src="{{ asset('js/config.js') }}"></script>

    <script src="{{ asset('js/color-modes.js') }}"></script>

    @stack('css')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('vendors/@coreui/icons/css/free.min.css') }}">
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">


    @php

$config = \App\Models\Configuracion::first();

@endphp

<style>

:root{

    --cui-primary: {{ $config->color_principal ?? '#321fdb' }};

}

.btn-primary{

    background: var(--cui-primary);

    border-color: var(--cui-primary);

}

.bg-primary{

    background: var(--cui-primary)!important;

}

.text-primary{

    color: var(--cui-primary)!important;

}

</style>
</head>
<body>

    {{-- SIDEBAR --}}
    @include('partials.sidebar')

    <div class="wrapper d-flex flex-column min-vh-100">

        {{-- NAVBAR --}}
        @include('partials.navbar')

        <div class="body flex-grow-1 bg-body-tertiary">

            <div class="container-fluid px-4 py-4">

                

                @if(session('error'))

                    <div class="alert alert-danger alert-dismissible fade show">

                        {{ session('error') }}

                        <button
                            type="button"
                            class="btn-close"
                            data-bs-dismiss="alert">
                        </button>

                    </div>

                @endif

                @if($errors->any())

                    <div class="alert alert-danger">

                        <ul class="mb-0">

                            @foreach($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif

                @yield('content')

            </div>

        </div>

        {{-- FOOTER --}}
        @include('partials.footer')

    </div>
{{-- CoreUI --}}
<script src="{{ asset('vendors/@coreui/coreui/js/coreui.bundle.min.js') }}"></script>

<script src="{{ asset('vendors/simplebar/js/simplebar.min.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="{{ asset('js/sigefiv.js') }}"></script>

@stack('scripts')

{{-- Chart.js DEBE cargarse antes de dashboard.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="{{ asset('js/dashboard/dashboard.js') }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const alerta = document.getElementById('alert-success');

    if (alerta) {

        setTimeout(function () {

            alerta.style.transition = "opacity 0.5s ease";

            alerta.style.opacity = "0";

            setTimeout(function () {

                alerta.remove();

            }, 500);

        }, 5000);

    }

});
</script>

</body>

</html>