@if($ruta)

<div class="text-center">

    <img

        src="{{ asset('storage/'.$ruta) }}"

        class="img-thumbnail shadow-sm"

        style="max-height:160px">

</div>

@else

<div class="border rounded p-5 text-center text-secondary">

    <i class="cil-image fs-1"></i>

    <br><br>

    Sin imagen

</div>

@endif