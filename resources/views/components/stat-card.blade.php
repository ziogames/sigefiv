@props([

'title',

'value',

'icon',

'color'=>'primary'

])

<div class="card border-0 shadow-sm h-100">

<div class="card-body">

<div class="d-flex justify-content-between">

<div>

<div class="text-body-secondary">

{{ $title }}

</div>

<h2 class="fw-bold mt-2">

{{ $value }}

</h2>

</div>

<div>

<i class="{{ $icon }} text-{{ $color }}"
style="font-size:40px">

</i>

</div>

</div>

</div>

</div>