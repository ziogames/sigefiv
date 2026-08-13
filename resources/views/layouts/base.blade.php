<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('titulo', 'Mi aplicación')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>


    </style>

</head>
<body>
    @include('layouts.partials.header')

    @include('layouts.partials.navbar')

    <div class="flex">
    @include('layouts.partials.sidebar')

   <main id="contenido-principal" class="min-h-screen flex-1 bg-slate-100 p-8">
    @yield('contenido')
</main>
</div>

    @include('layouts.partials.footer')

   
</body>
</html>