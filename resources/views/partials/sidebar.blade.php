<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar">

    <div class="sidebar-header border-bottom">

      <div class="sidebar-brand d-flex flex-column align-items-center py-3">

    @if(!empty($configuracionGlobal?->logo))

        <img
            src="{{ asset('storage/'.$configuracionGlobal->logo) }}"
            alt="Logo"
            style="height:55px;"
            class="mb-2">

    @endif

    <span class="fw-bold text-white">

        {{ $configuracionGlobal->nombre_sistema ?? 'SIGEFIV' }}

    </span>

    <small class="text-white-50">

        {{ $configuracionGlobal->organizacion ?? '' }}

    </small>

</div>

        <button
    class="btn-close d-lg-none"
    type="button"
    onclick="toggleSidebarMovil()">
</button>

    </div>

    <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>

        @can('dashboard')
<li class="nav-item">
    <a class="nav-link" href="{{ route('dashboard') }}">
        <i class="nav-icon cil-speedometer"></i>
        Dashboard
    </a>
</li>
@endcan

        <li class="nav-title">
            CONTABILIDAD
        </li>

      @can('reportes.index')
<li class="nav-item">

    <a
        class="nav-link"
        href="{{ route('reportes.index') }}">

        <i class="nav-icon cil-chart"></i>

        Reportes

    </a>

</li>
@endcan

@can('movimientos.index')
<li class="nav-item">

    <a
        class="nav-link"
        href="{{ route('movimientos.index') }}">

        <i class="nav-icon cil-transfer"></i>

        Movimientos

    </a>

</li>
@endcan

@can('caja.index')
<li class="nav-item">

    <a
        class="nav-link"
        href="{{ route('caja.index') }}">

        <i class="nav-icon cil-wallet"></i>

        Caja

    </a>

</li>
@endcan

        <li class="nav-title">
            ADMINISTRACIÓN
        </li>

       @can('usuarios.index')
<li class="nav-item">
    <a class="nav-link" href="{{ route('usuarios.index') }}">
        <i class="nav-icon cil-user"></i>
        Usuarios
    </a>
</li>
@endcan

        @can('roles.index')
<li class="nav-item">
    <a class="nav-link" href="{{ route('roles.index') }}">
        <i class="nav-icon cil-shield-alt"></i>
        Roles
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

        Configuración

    </a>

</li>
@endcan

@can('categorias.index')
<li class="nav-item">

    <a
        class="nav-link"
        href="{{ route('categorias.index') }}">

        <i class="nav-icon cil-folder"></i>

        Categorías

    </a>

</li>
@endcan


        @can('bitacora.index')
<li class="nav-item">
    <a class="nav-link" href="{{ route('bitacora.index') }}">
        <i class="nav-icon cil-notes"></i>
        Bitácora
    </a>
</li>
@endcan
        <li class="nav-divider"></li>

        <li class="nav-item mt-auto">

            <form method="POST" action="{{ route('logout') }}">

                @csrf

                <button
                    type="submit"
                    class="nav-link border-0 bg-transparent text-start w-100">

                    <i class="nav-icon cil-account-logout"></i>

                    Cerrar sesión

                </button>

            </form>

        </li>

        <li class="nav-divider"></li>

        <li class="nav-title">
            SISTEMA
        </li>

        <li class="nav-item">
           <a
    class="nav-link"
    href="{{ route('perfil.index') }}">

    <i class="nav-icon cil-user"></i>

    Mi Cuenta

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
<script>
function toggleSidebarMovil() {
    const sidebar = document.getElementById('sidebar');

    if (!sidebar) {
        return;
    }

    const instancia = coreui.Sidebar.getInstance(sidebar);

    if (instancia) {
        instancia.toggle();
    }
}
</script>