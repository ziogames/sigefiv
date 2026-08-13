<form
method="GET"
class="row g-2 mb-4">

<div class="col">

<input

type="search"

name="buscar"

value="{{ request('buscar') }}"

class="form-control"

placeholder="Buscar...">

</div>

<div class="col-auto">

<x-button

type="submit"

icon="cil-search">

Buscar

</x-button>

</div>

</form>