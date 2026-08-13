@props([

    'type' => 'submit',

    'color' => 'primary',

    'icon' => null

])

<button
    type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "btn btn-$color"
    ]) }}>

    @if($icon)

        <i class="{{ $icon }} me-1"></i>

    @endif

    {{ $slot }}

</button>

