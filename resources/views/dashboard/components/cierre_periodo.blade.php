@if($cierrePeriodo['requiere_cierre'])

<div class="alert alert-warning shadow-sm border-0 mb-4">

    <div class="d-flex align-items-center">

        <i class="cil-warning fs-3 me-3"></i>

        <div>

            <h5 class="mb-1">

                El período {{ $cierrePeriodo['periodo']->nombre_completo }}
                ha finalizado.

            </h5>

            <div>

                Es necesario cerrarlo antes de continuar.

            </div>

        </div>

    </div>

</div>

@endif