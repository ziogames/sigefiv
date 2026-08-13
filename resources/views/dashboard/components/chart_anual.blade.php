<div class="dashboard-card p-4">

    <div class="row mb-4">

        <div class="col-md-6">

            <h4 class="fw-bold">

                Resumen Financiero

            </h4>

            <small class="text-muted">

                Ingresos y egresos del período

            </small>

        </div>

        <div class="col-md-6 text-end">

            <select
                id="anioDashboard"
                class="form-select w-auto d-inline">

                @foreach($anios as $anio)

                    <option value="{{ $anio }}">

                        {{ $anio }}

                    </option>

                @endforeach

            </select>

        </div>

    </div>

    <div class="chart-container">

    <canvas id="graficoAnual"></canvas>
            <script>

window.graficoAnual = @json($graficoAnual);

</script>
</div>

</div>