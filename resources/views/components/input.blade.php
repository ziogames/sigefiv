@props([

    'label',

    'name',

    'type' => 'text',

    'value' => '',

    'required' => false

])

<div class="mb-3">

    <label
        for="{{ $name }}"
        class="form-label">

        {{ $label }}

    </label>

    <input

        id="{{ $name }}"

        name="{{ $name }}"

        type="{{ $type }}"

        value="{{ old($name,$value) }}"

        @required($required)

        {{ $attributes->merge([

            'class' => 'form-control'

        ]) }}

    >

    @error($name)

        <div class="text-danger small mt-1">

            {{ $message }}

        </div>

    @enderror

</div>