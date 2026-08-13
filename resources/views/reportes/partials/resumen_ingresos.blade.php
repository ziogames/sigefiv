<div class="card shadow-sm border-0 mb-4">

    <div class="card-body">

        <div class="row align-items-center">

            <div class="col-md-8">

                <small class="text-muted">

                    Total de Ingresos del período

                </small>

                <h2 class="text-success fw-bold mb-0">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['ingresos'],2) }}

                </h2>

            </div>

            <div class="col-md-4 text-end">

                <i class="cil-arrow-circle-top text-success"
                   style="font-size:70px;"></i>

            </div>

        </div>

    </div>

</div>