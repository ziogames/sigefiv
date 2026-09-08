@php

    $colores = [
        'bg-primary',
        'bg-success',
        'bg-danger',
        'bg-warning',
        'bg-info',
        'bg-secondary',
    ];

    /*
     * Este componente también puede renderizarse desde páginas
     * de error (403, 404, etc.) donde puede no existir un
     * usuario autenticado.
     */
    $nombreUsuario = $user?->name ?? 'Usuario';
    $avatar = $user?->avatar;

    $color = $colores[
        crc32($nombreUsuario) % count($colores)
    ];

@endphp

@if($avatar)

    <img
        src="{{ $avatar }}"
        alt="{{ $nombreUsuario }}"
        class="rounded-circle"
        style="
            width:{{ $size }}px;
            height:{{ $size }}px;
            object-fit:cover;
        "
        loading="lazy"
        referrerpolicy="no-referrer">

@else

    <div
        class="rounded-circle {{ $color }} text-white fw-bold d-flex align-items-center justify-content-center"
        style="
            width:{{ $size }}px;
            height:{{ $size }}px;
            font-size:{{ intval($size/2.3) }}px;
        "
        title="{{ $nombreUsuario }}">

        {{ strtoupper(substr($nombreUsuario,0,1)) }}

    </div>

@endif
