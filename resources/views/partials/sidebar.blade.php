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
             CHAT VECINAL
        ========================================================== --}}

        <li class="nav-title chat-nav-title">
            COMUNIDAD
        </li>

        <li class="nav-item">

            <a
                class="nav-link chat-nav-link"
                href="{{ route('chat.index') }}"
                id="sidebarChatLink"
            >

                <span class="chat-nav-icon">
                    <i class="cil-chat-bubble"></i>
                </span>

                <span class="chat-nav-content">
                    <span class="chat-nav-text">
                        Chat Vecinal
                    </span>

                    <span
                        class="chat-nav-meta"
                        id="sidebarChatMeta"
                    >
                        <span id="sidebarChatOnline">…</span>
                        en línea
                        <span class="chat-nav-separator">•</span>
                        <span id="sidebarChatPeople">…</span>
                        personas
                    </span>
                </span>

                <span
                    class="chat-nav-unread d-none"
                    id="sidebarChatUnread"
                >
                    0
                </span>

            </a>

        </li>


        {{-- =========================================================
             ASISTENTE ZOE
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
                    ZOE
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
       CHAT VECINAL
    ========================================================== */

    .chat-nav-link {
        display: flex !important;
        align-items: center !important;
        gap: 10px;
        min-height: 52px;
        border: 1px solid rgba(14,165,233,.20) !important;
        background: linear-gradient(90deg,rgba(14,165,233,.10),transparent) !important;
        margin: 4px 8px !important;
        width: calc(100% - 16px) !important;
        border-radius: 11px !important;
        transition: background-color .2s ease,border-color .2s ease,transform .2s ease;
    }

    .chat-nav-icon {
        width: 30px;
        height: 30px;
        min-width: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 9px;
        background: rgba(14,165,233,.17);
        color: #0ea5e9;
        font-size: 14px;
        transition: transform .2s ease,background-color .2s ease;
    }

    .chat-nav-content {
        min-width: 0;
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        line-height: 1.15;
    }

    .chat-nav-text {
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .02em;
    }

    .chat-nav-meta {
        margin-top: 4px;
        color: #8b98a9;
        font-size: 9px;
        font-weight: 500;
        white-space: nowrap;
    }

    .chat-nav-separator {
        margin: 0 3px;
        opacity: .7;
    }

    .chat-nav-unread {
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 9px;
        font-weight: 900;
        box-shadow: 0 3px 9px rgba(239,68,68,.25);
        animation: chatSidebarPulse 1.8s ease-in-out infinite;
    }

    @keyframes chatSidebarPulse {
        0%,100% { transform: scale(1); }
        50% { transform: scale(1.06); }
    }

    .chat-nav-link:hover {
        background: rgba(14,165,233,.14) !important;
        border-color: rgba(14,165,233,.30) !important;
        transform: translateX(2px);
    }

    .chat-nav-link:hover .chat-nav-icon {
        transform: translateX(2px);
        background: rgba(14,165,233,.25);
        color: #0284c7;
    }

    .chat-nav-link.active {
        background: rgba(14,165,233,.15) !important;
        color: #0284c7 !important;
        border-left: 3px solid #0284c7 !important;
    }

    .sidebar-narrow .chat-nav-content,
    .sidebar-narrow .chat-nav-unread {
        display: none !important;
    }

    .sidebar-narrow .chat-nav-link {
        justify-content: center;
        margin-left: 6px !important;
        margin-right: 6px !important;
        width: calc(100% - 12px) !important;
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


    /* =========================================================
       ACCESO RÁPIDO AL CHAT — MÓVIL
    ========================================================== */

    .chat-mobile-access {
        display: none;
    }

    @media (max-width: 767.98px) {
        .chat-mobile-access {
            position: fixed;
            right: 16px;
            bottom: 16px;
            z-index: 1035;
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 48px;
            padding: 6px 12px 6px 7px;
            border-radius: 15px;
            background: linear-gradient(135deg,#0284c7,#2563eb);
            color: #fff !important;
            text-decoration: none !important;
            box-shadow: 0 12px 28px rgba(37,99,235,.30);
        }

        .chat-mobile-access-icon {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(255,255,255,.16);
        }

        .chat-mobile-access-text {
            font-size: 11px;
            font-weight: 800;
        }
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

<script>
(function () {

    const onlineElement =
        document.getElementById('sidebarChatOnline');

    const peopleElement =
        document.getElementById('sidebarChatPeople');

    if (!onlineElement || !peopleElement) {
        return;
    }

    const urlPresencia =
        @json(route('chat.presencia'));

    let actualizando =
        false;

    async function actualizarPresenciaSidebar() {

        if (document.hidden || actualizando) {
            return;
        }

        actualizando = true;

        try {

            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content');

            const respuesta =
                await fetch(
                    urlPresencia,
                    {
                        method: 'POST',

                        headers: {
                            'Accept':
                                'application/json',

                            'X-Requested-With':
                                'XMLHttpRequest',

                            'X-CSRF-TOKEN':
                                token || ''
                        },

                        credentials:
                            'same-origin',

                        cache:
                            'no-store'
                    }
                );

            if (!respuesta.ok) {
                return;
            }

            const data =
                await respuesta.json();

            if (!data.success) {
                return;
            }

            if (
                data.personas_en_linea !== undefined
            ) {

                onlineElement.textContent =
                    data.personas_en_linea;

            }

            if (
                data.personas !== undefined
            ) {

                peopleElement.textContent =
                    data.personas;

            }

        } catch (error) {

            console.debug(
                'Presencia del Chat Vecinal no disponible:',
                error
            );

        } finally {

            actualizando =
                false;

        }

    }

    /*
     * Primera actualización al cargar cualquier página
     * que utilice este sidebar.
     */
    actualizarPresenciaSidebar();

    /*
     * Mantener actualizado el indicador.
     */
    setInterval(
        actualizarPresenciaSidebar,
        10000
    );

})();
</script>

<a
    href="{{ route('chat.index') }}"
    class="chat-mobile-access"
    aria-label="Abrir Chat Vecinal"
    title="Chat Vecinal"
>
    <span class="chat-mobile-access-icon">
        <i class="cil-chat-bubble"></i>
    </span>
    <span class="chat-mobile-access-text">
        Chat Vecinal
    </span>
</a>

