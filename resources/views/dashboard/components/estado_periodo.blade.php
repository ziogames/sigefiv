@if ($periodo)

    <div class="periodo-actual-card">

        {{-- =====================================================
             BLOQUE IZQUIERDO: METRICAS Y DETALLES
        ====================================================== --}}
        <div class="periodo-actual-left">

            <div class="periodo-actual-header">
                <span class="periodo-actual-label">PERÍODO ACTUAL</span>
                <span class="periodo-actual-nombre">{{ $periodo->nombre_completo }}</span>
                <span class="periodo-actual-estado {{ $periodo->estado === 'Abierto' ? 'abierto' : 'cerrado' }}">
                    <span class="estado-dot"></span>
                    {{ $periodo->estado === 'Abierto' ? 'ABIERTO' : 'CERRADO' }}
                </span>
            </div>

            <div class="periodo-actual-linea"></div>

            {{-- INDICADORES --}}
            <div class="periodo-actual-metricas">

                {{-- SALDO INICIAL --}}
                <div class="periodo-actual-metrica">
                    <div class="periodo-actual-metrica-label">
                        <svg class="metrica-icon neutral" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        Saldo inicial
                    </div>
                    <div class="periodo-actual-metrica-valor neutral">
                        {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->saldo_inicial, 2) }}
                    </div>
                </div>

                {{-- INGRESOS --}}
                <div class="periodo-actual-metrica">
                    <div class="periodo-actual-metrica-label">
                        <svg class="metrica-icon ingreso" viewBox="0 0 24 24"><path d="M7 17L17 7M17 7H7M17 7V17"/></svg>
                        Ingresos del mes
                    </div>
                    <div class="periodo-actual-metrica-valor ingreso">
                        {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->total_ingresos, 2) }}
                    </div>
                </div>

                {{-- EGRESOS --}}
                <div class="periodo-actual-metrica">
                    <div class="periodo-actual-metrica-label">
                        <svg class="metrica-icon egreso" viewBox="0 0 24 24"><path d="M7 7l10 10M17 7v10M17 17H7"/></svg>
                        Egresos del mes
                    </div>
                    <div class="periodo-actual-metrica-valor egreso">
                        {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->total_egresos, 2) }}
                    </div>
                </div>

                {{-- SALDO FINAL --}}
                <div class="periodo-actual-metrica periodo-final">
                    <div class="periodo-actual-metrica-label">
                        <svg class="metrica-icon saldo" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Saldo final
                    </div>
                    <div class="periodo-actual-metrica-valor saldo">
                        {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->saldo_final, 2) }}
                    </div>
                </div>

            </div>

        </div>


        {{-- =====================================================
             BLOQUE DERECHO: ACCIONES
        ====================================================== --}}
        <div class="periodo-actual-actions">

            <div class="periodo-acciones-titulo">Acciones rápidas</div>

            <div class="periodo-acciones-botones">

                <a href="{{ route('movimientos.index') }}" class="periodo-btn periodo-btn-secundario">
                    <i class="cil-list"></i>
                    <span>Revisar movimientos</span>
                </a>

                @if ($periodo->estado === 'Abierto')
                    <form id="formCerrarPeriodo" action="{{ route('periodos.cerrar', $periodo) }}" method="POST">
                        @csrf
                        <button type="button" class="periodo-btn periodo-btn-danger" data-coreui-toggle="modal" data-coreui-target="#modalCerrarPeriodo">
                            <i class="cil-lock-locked"></i>
                            <span>Cerrar período</span>
                        </button>
                    </form>
                @endif

            </div>

        </div>

    </div>


    {{-- =========================================================
         MODAL CERRAR PERIODO (DARK STYLED)
    ========================================================== --}}
    @if ($periodo->estado === 'Abierto')

        <div class="modal fade" id="modalCerrarPeriodo" tabindex="-1" aria-labelledby="modalCerrarPeriodoLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-dark">

                    {{-- CABECERA --}}
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title d-flex align-items-center gap-2" id="modalCerrarPeriodoLabel">
                            <i class="cil-lock-locked text-warning"></i>
                            <span>Cerrar Período Contable</span>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-coreui-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    {{-- CONTENIDO --}}
                    <div class="modal-body py-4">

                        <div class="text-center mb-4">
                            <div class="modal-icon-wrapper mb-3">
                                <i class="cil-lock-locked"></i>
                            </div>
                            <h4 class="fw-bold text-white mb-1">{{ $periodo->nombre_completo }}</h4>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                                <i class="cil-check-circle me-1"></i> Período Activo
                            </span>
                            <p class="text-muted small mt-3 mb-0">
                                ¿Estás seguro de que deseas finalizar este período?
                            </p>
                        </div>

                        {{-- RESUMEN --}}
                        <div class="resumen-box p-3 rounded-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Saldo inicial</span>
                                <span class="text-white fw-semibold small">{{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->saldo_inicial, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Ingresos</span>
                                <span class="text-success fw-semibold small">+ {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->total_ingresos, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Egresos</span>
                                <span class="text-danger fw-semibold small">- {{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->total_egresos, 2) }}</span>
                            </div>
                            <div class="border-top border-secondary my-2"></div>
                            <div class="d-flex justify-content-between">
                                <span class="text-white fw-bold small">Saldo final proyectado</span>
                                <span class="text-info fw-bold small">{{ $configuracionGlobal->simbolo_moneda ?? 'S/' }} {{ number_format($periodo->saldo_final, 2) }}</span>
                            </div>
                        </div>

                        {{-- ADVERTENCIA --}}
                        <div class="alert alert-warning-custom d-flex gap-3 align-items-center m-0 p-3 rounded-3">
                            <i class="cil-warning fs-4 flex-shrink-0"></i>
                            <div class="small">
                                <strong>Acción irreversible:</strong> Una vez cerrado, no podrás registrar, modificar ni eliminar transacciones dentro de este rango de fechas.
                            </div>
                        </div>

                    </div>

                    {{-- BOTONES --}}
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-light btn-sm px-3" data-coreui-dismiss="modal">Cancelar</button>
                        <button type="submit" form="formCerrarPeriodo" class="btn btn-warning btn-sm px-3 fw-bold d-flex align-items-center gap-2">
                            <i class="cil-lock-locked"></i> Confirmar y cerrar
                        </button>
                    </div>

                </div>
            </div>
        </div>

    @endif


    {{-- =========================================================
         ESTILOS
    ========================================================== --}}
    <style>
        /* =====================================================
           TARJETA PRINCIPAL
        ====================================================== */
        .periodo-actual-card {
            display: flex;
            align-items: stretch;
            width: 100%;
            min-height: 140px;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            color: #f8fafc;
            margin-top: 20px;
        }

        /* =====================================================
           PARTE IZQUIERDA
        ====================================================== */
        .periodo-actual-left {
            flex: 1;
            min-width: 0;
            padding: 20px 24px;
        }

        .periodo-actual-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .periodo-actual-label {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .periodo-actual-nombre {
            color: #f8fafc;
            font-size: 16px;
            font-weight: 700;
        }

        .periodo-actual-estado {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        .periodo-actual-estado .estado-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: currentColor;
        }

        .periodo-actual-estado.abierto {
            background: rgba(52, 211, 153, 0.12);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.2);
        }

        .periodo-actual-estado.cerrado {
            background: rgba(248, 113, 113, 0.12);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.2);
        }

        .periodo-actual-linea {
            height: 1px;
            margin: 14px 0 16px;
            background: rgba(255, 255, 255, 0.08);
            max-width: 100%;
        }

        /* =====================================================
           MÉTRICAS
        ====================================================== */
        .periodo-actual-metricas {
            display: grid;
            grid-template-columns: repeat(4, minmax(120px, 1fr));
            gap: 12px;
        }

        .periodo-actual-metrica {
            padding-right: 16px;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .periodo-actual-metrica:last-child {
            border-right: none;
        }

        .periodo-actual-metrica-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 500;
            white-space: nowrap;
        }

        .metrica-icon {
            width: 14px;
            height: 14px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .metrica-icon.neutral { color: #38bdf8; }
        .metrica-icon.ingreso { color: #34d399; }
        .metrica-icon.egreso  { color: #f87171; }
        .metrica-icon.saldo   { color: #fbbf24; }

        .periodo-actual-metrica-valor {
            color: #f8fafc;
            font-size: 18px;
            line-height: 1.1;
            font-weight: 700;
            white-space: nowrap;
        }

        .periodo-actual-metrica-valor.ingreso { color: #34d399; }
        .periodo-actual-metrica-valor.egreso  { color: #f87171; }
        .periodo-actual-metrica-valor.saldo   { color: #38bdf8; }

        /* =====================================================
           ACCIONES Y BOTONES
        ====================================================== */
        .periodo-actual-actions {
            width: 320px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 20px 24px;
            background: rgba(15, 23, 42, 0.4);
            border-left: 1px solid rgba(255, 255, 255, 0.08);
        }

        .periodo-acciones-titulo {
            margin-bottom: 10px;
            color: #f8fafc;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .periodo-acciones-botones {
            display: flex;
            gap: 10px;
        }

        .periodo-acciones-botones form {
            margin: 0;
            flex: 1;
        }

        .periodo-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 38px;
            padding: 0 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            cursor: pointer;
        }

        .periodo-btn-secundario {
            color: #f8fafc;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .periodo-btn-secundario:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .periodo-btn-danger {
            color: #f87171;
            background: rgba(248, 113, 113, 0.1);
            border: 1px solid rgba(248, 113, 113, 0.2);
        }

        .periodo-btn-danger:hover {
            background: rgba(248, 113, 113, 0.2);
            border-color: rgba(248, 113, 113, 0.3);
            color: #ffffff;
        }

        /* =====================================================
           MODAL DARK DESIGN
        ====================================================== */
        .modal-dark {
            background-color: #1e293b;
            color: #f8fafc;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }

        .modal-icon-wrapper {
            width: 56px;
            height: 56px;
            margin: 0 auto;
            border-radius: 50%;
            background: rgba(251, 191, 36, 0.1);
            border: 1px solid rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .resumen-box {
            background-color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .alert-warning-custom {
            background-color: rgba(251, 191, 36, 0.08);
            border: 1px solid rgba(251, 191, 36, 0.2);
            color: #fef08a;
        }

        /* =====================================================
           RESPONSIVE
        ====================================================== */
        @media (max-width: 1100px) {
            .periodo-actual-card {
                flex-direction: column;
            }

            .periodo-actual-actions {
                width: 100%;
                border-left: none;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
            }
        }

        @media (max-width: 700px) {
            .periodo-actual-metricas {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }

            .periodo-actual-metrica {
                border-right: none;
            }
        }

        @media (max-width: 480px) {
            .periodo-actual-metricas {
                grid-template-columns: 1fr;
            }

            .periodo-acciones-botones {
                flex-direction: column;
            }
        }
    </style>

@endif