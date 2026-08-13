@if ($periodo)

    <div class="periodo-actual-card">

        {{-- =====================================================
             BLOQUE IZQUIERDO
        ====================================================== --}}

        <div class="periodo-actual-left">

            <div class="periodo-actual-header">

                <span class="periodo-actual-label">
                    PERIODO ACTUAL
                </span>

                <span class="periodo-actual-nombre">
                    {{ $periodo->nombre_completo }}
                </span>

                <span class="periodo-actual-estado
                    {{ $periodo->estado === 'Abierto' ? 'abierto' : 'cerrado' }}">

                    {{ $periodo->estado === 'Abierto' ? 'ABIERTO' : 'CERRADO' }}

                </span>

            </div>


            <div class="periodo-actual-linea"></div>


            {{-- =================================================
                 INDICADORES
            ================================================== --}}

            <div class="periodo-actual-metricas">

                {{-- SALDO INICIAL --}}
                <div class="periodo-actual-metrica">

                    <div class="periodo-actual-metrica-label">
                        <span class="metrica-flecha neutral">▸</span>
                        Saldo inicial
                    </div>

                    <div class="periodo-actual-metrica-valor neutral">

                        S/
                        {{ number_format($periodo->saldo_inicial, 2) }}

                    </div>

                </div>


                {{-- INGRESOS --}}
                <div class="periodo-actual-metrica">

                    <div class="periodo-actual-metrica-label">
                        <span class="metrica-flecha ingreso">▸</span>
                        Ingresos del mes
                    </div>

                    <div class="periodo-actual-metrica-valor ingreso">

                        S/
                        {{ number_format($periodo->total_ingresos, 2) }}

                    </div>

                </div>


                {{-- EGRESOS --}}
                <div class="periodo-actual-metrica">

                    <div class="periodo-actual-metrica-label">
                        <span class="metrica-flecha egreso">▸</span>
                        Egresos del mes
                    </div>

                    <div class="periodo-actual-metrica-valor egreso">

                        S/
                        {{ number_format($periodo->total_egresos, 2) }}

                    </div>

                </div>


                {{-- SALDO FINAL --}}
                <div class="periodo-actual-metrica periodo-final">

                    <div class="periodo-actual-metrica-label">
                        <span class="metrica-flecha saldo">▸</span>
                        Saldo final
                    </div>

                    <div class="periodo-actual-metrica-valor saldo">

                        S/
                        {{ number_format($periodo->saldo_final, 2) }}

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             ACCIONES
        ====================================================== --}}

        <div class="periodo-actual-actions">

            <div class="periodo-acciones-titulo">
                Acciones rápidas
            </div>


            <div class="periodo-acciones-botones">

                <a
                    href="{{ route('movimientos.index') }}"
                    class="periodo-btn periodo-btn-secundario">

                    <i class="cil-list"></i>

                    <span>
                        Revisar movimientos
                    </span>

                </a>


                @if ($periodo->estado === 'Abierto')

                    <form
                        id="formCerrarPeriodo"
                        action="{{ route('periodos.cerrar', $periodo) }}"
                        method="POST">

                        @csrf

                        <button
                            type="button"
                            class="periodo-btn periodo-btn-primario"
                            data-coreui-toggle="modal"
                            data-coreui-target="#modalCerrarPeriodo">

                            <i class="cil-lock-locked"></i>

                            <span>
                                Cerrar período
                            </span>

                        </button>

                    </form>

                @endif

            </div>

        </div>

    </div>


    {{-- =========================================================
         MODAL CERRAR PERIODO
    ========================================================== --}}

    @if ($periodo->estado === 'Abierto')

        <div
            class="modal fade"
            id="modalCerrarPeriodo"
            tabindex="-1"
            aria-labelledby="modalCerrarPeriodoLabel"
            aria-hidden="true">

            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">

                    {{-- CABECERA --}}
                    <div class="modal-header">

                        <h5
                            class="modal-title"
                            id="modalCerrarPeriodoLabel">

                            <i class="cil-lock-locked me-2"></i>

                            Cerrar período

                        </h5>

                        <button
                            type="button"
                            class="btn-close"
                            data-coreui-dismiss="modal"
                            aria-label="Cerrar">
                        </button>

                    </div>


                    {{-- CONTENIDO --}}
                    <div class="modal-body">

                        <div class="text-center mb-4">

                            <div class="mb-2">

                                <i
                                    class="cil-lock-locked"
                                    style="font-size:42px;">
                                </i>

                            </div>

                            <div class="fw-bold fs-4 text-primary">

                                {{ $periodo->nombre_completo }}

                            </div>

                            <div class="mt-1">

                                <span class="badge bg-success">

                                    <i class="cil-check-circle me-1"></i>

                                    Período abierto

                                </span>

                            </div>

                            <p class="text-muted mt-3 mb-0">

                                ¿Está seguro de cerrar este período?

                            </p>

                        </div>


                        {{-- RESUMEN --}}
                        <div class="card border-0">

                            <div class="card-header bg-transparent border-0 pb-0">

                                <strong>

                                    <i class="cil-spreadsheet me-2"></i>

                                    Resumen del período

                                </strong>

                            </div>


                            <div class="card-body">

                                <div class="d-flex justify-content-between mb-3">

                                    <span class="text-muted">
                                        Saldo inicial
                                    </span>

                                    <strong>

                                        S/
                                        {{ number_format(
                                            $periodo->saldo_inicial,
                                            2
                                        ) }}

                                    </strong>

                                </div>


                                <div class="d-flex justify-content-between mb-3">

                                    <span class="text-muted">
                                        Ingresos
                                    </span>

                                    <strong class="text-success">

                                        S/
                                        {{ number_format(
                                            $periodo->total_ingresos,
                                            2
                                        ) }}

                                    </strong>

                                </div>


                                <div class="d-flex justify-content-between mb-3">

                                    <span class="text-muted">
                                        Egresos
                                    </span>

                                    <strong class="text-danger">

                                        S/
                                        {{ number_format(
                                            $periodo->total_egresos,
                                            2
                                        ) }}

                                    </strong>

                                </div>


                                <hr>


                                <div class="d-flex justify-content-between">

                                    <span>
                                        <strong>
                                            Saldo final
                                        </strong>
                                    </span>

                                    <strong class="text-primary">

                                        S/
                                        {{ number_format(
                                            $periodo->saldo_final,
                                            2
                                        ) }}

                                    </strong>

                                </div>

                            </div>

                        </div>


                        {{-- ADVERTENCIA --}}
                        <div class="alert alert-warning mt-4 mb-0">

                            <div class="d-flex">

                                <i class="cil-warning me-2 mt-1"></i>

                                <div>

                                    <strong>
                                        Importante
                                    </strong>

                                    <div class="small mt-1">

                                        Una vez cerrado el período,
                                        no se podrán registrar,
                                        editar ni eliminar movimientos
                                        correspondientes a este período.

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- BOTONES --}}
                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-coreui-dismiss="modal">

                            Cancelar

                        </button>


                        <button
                            type="submit"
                            form="formCerrarPeriodo"
                            class="btn btn-sigefiv-primary">

                            <i class="cil-lock-locked me-2"></i>

                            Sí, cerrar período

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

            min-height: 148px;

            background:
                linear-gradient(
                    135deg,
                    #172033 0%,
                    #1c2940 100%
                );

            border: 1px solid rgba(255,255,255,.08);

            border-radius: 14px;

            overflow: hidden;

            box-shadow:
                0 7px 22px rgba(0,0,0,.14);

            color: #ffffff;

        }


        /* =====================================================
           PARTE IZQUIERDA
        ====================================================== */

        .periodo-actual-left {

            flex: 1;

            min-width: 0;

            padding: 18px 22px;

        }


        .periodo-actual-header {

            display: flex;

            align-items: center;

            gap: 14px;

            min-height: 27px;

        }


        .periodo-actual-label {

            color: #ffffff;

            font-size: 11px;

            font-weight: 700;

            letter-spacing: .4px;

        }


        .periodo-actual-nombre {

            color: #ffffff;

            font-size: 17px;

            font-weight: 700;

        }


        .periodo-actual-estado {

            display: inline-flex;

            align-items: center;

            padding: 5px 11px;

            border-radius: 20px;

            font-size: 10px;

            font-weight: 800;

            letter-spacing: .3px;

        }


        .periodo-actual-estado.abierto {

            background: #195d42;

            color: #8df0bd;

        }


        .periodo-actual-estado.cerrado {

            background: #61343a;

            color: #ff9da4;

        }


        /* =====================================================
           LINEA
        ====================================================== */

        .periodo-actual-linea {

            height: 1px;

            margin: 12px 0 13px;

            background:
                rgba(255,255,255,.10);

            max-width: 650px;

        }


        /* =====================================================
           METRICAS
        ====================================================== */

        .periodo-actual-metricas {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(130px, 1fr));

            max-width: 700px;

        }


        .periodo-actual-metrica {

            padding: 0 24px;

            border-right:
                1px solid rgba(255,255,255,.12);

        }


        .periodo-actual-metrica:first-child {

            padding-left: 8px;

        }


        .periodo-actual-metrica:last-child {

            border-right: none;

        }


        .periodo-actual-metrica-label {

            margin-bottom: 6px;

            color: #c2cad7;

            font-size: 11px;

            font-weight: 500;

            white-space: nowrap;

        }


        .metrica-flecha {

            margin-right: 4px;

            font-size: 10px;

        }


        .metrica-flecha.neutral {

            color: #7ed8d6;

        }


        .metrica-flecha.ingreso {

            color: #22c55e;

        }


        .metrica-flecha.egreso {

            color: #ff5c5c;

        }


        .metrica-flecha.saldo {

            color: #4389ff;

        }


        .periodo-actual-metrica-valor {

            color: #f8fafc;

            font-size: 20px;

            line-height: 1.1;

            font-weight: 800;

            white-space: nowrap;

        }


        .periodo-actual-metrica-valor.ingreso {

            color: #20c95a;

        }


        .periodo-actual-metrica-valor.egreso {

            color: #ff5656;

        }


        .periodo-actual-metrica-valor.saldo {

            color: #3f82ff;

        }


        /* =====================================================
           ACCIONES
        ====================================================== */

        .periodo-actual-actions {

            width: 355px;

            flex-shrink: 0;

            display: flex;

            flex-direction: column;

            justify-content: center;

            padding: 18px 22px;

            border-left:
                1px solid rgba(255,255,255,.10);

        }


        .periodo-acciones-titulo {

            margin-bottom: 12px;

            color: #f0f3f8;

            font-size: 13px;

            font-weight: 700;

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

            min-height: 40px;

            padding: 0 14px;

            border-radius: 7px;

            font-size: 11px;

            font-weight: 700;

            text-decoration: none;

            transition:
                background .2s ease,
                border-color .2s ease,
                transform .2s ease;

            white-space: nowrap;

        }


        .periodo-btn i {

            font-size: 15px;

        }


        .periodo-btn-secundario {

            color: #ffffff;

            background: transparent;

            border: 1px solid #4d78aa;

        }


        .periodo-btn-secundario:hover {

            color: #ffffff;

            background: rgba(67,137,255,.10);

            border-color: #6194d2;

        }


        .periodo-btn-primario {

            color: #ffffff;

            background: #2864dd;

            border: 1px solid #2864dd;

            cursor: pointer;

        }


        .periodo-btn-primario:hover {

            background: #3574ef;

            border-color: #3574ef;

            transform: translateY(-1px);

        }


        /* =====================================================
           RESPONSIVE
        ====================================================== */

        @media (max-width: 1100px) {

            .periodo-actual-card {

                flex-direction: column;

            }


            .periodo-actual-actions {

                width: auto;

                border-left: none;

                border-top:
                    1px solid rgba(255,255,255,.10);

            }


            .periodo-actual-metricas {

                max-width: none;

            }

        }


        @media (max-width: 700px) {

            .periodo-actual-header {

                flex-wrap: wrap;

                gap: 8px 12px;

            }


            .periodo-actual-metricas {

                grid-template-columns:
                    repeat(2, 1fr);

                gap: 16px 0;

            }


            .periodo-actual-metrica {

                padding: 0 14px;

            }


            .periodo-actual-metrica:first-child {

                padding-left: 0;

            }


            .periodo-actual-acciones-botones {

                flex-direction: column;

            }

        }


        @media (max-width: 480px) {

            .periodo-actual-metricas {

                grid-template-columns: 1fr;

            }


            .periodo-actual-metrica {

                padding: 0 0 12px;

                border-right: none;

                border-bottom:
                    1px solid rgba(255,255,255,.08);

            }


            .periodo-actual-metrica:last-child {

                border-bottom: none;

            }


            .periodo-acciones-botones {

                flex-direction: column;

            }

        }

    </style>

@endif