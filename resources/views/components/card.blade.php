<div {{ $attributes->merge(['class' => 'card shadow-sm border-0']) }}>

    @isset($title)

        <div class="card-header bg-white">

            <h4 class="mb-0">

                @isset($icon)

                    <i class="{{ $icon }} me-2"></i>

                @endisset

                {{ $title }}

            </h4>

        </div>

    @endisset

    <div class="card-body">

        {{ $slot }}

    </div>

    @isset($footer)

        <div class="card-footer bg-white">

            {{ $footer }}

        </div>

    @endisset

</div>