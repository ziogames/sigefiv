<div class="mov-section">

    {{-- =====================================================
         ENCABEZADO DE LA TABLA
         ===================================================== --}}

    <div class="mov-table-header">

        <div>

            <h5 class="mov-table-title">

                <i class="cil-list me-1"></i>

                Detalle de movimientos

            </h5>

            <small class="text-muted">

                Registro de ingresos y egresos

            </small>

        </div>


        <span class="mov-count">

            {{ $movimientos->total() }}

            registros

        </span>

    </div>


    {{-- =====================================================
         TABLA
         ===================================================== --}}

    <div class="table-responsive">

        <table class="table mov-table align-middle">

            <thead>

                <tr>

                    <th>
                        Número
                    </th>

                    <th>
                        Fecha
                    </th>

                    <th>
                        Tipo
                    </th>

                    <th>
                        Categoría
                    </th>

                    <th>
                        Concepto
                    </th>

                    <th>
                        Persona
                    </th>

                    <th class="text-end">
                        Monto
                    </th>

                    <th>
                        Estado
                    </th>

                    <th class="text-center">
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($movimientos as $movimiento)

                    <tr>


                        {{-- =================================
                             NÚMERO
                             ================================= --}}

                        <td>

                            <span class="mov-number">

                                {{ $movimiento->numero }}

                            </span>

                        </td>


                        {{-- =================================
                             FECHA
                             ================================= --}}

                        <td>

                            <span class="mov-date">

                                {{ $movimiento->fecha->format('d/m/Y') }}

                            </span>

                        </td>


                        {{-- =================================
                             TIPO
                             ================================= --}}

                        <td>

                            @if($movimiento->tipo == 'Ingreso')

                                <span class="mov-badge mov-badge-income">

                                    <i class="cil-arrow-top"></i>

                                    Ingreso

                                </span>

                            @else

                                <span class="mov-badge mov-badge-expense">

                                    <i class="cil-arrow-bottom"></i>

                                    Egreso

                                </span>

                            @endif

                        </td>


                        {{-- =================================
                             CATEGORÍA
                             ================================= --}}

                        <td>

                            {{ $movimiento->categoria->nombre }}

                        </td>


                        {{-- =================================
                             CONCEPTO
                             ================================= --}}

                        <td>

                            <div class="mov-concept">

                                {{ $movimiento->concepto }}

                            </div>

                        </td>


                        {{-- =================================
                             PERSONA
                             ================================= --}}

                        <td>

                            <span class="mov-person">

                                {{ $movimiento->persona ?: '—' }}

                            </span>

                        </td>


                        {{-- =================================
                             MONTO
                             ================================= --}}

                        <td class="text-end">

                            @if($movimiento->tipo == 'Ingreso')

                                <span class="mov-amount-income">

                                    + S/
                                    {{ number_format($movimiento->monto, 2) }}

                                </span>

                            @else

                                <span class="mov-amount-expense">

                                    - S/
                                    {{ number_format($movimiento->monto, 2) }}

                                </span>

                            @endif

                        </td>


                        {{-- =================================
                             ESTADO
                             ================================= --}}

                        <td>

                            @if($movimiento->estado == 'Registrado')

                                <span class="mov-badge mov-badge-registered">

                                    Registrado

                                </span>

                            @else

                                <span class="mov-badge mov-badge-cancelled">

                                    Anulado

                                </span>

                            @endif

                        </td>


                        {{-- =================================
                             ACCIONES
                             ================================= --}}

                        <td>

                            <div class="d-flex justify-content-center gap-1">


                                {{-- EDITAR --}}

                                @can('movimientos.edit')

    <a
        href="{{ route('movimientos.edit', $movimiento) }}"
        class="btn mov-action mov-action-edit"
        title="Editar movimiento">

        <i class="cil-pencil"></i>

    </a>

@endcan


                                {{-- ELIMINAR --}}

  @can('movimientos.destroy')

    <form
        action="{{ route('movimientos.destroy', $movimiento) }}"
        method="POST"
        class="d-inline form-eliminar-movimiento">

        @csrf

        @method('DELETE')

        <button
            type="submit"
            class="btn mov-action mov-action-delete"
            title="Eliminar movimiento">

            <i class="cil-trash"></i>

        </button>

    </form>

@endcan

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="9"
                            class="text-center py-5">

                            <div class="text-muted">

                                <i
                                    class="cil-folder-open"
                                    style="font-size:35px;">
                                </i>

                                <div class="mt-2">

                                    No existen movimientos.

                                </div>

                            </div>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- =====================================================
         PIE Y PAGINACIÓN
         ===================================================== --}}

    <div class="mov-footer">

        <div>

            Mostrando

            <strong class="text-light">

                {{ $movimientos->firstItem() ?? 0 }}

            </strong>

            a

            <strong class="text-light">

                {{ $movimientos->lastItem() ?? 0 }}

            </strong>

            de

            <strong class="text-light">

                {{ $movimientos->total() }}

            </strong>

            registros

        </div>


        <div>

            {{ $movimientos->links() }}

        </div>

    </div>

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.form-eliminar-movimiento')
        .forEach(function (form) {

            form.addEventListener('submit', function (event) {

                event.preventDefault();

                Swal.fire({

                    title: '¿Eliminar movimiento?',

                    text: 'Esta acción eliminará el movimiento seleccionado y no se puede deshacer.',

                    icon: 'warning',

                    showCancelButton: true,

                    confirmButtonText: 'Sí, eliminar',

                    cancelButtonText: 'Cancelar',

                    reverseButtons: true,

                    focusCancel: true

                }).then(function (result) {

                    if (result.isConfirmed) {

                        form.submit();

                    }

                });

            });

        });

});

</script>