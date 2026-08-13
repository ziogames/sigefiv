<div class="card shadow">

    <div class="card-header d-flex justify-content-between align-items-center">

        <strong>

            Últimos Movimientos

        </strong>

        <a
            href="{{ route('movimientos.index') }}"
            class="btn btn-sm btn-primary">

            Ver todos

        </a>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover table-striped mb-0">

                <thead class="table-light">

                    <tr>

                        <th>Fecha</th>

                        <th>Concepto</th>

                        <th>Categoría</th>

                        <th>Tipo</th>

                        <th class="text-end">

                            Monto

                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($ultimosMovimientos as $movimiento)

                        <tr>

                            <td>

                                {{ \Carbon\Carbon::parse($movimiento->fecha)->format('d/m/Y') }}

                            </td>

                            <td>

                                {{ $movimiento->concepto }}

                            </td>

                            <td>

                                {{ $movimiento->categoria->nombre ?? '-' }}

                            </td>

                            <td>

                                @if($movimiento->tipo=='Ingreso')

                                    <span class="badge bg-success">

                                        Ingreso

                                    </span>

                                @else

                                    <span class="badge bg-danger">

                                        Egreso

                                    </span>

                                @endif

                            </td>

                            <td class="text-end">

                                {{ $configuracionGlobal->simbolo_moneda }}

                                {{ number_format($movimiento->monto,2) }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="text-center text-muted py-4">

                                No existen movimientos registrados.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>