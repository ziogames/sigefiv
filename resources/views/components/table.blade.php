@props([
    'hover' => true,
    'striped' => false,
    'bordered' => false
])

<div class="table-responsive">

    <table
        {{ $attributes->merge([
            'class' =>
                'table ' .
                ($hover ? 'table-hover ' : '') .
                ($striped ? 'table-striped ' : '') .
                ($bordered ? 'table-bordered ' : '') .
                'align-middle mb-0'
        ]) }}>

        {{ $slot }}

    </table>

</div>