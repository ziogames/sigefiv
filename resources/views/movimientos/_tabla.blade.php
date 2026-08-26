<div class="mov-section">

    {{-- ENCABEZADO DE SECCIÓN --}}
    <div class="mov-table-header">
        <div>
            <h5 class="mov-table-title">
                <i class="cil-list me-2"></i> DETALLE DE MOVIMIENTOS
            </h5>
            <small class="mov-table-sub">Registro detallado de ingresos y egresos</small>
        </div>

        <span class="mov-count-badge">
            {{ $movimientos->total() }} REGISTROS
        </span>
    </div>

    @php
        $movimientosPorFecha = $movimientos
            ->getCollection()
            ->groupBy(function ($movimiento) {
                return $movimiento->fecha->format('Y-m-d');
            });
    @endphp

    <div class="mov-days-container">

        @forelse($movimientosPorFecha as $fecha => $movimientosDelDia)

            @php
                $primerMovimiento = $movimientosDelDia->first();
                $ingresosDia = $movimientosDelDia->where('tipo', 'Ingreso')->sum('monto');
                $egresosDia = $movimientosDelDia->where('tipo', 'Egreso')->sum('monto');
            @endphp

            {{-- GRUPO DEL DÍA --}}
            <section class="mov-day-group">

                {{-- CABECERA DEL DÍA --}}
                <div class="mov-day-header">
                    <div class="mov-day-title">
                        <i class="cil-calendar me-2"></i>
                        <span>
                            {{ ucfirst($primerMovimiento->fecha->locale('es')->translatedFormat('l, d \d\e F \d\e Y')) }}
                        </span>
                    </div>

                    <div class="mov-day-summary">
                        <span class="summary-count">
                            {{ $movimientosDelDia->count() }} {{ $movimientosDelDia->count() === 1 ? 'movimiento' : 'movimientos' }}
                        </span>

                        @if($ingresosDia > 0)
                            <span class="mov-day-badge income">
                                Ingresos: + S/ {{ number_format($ingresosDia, 2) }}
                            </span>
                        @endif

                        @if($egresosDia > 0)
                            <span class="mov-day-badge expense">
                                Egresos: - S/ {{ number_format($egresosDia, 2) }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- TABLA DE DATOS DEL DÍA --}}
                <div class="table-responsive">
                    <table class="mov-table">
                        <thead>
                            <tr>
                                <th>TIPO</th>
                                <th>CATEGORÍA</th>
                                <th>CONCEPTO</th>
                                <th>PERSONA / REFERENCIA</th>
                                <th>ESTADO</th>
                                <th class="text-end">MONTO</th>
                                <th class="text-center" style="width: 90px;">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movimientosDelDia as $movimiento)
                                @php $esIngreso = $movimiento->tipo === 'Ingreso'; @endphp
                                <tr>
                                    {{-- TIPO --}}
                                    <td>
                                        <span class="type-badge {{ $esIngreso ? 'type-income' : 'type-expense' }}">
                                            <i class="{{ $esIngreso ? 'cil-arrow-top' : 'cil-arrow-bottom' }}"></i>
                                            {{ $movimiento->tipo }}
                                        </span>
                                    </td>

                                    {{-- CATEGORÍA --}}
                                    <td>
                                        <span class="text-main fw-bold">
                                            {{ $movimiento->categoria->nombre ?? '—' }}
                                        </span>
                                    </td>

                                    {{-- CONCEPTO --}}
                                    <td>
                                        <span class="text-muted">
                                            {{ $movimiento->concepto ?: '—' }}
                                        </span>
                                    </td>

                                    {{-- PERSONA --}}
                                    <td>
                                        <span class="text-main">
                                            {{ $movimiento->persona ?: '—' }}
                                        </span>
                                    </td>

                                    {{-- ESTADO --}}
                                    <td>
                                        @if($movimiento->estado === 'Registrado')
                                            <span class="status-tag status-active">
                                                <span class="dot"></span> Registrado
                                            </span>
                                        @else
                                            <span class="status-tag status-cancelled">
                                                <span class="dot"></span> Anulado
                                            </span>
                                        @endif
                                    </td>

                                    {{-- MONTO --}}
                                    <td class="text-end fw-bold {{ $esIngreso ? 'text-income' : 'text-expense' }}">
                                        {{ $esIngreso ? '+' : '-' }} S/ {{ number_format($movimiento->monto, 2) }}
                                    </td>

                                    {{-- ACCIONES --}}
                                    <td class="text-center">
                                        <div class="table-actions">
                                            @can('movimientos.edit')
                                                <a href="{{ route('movimientos.edit', $movimiento) }}" class="btn-action edit" title="Editar">
                                                    <i class="cil-pencil"></i>
                                                </a>
                                            @endcan

                                            @can('movimientos.destroy')
                                                <form action="{{ route('movimientos.destroy', $movimiento) }}" method="POST" class="d-inline form-eliminar-movimiento">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-action delete" title="Eliminar">
                                                        <i class="cil-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </section>

        @empty

            <div class="mov-empty">
                <i class="cil-folder-open mov-empty-icon"></i>
                <div class="mt-2">No existen movimientos registrados.</div>
            </div>

        @endforelse

    </div>

    {{-- PAGINACIÓN --}}
    <div class="mov-footer">
        <div class="mov-footer-info">
            Mostrando <strong>{{ $movimientos->firstItem() ?? 0 }}</strong> a <strong>{{ $movimientos->lastItem() ?? 0 }}</strong> de <strong>{{ $movimientos->total() }}</strong> registros
        </div>

        <div class="mov-pagination">
            {{ $movimientos->links() }}
        </div>
    </div>

