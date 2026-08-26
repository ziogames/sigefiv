<div class="sidebar sidebar-fixed border-end" id="sidebar">

    <div class="sidebar-header border-bottom">

        <div class="sidebar-brand d-flex flex-column align-items-center py-3">

            @if (!empty($configuracionGlobal?->logo))

                <img
                    src="{{ asset('storage/' . $configuracionGlobal->logo) }}"
                    alt="Logo"
                    style="height:55px;"
                    class="mb-2 brand-logo-tech">

            @endif

            <span class="sidebar-brand-title fw-bold">

                {{ $configuracionGlobal->nombre_sistema ?? 'SIGEFIV' }}

            </span>

            <small class="sidebar-brand-sub">

                {{ $configuracionGlobal->organizacion ?? '' }}

            </small>

        </div>

        <button
            class="btn-close d-lg-none"
            type="button"
            onclick="toggleSidebarMovil()">
        </button>

    </div>


    <ul
        class="sidebar-nav"
        data-coreui="navigation"
        data-simplebar>


        {{-- =========================================================
             DASHBOARD
        ========================================================== --}}

        @can('dashboard')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('dashboard') }}">

                    <i class="nav-icon cil-speedometer"></i>

                    <span>
                        Dashboard
                    </span>

                </a>

            </li>

        @endcan


        {{-- =========================================================
             ASISTENTE SIGI
        ========================================================== --}}

        <li class="nav-title sigi-nav-title">
            ASISTENTE
        </li>

        <li class="nav-item">

            <a
                class="nav-link sigi-nav-link"
                href="{{ route('sigi.index') }}">

                <span class="sigi-nav-icon">
                    🤖
                </span>

                <span class="sigi-nav-text">
                    SIGI
                </span>

                <span class="sigi-nav-badge">
                    IA
                </span>

            </a>

        </li>


        {{-- =========================================================
             CONTABILIDAD
        ========================================================== --}}

        <li class="nav-title">
            CONTABILIDAD
        </li>


        @can('reportes.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('reportes.index') }}">

                    <i class="nav-icon cil-chart"></i>

                    <span>
                        Reportes
                    </span>

                </a>

            </li>

        @endcan


        @can('movimientos.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('movimientos.index') }}">

                    <i class="nav-icon cil-transfer"></i>

                    <span>
                        Movimientos
                    </span>

                </a>

            </li>

        @endcan


        @can('caja.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('caja.index') }}">

                    <i class="nav-icon cil-wallet"></i>

                    <span>
                        Caja
                    </span>

                </a>

            </li>

        @endcan


        {{-- =========================================================
             COMUNIDAD
        ========================================================== --}}

        @can('asambleas.index')

            <li class="nav-title">
                COMUNIDAD
            </li>

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('asambleas.index') }}">

                    <i class="nav-icon cil-bullhorn"></i>

                    <span>
                        Asambleas
                    </span>

                </a>

            </li>

        @endcan


        {{-- =========================================================
             ADMINISTRACIÓN
        ========================================================== --}}

        <li class="nav-title">
            ADMINISTRACIÓN
        </li>


        @can('usuarios.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('usuarios.index') }}">

                    <i class="nav-icon cil-user"></i>

                    <span>
                        Usuarios
                    </span>

                </a>

            </li>

        @endcan


        @can('roles.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('roles.index') }}">

                    <i class="nav-icon cil-shield-alt"></i>

                    <span>
                        Roles
                    </span>

                </a>

            </li>

        @endcan


        <li class="nav-divider"></li>


        @can('configuracion')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('configuracion.index') }}">

                    <i class="nav-icon cil-settings"></i>

                    <span>
                        Configuración
                    </span>

                </a>

            </li>

        @endcan


        @can('categorias.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('categorias.index') }}">

                    <i class="nav-icon cil-folder"></i>

                    <span>
                        Categorías
                    </span>

                </a>

            </li>

        @endcan


        @can('bitacora.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('bitacora.index') }}">

                    <i class="nav-icon cil-notes"></i>

                    <span>
                        Bitácora
                    </span>

                </a>

            </li>

        @endcan


        {{-- =========================================================
             ACTIVIDAD DE USUARIOS
        ========================================================== --}}

        @can('actividad.index')

            <li class="nav-item">

                <a
                    class="nav-link"
                    href="{{ route('actividad.index') }}">

                    <i class="nav-icon cil-chart-line"></i>

                    <span>
                        Actividad
                    </span>

                </a>

            </li>

        @endcan


        <li class="nav-divider"></li>


        {{-- =========================================================
             CERRAR SESIÓN
        ========================================================== --}}

        <li class="nav-item mt-auto">

            <form
                method="POST"
                action="{{ route('logout') }}">

                @csrf

                <button
                    type="submit"
                    class="nav-link border-0 bg-transparent text-start w-100">

                    <i class="nav-icon cil-account-logout"></i>

                    <span>
                        Cerrar sesión
                    </span>

                </button>

            </form>

        </li>


        <li class="nav-divider"></li>


        {{-- =========================================================
             SISTEMA
        ========================================================== --}}

        <li class="nav-title">
            SISTEMA
        </li>


        <li class="nav-item">

            <a
                class="nav-link"
                href="{{ route('perfil.index') }}">

                <i class="nav-icon cil-user"></i>

                <span>
                    Mi Cuenta
                </span>

            </a>

        </li>

    </ul>


    <div class="sidebar-footer border-top d-none d-lg-flex">

        <button
            class="sidebar-toggler"
            type="button"
            data-coreui-toggle="unfoldable">
        </button>

    </div>

</div>


<style>

    /* =========================================================
       VARIABLES DE TEMA
    ========================================================== */

    :root,
    [data-theme="dark"] {

        --sb-bg: #1e293b;
        --sb-border: rgba(255, 255, 255, 0.08);
        --sb-text: #94a3b8;
        --sb-title-text: #f8fafc;
        --sb-hover-bg: rgba(255, 255, 255, 0.04);
        --sb-hover-text: #ffffff;
        --sb-active-bg: rgba(56, 189, 248, 0.1);
        --sb-active-text: #38bdf8;
        --sb-laser-color: #38bdf8;

    }


    [data-theme="light"],
    .light-theme {

        --sb-bg: #ffffff;
        --sb-border: #e2e8f0;
        --sb-text: #64748b;
        --sb-title-text: #0f172a;
        --sb-hover-bg: #f8fafc;
        --sb-hover-text: #0f172a;
        --sb-active-bg: rgba(2, 132, 199, 0.08);
        --sb-active-text: #0284c7;
        --sb-laser-color: #0284c7;

    }


    /* =========================================================
       ESTILOS DEL CONTENEDOR
    ========================================================== */

    #sidebar.sidebar {

        background-color: var(--sb-bg) !important;

        border-color: var(--sb-border) !important;

        border-radius: 0 !important;

        transition:
            background-color 0.3s ease,
            border-color 0.3s ease;

    }


    .sidebar-header,
    .sidebar-footer {

        background-color: var(--sb-bg) !important;

        border-color: var(--sb-border) !important;

        border-radius: 0 !important;

    }


    .sidebar-brand-title {

        color: var(--sb-title-text) !important;

    }


    .sidebar-brand-sub {

        color: var(--sb-text) !important;

    }


    .brand-logo-tech {

        transition: filter 0.3s ease;

    }


    .sidebar-brand:hover .brand-logo-tech {

        filter:
            drop-shadow(
                0 0 6px var(--sb-laser-color)
            );

    }


    #sidebar .nav-title {

        color: var(--sb-text) !important;

        opacity: 0.6;

        font-weight: 700;

        font-size: 10px;

        letter-spacing: 0.1em;

        text-transform: uppercase;

    }


    /* =========================================================
       BOTONES Y ANIMACIÓN LÁSER
    ========================================================== */

    #sidebar .nav-link {

        position: relative;

        color: var(--sb-text) !important;

        padding: 11px 16px;

        margin: 2px 0;

        border-radius: 0 !important;

        display: flex;

        align-items: center;

        gap: 12px;

        border-left: 2px solid transparent;

        background-color: transparent;

        transition:
            background-color 0.2s ease,
            color 0.2s ease,
            border-color 0.2s ease;

    }


    /* Ícono */

    #sidebar .nav-link i.nav-icon {

        color: inherit !important;

        transition:
            transform 0.2s ease,
            color 0.2s ease;

    }


    /* LÍNEA LÁSER VERTICAL */

    #sidebar .nav-link::before {

        content: '';

        position: absolute;

        left: 0;

        top: 50%;

        width: 3px;

        height: 0%;

        background-color: var(--sb-laser-color);

        box-shadow:
            0 0 8px var(--sb-laser-color);

        transition:
            height 0.25s ease,
            top 0.25s ease;

    }


    /* HOVER */

    #sidebar .nav-link:hover {

        background-color: var(--sb-hover-bg) !important;

        color: var(--sb-hover-text) !important;

    }


    #sidebar .nav-link:hover::before {

        top: 0%;

        height: 100%;

    }


    #sidebar .nav-link:hover i.nav-icon {

        transform: translateX(3px);

        color: var(--sb-laser-color) !important;

    }


    /* BOTÓN ACTIVO */

    #sidebar .nav-link.active {

        background-color: var(--sb-active-bg) !important;

        color: var(--sb-active-text) !important;

        border-left:
            3px solid var(--sb-active-text) !important;

        font-weight: 600;

    }


    #sidebar .nav-link.active::before {

        display: none;

    }


    /* =========================================================
       ASISTENTE SIGI
    ========================================================== */

    .sigi-nav-link {

        border-radius: 0 !important;

        border:
            1px solid rgba(139, 92, 246, 0.3) !important;

        background:
            linear-gradient(
                90deg,
                rgba(139, 92, 246, 0.12),
                transparent
            ) !important;

        margin: 4px 8px !important;

        width: calc(100% - 16px) !important;

    }


    .sigi-nav-icon {

        width: 24px;

        height: 24px;

        display: flex;

        align-items: center;

        justify-content: center;

        border-radius: 0 !important;

        background: rgba(139, 92, 246, 0.25);

        font-size: 13px;

    }


    .sigi-nav-text {

        font-size: 12px;

        font-weight: 800;

        letter-spacing: 0.05em;

    }


    .sigi-nav-badge {

        padding: 2px 5px;

        border-radius: 0 !important;

        background: #8b5cf6;

        color: #ffffff;

        font-size: 8px;

        font-weight: 900;

    }

</style>


<script>

    function toggleSidebarMovil() {

        const sidebar =
            document.getElementById('sidebar');

        if (!sidebar) return;


        const instancia =
            coreui.Sidebar.getInstance(sidebar);


        if (instancia) {

            instancia.toggle();

        }

    }

</script>