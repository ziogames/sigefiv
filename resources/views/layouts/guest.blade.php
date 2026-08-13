<!DOCTYPE html>
<html lang="es">

<head>

    <base href="/">

    <meta charset="utf-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <meta name="csrf-token"
          content="{{ csrf_token() }}">

    <title>{{ config('app.name','SIGEFIV') }}</title>

    <link rel="apple-touch-icon"
          sizes="180x180"
          href="{{ asset('assets/favicon/apple-icon-180x180.png') }}">

    <link rel="icon"
          type="image/png"
          href="{{ asset('assets/favicon/favicon-32x32.png') }}">

    <link rel="stylesheet"
          href="{{ asset('vendors/simplebar/css/simplebar.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/vendors/simplebar.css') }}">

    <link rel="stylesheet"
          href="{{ asset('vendors/@coreui/icons/css/free.min.css') }}">
          <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

    <link rel="stylesheet"
          href="{{ asset('css/style.css') }}">

    <script src="{{ asset('js/config.js') }}"></script>

    <script src="{{ asset('js/color-modes.js') }}"></script>

    <style>

        body{

            min-height:100vh;

            margin:0;

            background:linear-gradient(
                135deg,
                #0f172a,
                #1e3a8a,
                #2563eb
            );

            display:flex;

            justify-content:center;

            align-items:center;

            overflow-x:hidden;

        }

    </style>

</head>

<body>

    @yield('content')

    <script src="{{ asset('vendors/@coreui/coreui/js/coreui.bundle.min.js') }}"></script>

    <script src="{{ asset('vendors/simplebar/js/simplebar.min.js') }}"></script>

</body>

</html>