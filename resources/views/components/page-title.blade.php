@props([

'title',

'icon'=>null

])

<div class="d-flex justify-content-between align-items-center mb-4">

    <div>

        <h2>

            @if($icon)

                <i class="{{ $icon }} me-2"></i>

            @endif

            {{ $title }}

        </h2>

    </div>

    <div>

        {{ $slot }}

    </div>

</div>