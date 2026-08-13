<div class="row">

    <div class="col-lg-5">

        <div class="card shadow">

            <div class="card-header d-flex justify-content-between align-items-center">

                <strong>

                    Gastos por Categoría

                </strong>

                <select
                    id="mesCategoria"
                    class="form-select form-select-sm w-auto">

                    @foreach($meses as $numero=>$nombre)

                        <option
                            value="{{ $numero }}">

                            {{ $nombre }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div class="card-body">

                <canvas id="graficoCategorias"></canvas>

            </div>

        </div>

    </div>

    <div class="col-lg-7">

        <div class="card shadow">

            <div class="card-header d-flex justify-content-between align-items-center">

                <strong>

                    Comparación Mensual

                </strong>

                <div>

                    <select
                        id="anioComparacion"
                        class="form-select form-select-sm d-inline-block"
                        style="width:100px">

                        @foreach($anios as $anio)

                            <option value="{{ $anio }}">

                                {{ $anio }}

                            </option>

                        @endforeach

                    </select>

                    <select
                        id="mesComparacion"
                        class="form-select form-select-sm d-inline-block"
                        style="width:130px">

                        @foreach($meses as $numero=>$nombre)

                            <option value="{{ $numero }}">

                                {{ $nombre }}

                            </option>

                        @endforeach

                    </select>

                </div>

            </div>

            <div class="card-body">

                <canvas id="graficoComparacion"></canvas>

            </div>

        </div>

    </div>

</div>