</div>

<style>
/* =========================================================
   CONTENEDOR GENERAL Y ENCABEZADOS
========================================================= */
.mov-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--sb-border, rgba(255, 255, 255, 0.08));
}

.mov-table-title {
    margin: 0;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-main, #f8fafc);
}

.mov-table-sub {
    font-size: 11px;
    color: var(--text-muted, #94a3b8);
}

.mov-count-badge {
    padding: 4px 10px;
    background: var(--input-bg, rgba(15, 23, 42, 0.6));
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.12));
    color: var(--text-muted, #94a3b8);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.05em;
}

/* =========================================================
   GRUPO DEL DÍA Y BARRAS
========================================================= */
.mov-day-group {
    margin-bottom: 24px;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
    background: var(--card-bg, #1e293b);
}

.mov-day-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: var(--input-bg, rgba(15, 23, 42, 0.4));
    border-bottom: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
}

.mov-day-title {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-main, #f8fafc);
    display: flex;
    align-items: center;
}

.mov-day-summary {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 11px;
}

.summary-count { color: var(--text-muted, #94a3b8); }

.mov-day-badge {
    padding: 2px 8px;
    font-size: 10px;
    font-weight: 700;
}

.mov-day-badge.income { background: rgba(52, 211, 153, 0.12); color: #34d399; }
.mov-day-badge.expense { background: rgba(248, 113, 113, 0.12); color: #f87171; }

/* =========================================================
   TABLA INDUSTRIAL (BORDES RECTOS)
========================================================= */
.mov-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
    color: var(--text-main, #f8fafc);
}

.mov-table th {
    padding: 10px 14px;
    background: rgba(0, 0, 0, 0.15);
    color: var(--text-muted, #94a3b8);
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border-bottom: 1px solid var(--card-border, rgba(255, 255, 255, 0.08));
    text-align: left;
}

.mov-table td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--sb-border, rgba(255, 255, 255, 0.04));
    vertical-align: middle;
}

.mov-table tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.02);
}

/* TIPO BADGE */
.type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
}

.type-income { background: rgba(52, 211, 153, 0.12); color: #34d399; }
.type-expense { background: rgba(248, 113, 113, 0.12); color: #f87171; }

/* TEXTOS DE MONTO */
.text-income { color: #34d399 !important; }
.text-expense { color: #f87171 !important; }

/* ESTADO TAG */
.status-tag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--text-muted, #94a3b8);
}

.status-tag .dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.status-active .dot { background-color: #34d399; }
.status-cancelled .dot { background-color: #f87171; }

/* =========================================================
   BOTONES DE ACCIÓN
========================================================= */
.table-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.btn-action {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--card-border, rgba(255, 255, 255, 0.12));
    background: transparent;
    color: var(--text-muted, #94a3b8);
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-action.edit:hover { background: #38bdf8; color: #0f172a; border-color: #0284c7; }
.btn-action.delete:hover { background: #f87171; color: #ffffff; border-color: #dc2626; }

/* =========================================================
   PAGINACIÓN Y PIE
========================================================= */
.mov-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 16px;
    border-top: 1px solid var(--sb-border, rgba(255, 255, 255, 0.08));
}

.mov-footer-info {
    font-size: 12px;
    color: var(--text-muted, #94a3b8);
}

.mov-empty {
    text-align: center;
    padding: 40px;
    color: var(--text-muted, #94a3b8);
}

.mov-empty-icon {
    font-size: 32px;
    opacity: 0.5;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.form-eliminar-movimiento').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            if (typeof Swal === 'undefined') {
                if (confirm('¿Deseas eliminar este movimiento?')) {
                    form.submit();
                }
                return;
            }

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