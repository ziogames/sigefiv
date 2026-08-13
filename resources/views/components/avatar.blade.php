@php

$colores = [
    'bg-primary',
    'bg-success',
    'bg-danger',
    'bg-warning',
    'bg-info',
    'bg-secondary',
];

$color = $colores[
    crc32($user->name) % count($colores)
];

@endphp

@if(!empty($user->foto))

    <img
        src="{{ $user->avatar }}"
        alt="{{ $user->name }}"
        class="rounded-circle"
        style="
            width:{{ $size }}px;
            height:{{ $size }}px;
            object-fit:cover;
        ">

@else

    <div
        class="rounded-circle {{ $color }} text-white fw-bold d-flex align-items-center justify-content-center"
        style="
            width:{{ $size }}px;
            height:{{ $size }}px;
            font-size:{{ intval($size/2.3) }}px;
        "
        title="{{ $user->name }}">

        {{ strtoupper(substr($user->name,0,1)) }}

    </div>

@endif