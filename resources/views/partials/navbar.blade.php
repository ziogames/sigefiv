@php
    /*
    |--------------------------------------------------------------------------
    | COLA DE NOTIFICACIONES
    |--------------------------------------------------------------------------
    |
    | Combinamos las notificaciones de movimientos con las existentes.
    | El servidor conserva todas las disponibles y el navegador muestra
    | solamente las 5 más recientes.
    |
    */

    $notificacionesMovimiento = collect(
        session('notificaciones_movimientos', [])
    )->map(function ($item) {

        $createdAt = $item['created_at'] ?? null;

        $item['tiempo'] = $createdAt
            ? \Carbon\Carbon::createFromTimestamp($createdAt)->diffForHumans()
            : 'Hace unos segundos';

        $item['_origen'] = 'movimiento';

        return $item;

    })->sortByDesc('created_at')->values();


    $notificacionesExistentes = collect(
        $notificaciones ?? []
    )->map(function ($item) {

        $item['_origen'] = 'sistema';

        return $item;

    });


    /*
    |--------------------------------------------------------------------------
    | No limitamos aquí a 5.
    | JavaScript filtrará las cerradas y mostrará las 5 más recientes.
    |--------------------------------------------------------------------------
    */

    $notificaciones = $notificacionesMovimiento
        ->concat($notificacionesExistentes)
        ->values();


    $cantidadNotificaciones =
        $notificaciones->count();
@endphp


<header class="header header-sticky p-0 mb-4">

    <div class="container-fluid border-bottom px-4">

        <button
    class="header-toggler"
    type="button"
    onclick="toggleSidebarMovil()">

            <i class="cil-menu icon icon-lg"></i>

        </button>


        <ul class="header-nav ms-auto">


            {{-- =====================================================
                 CONSULTA INTELIGENTE
            ====================================================== --}}

            <li class="nav-item me-2">

                <button
                    type="button"
                    class="btn btn-link nav-link consulta-nav-btn py-2 px-2 d-flex align-items-center robot-asistente"
                    data-coreui-toggle="modal"
                    data-coreui-target="#consultaInteligenteModal"
                    aria-label="Consulta inteligente"
                    >

                    <span class="consulta-robot-mini">
                        🤖
                    </span>

                  

                </button>

            </li>


            {{-- =====================================================
                 NOTIFICACIONES
            ====================================================== --}}

            <li class="nav-item dropdown me-2 notification-wrapper">

                <button
                    id="notificationButton"
                    class="btn btn-link nav-link position-relative py-2 px-2 notification-btn"
                    data-coreui-toggle="dropdown"
                    aria-expanded="false"
                    aria-label="Notificaciones">

                    <i class="cil-bell fs-5"></i>

                    <span
                        id="notificationBadge"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $cantidadNotificaciones > 0 ? '' : 'd-none' }}"
                        data-total="{{ $cantidadNotificaciones }}">
                        {{ $cantidadNotificaciones }}
                    </span>

                </button>


                <div
                    id="notificationMenu"
                    class="dropdown-menu dropdown-menu-end shadow notifications-menu"
                    style="width:340px;border-radius:16px;">

                    <div class="dropdown-header fw-bold d-flex align-items-center justify-content-between">

                        <span>
                            <i class="cil-bell me-2 text-primary"></i>
                            Notificaciones
                        </span>

                    </div>


                    <div class="dropdown-divider"></div>


                    <div id="notificationList">

                        @forelse($notificaciones as $item)

                            @php
                                /*
                                |------------------------------------------------------
                                | Clave estable para poder cerrar una notificación
                                | desde el navegador sin tocar los movimientos.
                                |------------------------------------------------------
                                */
                                $notificationKey = sha1(
                                    json_encode([
                                        $item['_origen'] ?? 'sistema',
                                        $item['titulo'] ?? '',
                                        $item['mensaje'] ?? '',
                                        $item['created_at'] ?? '',
                                    ])
                                );
                            @endphp

                            <div
                                class="dropdown-item notification-card notification-card-compact"
                                data-notification-key="{{ $notificationKey }}"
                                data-notification-time="{{ $item['created_at'] ?? 0 }}">

                                <div class="notification-icon bg-{{ $item['color'] ?? 'secondary' }}">

                                    <i class="{{ $item['icono'] ?? 'cil-bell' }}"></i>

                                </div>


                                <div class="notification-info">

                                    <div class="notification-title">

                                        {{ $item['titulo'] ?? 'Notificación' }}

                                    </div>


                                    <div class="notification-message">

                                        {{ $item['mensaje'] ?? '' }}

                                    </div>


                                    <div class="notification-time">

                                        {{ $item['tiempo'] ?? 'Hace unos segundos' }}

                                    </div>

                                </div>


                                <button
                                    type="button"
                                    class="notification-dismiss"
                                    data-notification-key="{{ $notificationKey }}"
                                    aria-label="Cerrar notificación"
                                    title="Cerrar">

                                    <i class="cil-x"></i>

                                </button>

                            </div>

                        @empty

                            <div
                                id="notificationEmpty"
                                class="dropdown-item text-center text-body-secondary notification-empty">

                                No hay notificaciones

                            </div>

                        @endforelse

                    </div>


                    <button
                        type="button"
                        id="notificationMore"
                        class="notification-more d-none">

                        Ver todas

                    </button>

                </div>

            </li>


            {{-- =====================================================
                 TEMA
            ====================================================== --}}

            <li class="nav-item dropdown">

                <button
                    class="btn btn-link nav-link py-2 px-2 d-flex align-items-center"
                    data-coreui-toggle="dropdown">

                    <i class="cil-contrast icon icon-lg"></i>

                </button>


                <ul class="dropdown-menu dropdown-menu-end">

                    <li>

                        <button
                            class="dropdown-item"
                            type="button"
                            data-coreui-theme-value="light">

                            Claro

                        </button>

                    </li>


                    <li>

                        <button
                            class="dropdown-item"
                            type="button"
                            data-coreui-theme-value="dark">

                            Oscuro

                        </button>

                    </li>


                    <li>

                        <button
                            class="dropdown-item active"
                            type="button"
                            data-coreui-theme-value="auto">

                            Automático

                        </button>

                    </li>

                </ul>

            </li>


            {{-- =====================================================
                 USUARIO
            ====================================================== --}}

            <li class="nav-item dropdown">

                <a
                    class="nav-link py-0 pe-0"
                    href="#"
                    data-coreui-toggle="dropdown">

                    <x-avatar
                        :user="auth()->user()"
                        size="40"/>

                </a>


                <div class="dropdown-menu dropdown-menu-end">

                    <div class="dropdown-header">

                        <strong>

                            {{ auth()->user()?->name ?? 'Invitado' }}

                        </strong>

                        <br>

                        <small>

                            {{ auth()->user()?->email ?? '' }}

                        </small>

                    </div>


                    <a
                        class="dropdown-item"
                        href="#">

                        <i class="cil-user me-2"></i>

                        Mi Perfil

                    </a>


                    <div class="dropdown-divider"></div>


                    <form
                        action="{{ route('logout') }}"
                        method="POST">

                        @csrf

                        <button
                            class="dropdown-item"
                            type="submit">

                            <i class="cil-account-logout me-2"></i>

                            Cerrar sesión

                        </button>

                    </form>

                </div>

            </li>


        </ul>

    </div>

</header>


{{-- =========================================================
     MODAL - ASISTENTE INTELIGENTE SIGEFIV
========================================================= --}}

<div
    class="modal fade"
    id="consultaInteligenteModal"
    tabindex="-1"
    aria-labelledby="consultaInteligenteModalLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered consulta-modal-dialog">

        <div class="modal-content consulta-modal">


            {{-- =================================================
                 CABECERA
            ================================================== --}}

            <div class="consulta-modal-header">

                <div class="consulta-titulo">

                    <div class="consulta-titulo-robot">
                        🤖
                    </div>

                    <div>

                        <h5
                            id="consultaInteligenteModalLabel"
                            class="mb-0">

                           Hola soy Sigi. Asistente de SIGEFIV

                        </h5>

                        <small>

                            Consultas inteligentes del sistema

                        </small>

                    </div>

                </div>


                <button
                    type="button"
                    class="consulta-close"
                    data-coreui-dismiss="modal"
                    aria-label="Cerrar">

                    <i class="cil-x"></i>

                </button>

            </div>


            {{-- =================================================
                 CUERPO
            ================================================== --}}

            <div class="consulta-modal-body">


                {{-- ROBOT --}}

                <div class="consulta-robot-container">

                    <div class="consulta-robot-glow"></div>

                    <div class="consulta-robot">

                        <div class="robot-antena">

                            <span></span>

                        </div>

                        <div class="robot-cabeza">

                            <div class="robot-ojo robot-ojo-izq"></div>

                            <div class="robot-ojo robot-ojo-der"></div>

                            <div class="robot-sonrisa"></div>

                        </div>

                        <div class="robot-cuerpo">

                            <div class="robot-brazo robot-brazo-izq"></div>

                            <div class="robot-brazo robot-brazo-der"></div>

                            <div class="robot-pantalla">

                                SIGEFIV

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =================================================
                     MENSAJES DEL ROBOT
                ================================================== --}}

                <div class="consulta-mensajes">


                    <div class="consulta-mensaje">

                        <div class="mensaje-avatar">
                            🤖
                        </div>

                        <div
                            id="mensajeBienvenida"
                            class="mensaje-texto">

                        </div>

                    </div>


                    <div
                        id="mensajePregunta"
                        class="consulta-mensaje consulta-mensaje-pregunta">

                        <div class="mensaje-avatar">
                            🤖
                        </div>

                        <div
                            id="mensajePreguntaTexto"
                            class="mensaje-texto">

                        </div>

                    </div>


                </div>


                {{-- =================================================
                     INDICADOR
                ================================================== --}}

                <div
                    id="consultaTyping"
                    class="consulta-typing">

                    <span></span>
                    <span></span>
                    <span></span>

                </div>


                {{-- =================================================
                     CAMPO DE CONSULTA
                ================================================== --}}

                <div
                    id="consultaCampo"
                    class="consulta-campo">

                    <label
                        for="consultaInteligenteTexto"
                        class="consulta-label">

                        Escriba aquí su consulta

                    </label>


                    <textarea
                        id="consultaInteligenteTexto"
                        class="consulta-textarea"
                        rows="4"
                        placeholder="Ejemplo: ¿Cuánto se recaudó por alquiler de la loza deportiva durante enero?"></textarea>


                    <div class="consulta-ayuda">

                        <i class="cil-info"></i>

                        Puede escribir su pregunta utilizando lenguaje natural.

                    </div>

                </div>

            </div>


            {{-- =================================================
                 PIE
            ================================================== --}}

            <div class="consulta-modal-footer">

                <div class="consulta-seguridad">

                    <i class="cil-shield-alt"></i>

                    <span>

                        Tus consultas son seguras y privadas

                    </span>

                </div>


                <div class="consulta-botones">

                    <button
                        type="button"
                        class="btn consulta-btn-cancelar"
                        data-coreui-dismiss="modal">

                        <i class="cil-x me-1"></i>

                        Cancelar

                    </button>


                    <button
                        type="button"
                        class="btn consulta-btn-ejecutar"
                        id="btnEjecutarConsulta">

                        <i class="cil-search me-1"></i>

                        Consultar

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     NOTIFICACIÓN AUTOMÁTICA
========================================================= --}}

@if(session('success') || session('error') || session('warning'))

    <div
        id="automaticNotification"
        class="automatic-notification">

        <div class="automatic-notification-icon">

            @if(session('success'))

                <i class="cil-check-circle"></i>

            @elseif(session('error'))

                <i class="cil-x-circle"></i>

            @else

                <i class="cil-warning"></i>

            @endif

        </div>


        <div class="automatic-notification-content">

            <div class="automatic-notification-title">

                @if(session('success'))

                    Acción realizada

                @elseif(session('error'))

                    Se produjo un error

                @else

                    Atención

                @endif

            </div>


            <div class="automatic-notification-message">

                {{ session('success') ?? session('error') ?? session('warning') }}

            </div>

        </div>


        <button
            type="button"
            class="automatic-notification-close"
            onclick="cerrarNotificacionAutomatica()">

            <i class="cil-x"></i>

        </button>

    </div>

@endif



<style>
/* =========================================================
   NOTIFICACIONES - DISEÑO COMPACTO
   ========================================================= */

.notifications-menu {
    max-height: none !important;
    overflow: hidden !important;
    padding-bottom: 0 !important;
}

.notification-card-compact {
    position: relative;
    display: flex;
    align-items: center;
    gap: 9px;
    min-height: 55px;
    padding: 7px 32px 7px 10px !important;
    border-bottom: 1px solid rgba(120, 150, 185, .10);
}

.notification-card-compact.notification-hidden {
    display: none !important;
}

.notification-card-compact .notification-icon {
    width: 30px;
    min-width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    font-size: 12px;
}

.notification-card-compact .notification-info {
    min-width: 0;
    flex: 1;
}

.notification-card-compact .notification-title {
    font-size: 11px;
    font-weight: 700;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notification-card-compact .notification-message {
    margin-top: 2px;
    font-size: 10px;
    line-height: 1.15;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notification-card-compact .notification-time {
    margin-top: 2px;
    font-size: 8px;
    line-height: 1;
    opacity: .65;
}

/* Animación suave al cerrar una notificación */
.notification-card-compact {
    transform-origin: center right;
}

.notification-card-compact.notification-closing {
    pointer-events: none !important;
    animation: notificationCerrar .55s cubic-bezier(.4, 0, .2, 1) forwards !important;
    will-change: opacity, transform, max-height;
    overflow: hidden !important;
}

@keyframes notificationCerrar {
    0% {
        opacity: 1;
        transform: translate3d(0, 0, 0) scale(1);
        max-height: 55px;
        padding-top: 7px;
        padding-bottom: 7px;
    }

    35% {
        opacity: .78;
        transform: translate3d(6px, 0, 0) scale(.985);
    }

    70% {
        opacity: .30;
        transform: translate3d(15px, 0, 0) scale(.96);
    }

    100% {
        opacity: 0;
        transform: translate3d(28px, 0, 0) scale(.93);
        max-height: 0;
        padding-top: 0;
        padding-bottom: 0;
        margin-top: 0;
        margin-bottom: 0;
    }
}

.notification-dismiss {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 19px;
    height: 19px;
    padding: 0;
    border: 0;
    border-radius: 50%;
    background: transparent;
    color: #7f8da0;
    cursor: pointer;
    opacity: .55;
    display: flex;
    align-items: center;
    justify-content: center;
    transition:
        background .15s ease,
        color .15s ease,
        opacity .15s ease,
        transform .15s ease;
}

.notification-dismiss i {
    font-size: 10px;
}

.notification-dismiss:hover {
    opacity: 1;
    color: #ffffff;
    background: rgba(220, 53, 69, .78);
    transform: scale(1.08);
}

.notification-more {
    width: 100%;
    padding: 9px 12px;
    border: 0;
    border-top: 1px solid rgba(120, 150, 185, .12);
    background: transparent;
    color: #6eaef7;
    font-size: 10px;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
}

.notification-more:hover {
    background: rgba(80, 150, 255, .06);
}

.notification-empty {
    padding: 16px 10px !important;
    font-size: 11px;
}

.icono-saludo {
    color: #f9c74f;
    display: inline-block;
    margin-right: 6px;
    font-size: 20px;
    animation: saludoSigi 1.8s ease-in-out infinite;
}

@keyframes saludoSigi {
    0%, 100% {
        transform: rotate(0deg);
    }

    25% {
        transform: rotate(15deg);
    }

    50% {
        transform: rotate(-10deg);
    }

    75% {
        transform: rotate(10deg);
    }
}
</style>

<style>

/* =========================================================
   RESULTADO DE CONSULTA
========================================================= */

.resultado-consulta {

    padding: 2px 0;

}


.resultado-titulo {

    margin-bottom: 5px;

    color: #8ea2bb;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .5px;

}


.resultado-valor {

    color: #5ebaff;

    font-size: 25px;

    font-weight: 800;

    letter-spacing: -.5px;

}


.resultado-lista {

    color: #dce5f1;

    font-size: 13px;

}


.resultado-error {

    color: #ff9b9b;

    font-size: 12px;

}


.resultado-error strong {

    color: #ff7070;

}

/* =========================================================
   CONSULTA INTELIGENTE - BOTÓN NAVBAR
========================================================= */

.consulta-nav-btn {

    color: #aeb9c8 !important;

    transition:
        color .25s ease,
        background .25s ease,
        transform .25s ease;

    border-radius: 10px;

}


.consulta-nav-btn:hover {

    color: #ffffff !important;

    background: rgba(59, 130, 246, .12);

    transform: translateY(-1px);

}


.consulta-robot-mini {

    width: 30px;

    height: 30px;

    display: flex;

    align-items: center;

    justify-content: center;

    margin-right: 7px;

    border-radius: 9px;

    background:
        linear-gradient(
            145deg,
            #24344d,
            #162236
        );

    border:
        1px solid rgba(80, 150, 255, .30);

    font-size: 17px;

    box-shadow:
        0 4px 12px rgba(0, 0, 0, .20);

}


.consulta-nav-text {

    font-size: 13px;

    font-weight: 600;

}


/* =========================================================
   MODAL
========================================================= */

.consulta-modal-dialog {

    max-width: 720px;

}


.consulta-modal {

    overflow: hidden;

    border: 1px solid rgba(82, 110, 145, .35);

    border-radius: 18px;

    background:
        linear-gradient(
            145deg,
            #111a29 0%,
            #172236 55%,
            #101927 100%
        );

    color: #ffffff;

    box-shadow:
        0 25px 70px rgba(0, 0, 0, .55);

}


/* =========================================================
   CABECERA
========================================================= */

.consulta-modal-header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 18px 22px;

    border-bottom:
        1px solid rgba(100, 130, 165, .18);

    background:
        rgba(255, 255, 255, .025);

}


.consulta-titulo {

    display: flex;

    align-items: center;

    gap: 12px;

}


.consulta-titulo-robot {

    width: 43px;

    height: 43px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 12px;

    background:
        linear-gradient(
            145deg,
            #263b5b,
            #15243a
        );

    border:
        1px solid rgba(77, 150, 255, .35);

    font-size: 23px;

}


.consulta-titulo h5 {

    color: #f3f6fb;

    font-size: 16px;

    font-weight: 700;

}


.consulta-titulo small {

    color: #8292a8;

    font-size: 11px;

}


.consulta-close {

    width: 34px;

    height: 34px;

    border: 0;

    border-radius: 9px;

    background: transparent;

    color: #7e8da2;

    cursor: pointer;

    transition: all .2s ease;

}


.consulta-close:hover {

    background: rgba(255, 255, 255, .08);

    color: #ffffff;

}


/* =========================================================
   CUERPO
========================================================= */

.consulta-modal-body {

    padding: 24px 28px 22px;

}


/* =========================================================
   ROBOT
========================================================= */

.consulta-robot-container {

    position: relative;

    width: 150px;

    height: 145px;

    margin: 0 auto 12px;

    display: flex;

    justify-content: center;

    align-items: flex-start;

}


.consulta-robot-glow {

    position: absolute;

    width: 130px;

    height: 50px;

    bottom: 0;

    border-radius: 50%;

    background:
        rgba(44, 130, 255, .12);

    filter: blur(15px);

}


.consulta-robot {

    position: relative;

    z-index: 2;

    width: 120px;

    height: 140px;

    animation:
        robotFlotar 3s ease-in-out infinite;

}


.robot-antena {

    position: absolute;

    top: 0;

    left: 55px;

    width: 10px;

    height: 24px;

    border-left:
        3px solid #6ba8ff;

}


.robot-antena span {

    position: absolute;

    top: -7px;

    left: -6px;

    width: 13px;

    height: 13px;

    border-radius: 50%;

    background: #4d9cff;

    box-shadow:
        0 0 14px rgba(77, 156, 255, .8);

}


.robot-cabeza {

    position: absolute;

    top: 18px;

    left: 10px;

    width: 100px;

    height: 68px;

    border-radius: 27px;

    background:
        linear-gradient(
            145deg,
            #e8edf4,
            #aeb8c7
        );

    border:
        2px solid #718097;

    box-shadow:
        0 10px 25px rgba(0, 0, 0, .35);

}


.robot-cabeza::before {

    content: "";

    position: absolute;

    top: 10px;

    left: 10px;

    right: 10px;

    bottom: 10px;

    border-radius: 19px;

    background:
        linear-gradient(
            145deg,
            #101a2b,
            #07101d
        );

    border:
        1px solid rgba(84, 142, 220, .35);

}


.robot-ojo {

    position: absolute;

    z-index: 2;

    top: 31px;

    width: 9px;

    height: 13px;

    border-radius: 50%;

    background: #54c7ff;

    box-shadow:
        0 0 10px rgba(84, 199, 255, .9);

}


.robot-ojo-izq {

    left: 32px;

}


.robot-ojo-der {

    right: 32px;

}


.robot-sonrisa {

    position: absolute;

    z-index: 2;

    left: 42px;

    top: 43px;

    width: 16px;

    height: 8px;

    border-bottom:
        2px solid #54c7ff;

    border-radius: 0 0 20px 20px;

}


.robot-cuerpo {

    position: absolute;

    top: 82px;

    left: 25px;

    width: 70px;

    height: 52px;

    border-radius: 18px 18px 12px 12px;

    background:
        linear-gradient(
            145deg,
            #d9e0e9,
            #929eaf
        );

    border:
        2px solid #68758a;

}


.robot-pantalla {

    position: absolute;

    top: 10px;

    left: 14px;

    width: 42px;

    height: 28px;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 7px;

    background: #0b1525;

    color: #5ebaff;

    font-size: 6px;

    font-weight: 800;

    letter-spacing: .5px;

    border:
        1px solid rgba(86, 170, 255, .35);

}


.robot-brazo {

    position: absolute;

    top: 10px;

    width: 12px;

    height: 30px;

    border-radius: 10px;

    background:
        linear-gradient(
            180deg,
            #cdd5df,
            #8794a6
        );

}


.robot-brazo-izq {

    left: -12px;

    transform: rotate(15deg);

}


.robot-brazo-der {

    right: -12px;

    transform: rotate(-15deg);

}


@keyframes robotFlotar {

    0%,
    100% {

        transform: translateY(0);

    }

    50% {

        transform: translateY(-5px);

    }

}


/* =========================================================
   MENSAJES
========================================================= */

.consulta-mensajes {

    max-width: 610px;

    margin: 0 auto;

    max-height: 330px;

    overflow-y: auto;

    padding: 4px 6px 8px;

    scroll-behavior: smooth;

}


.consulta-mensajes::-webkit-scrollbar {

    width: 5px;

}


.consulta-mensajes::-webkit-scrollbar-track {

    background: transparent;

}


.consulta-mensajes::-webkit-scrollbar-thumb {

    background: rgba(120, 150, 185, .28);

    border-radius: 10px;

}


.consulta-mensaje {

    display: flex;

    align-items: flex-start;

    gap: 10px;

    margin-bottom: 10px;

}


.consulta-mensaje-usuario {

    justify-content: flex-end;

}


.consulta-mensaje-usuario .mensaje-avatar {

    display: none;

}


.consulta-mensaje-usuario .mensaje-texto {

    flex: 0 1 auto;

    max-width: 78%;

    background: #075e54;

    border-color: rgba(120, 220, 190, .18);

    color: #ffffff;

    border-radius: 14px 14px 3px 14px;

}


.consulta-respuesta-resultado {

    justify-content: flex-start;

}


.consulta-respuesta-resultado .mensaje-texto {

    border-radius: 3px 14px 14px 14px;

}


.mensaje-avatar {

    width: 34px;

    height: 34px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 10px;

    background:
        #1d304a;

    border:
        1px solid rgba(75, 150, 255, .25);

    font-size: 17px;

}


.mensaje-texto {

    min-height: 34px;

    flex: 1;

    padding: 9px 13px;

    border-radius: 11px;

    background:
        #1a283b;

    border:
        1px solid rgba(100, 130, 165, .20);

    color: #dce5f1;

    font-size: 13px;

    line-height: 1.45;

}


.consulta-mensaje-pregunta {

    display: none;

}


/* =========================================================
   CURSOR DE ESCRITURA
========================================================= */

.typing-cursor {

    display: inline-block;

    width: 2px;

    height: 15px;

    margin-left: 3px;

    vertical-align: -2px;

    background: #4ea3ff;

    animation:
        cursorParpadeo .7s infinite;

}


@keyframes cursorParpadeo {

    0%,
    50% {

        opacity: 1;

    }

    51%,
    100% {

        opacity: 0;

    }

}


/* =========================================================
   INDICADOR
========================================================= */

.consulta-typing {

    display: none;

    align-items: center;

    gap: 4px;

    margin:
        10px 0
        12px
        44px;

}


.consulta-typing span {

    width: 5px;

    height: 5px;

    border-radius: 50%;

    background: #5aa9ff;

    animation:
        typingDot 1.2s infinite ease-in-out;

}


.consulta-typing span:nth-child(2) {

    animation-delay: .15s;

}


.consulta-typing span:nth-child(3) {

    animation-delay: .30s;

}


@keyframes typingDot {

    0%,
    60%,
    100% {

        transform: translateY(0);

        opacity: .45;

    }

    30% {

        transform: translateY(-4px);

        opacity: 1;

    }

}


/* =========================================================
   CAMPO DE CONSULTA
========================================================= */

.consulta-campo {

    margin-top: 18px;

}


.consulta-label {

    display: block;

    margin-bottom: 7px;

    color: #9eacc0;

    font-size: 12px;

    font-weight: 600;

}


.consulta-textarea {

    width: 100%;

    resize: vertical;

    min-height: 95px;

    padding: 13px 15px;

    border-radius: 12px;

    border:
        1px solid #30435d;

    outline: none;

    background:
        #0d1726;

    color: #e6edf6;

    font-size: 13px;

    transition:
        border-color .2s ease,
        box-shadow .2s ease;

}


.consulta-textarea::placeholder {

    color: #5f7188;

}


.consulta-textarea:focus {

    border-color: #418ff2;

    box-shadow:
        0 0 0 3px rgba(65, 143, 242, .12);

}


.consulta-ayuda {

    display: flex;

    align-items: center;

    gap: 6px;

    margin-top: 7px;

    color: #64758c;

    font-size: 10px;

}


.consulta-ayuda i {

    color: #438fe8;

}


/* =========================================================
   PIE
========================================================= */

.consulta-modal-footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding: 15px 22px;

    border-top:
        1px solid rgba(100, 130, 165, .18);

    background:
        rgba(5, 12, 22, .30);

}


.consulta-seguridad {

    display: flex;

    align-items: center;

    gap: 7px;

    color: #667990;

    font-size: 10px;

}


.consulta-seguridad i {

    color: #4d91df;

}


.consulta-botones {

    display: flex;

    gap: 8px;

}


.consulta-btn-cancelar {

    border:
        1px solid #34465e;

    background:
        #172235;

    color: #aebacc;

    border-radius: 9px;

    font-size: 12px;

}


.consulta-btn-cancelar:hover {

    background: #203049;

    color: #ffffff;

}


.consulta-btn-ejecutar {

    border: 0;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #2478e8,
            #3e9bff
        );

    color: #ffffff;

    font-size: 12px;

    font-weight: 600;

    box-shadow:
        0 5px 15px rgba(36, 120, 232, .25);

}


.consulta-btn-ejecutar:hover {

    background:
        linear-gradient(
            135deg,
            #3185f0,
            #50a7ff
        );

    color: #ffffff;

    transform: translateY(-1px);

}


/* =========================================================
   NOTIFICACIÓN AUTOMÁTICA
========================================================= */

.automatic-notification {

    position: fixed;

    top: 68px;

    right: 24px;

    width: 340px;

    display: flex;

    align-items: flex-start;

    gap: 12px;

    padding: 14px 15px;

    z-index: 9999;

    border-radius: 14px;

    background:
        linear-gradient(
            145deg,
            #172033 0%,
            #1b273a 100%
        );

    border:
        1px solid rgba(92, 117, 150, .35);

    box-shadow:
        0 12px 35px rgba(0, 0, 0, .28);

    color: #ffffff;

    animation:
        notificationEntrada .35s ease forwards;

}


.automatic-notification-icon {

    width: 34px;

    height: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    flex-shrink: 0;

    border-radius: 9px;

    background:
        rgba(32, 201, 90, .15);

    color: #36d96d;

    font-size: 17px;

}


.automatic-notification-content {

    flex: 1;

    min-width: 0;

}


.automatic-notification-title {

    margin-bottom: 3px;

    color: #f2f5f9;

    font-size: 12px;

    font-weight: 800;

}


.automatic-notification-message {

    color: #aeb9c8;

    font-size: 11px;

    line-height: 1.4;

}


.automatic-notification-close {

    border: 0;

    background: transparent;

    color: #7f8da0;

    padding: 2px;

    cursor: pointer;

    font-size: 13px;

}


.automatic-notification-close:hover {

    color: #ffffff;

}


@keyframes notificationEntrada {

    from {

        opacity: 0;

        transform:
            translateY(-12px)
            translateX(10px);

    }

    to {

        opacity: 1;

        transform:
            translateY(0)
            translateX(0);

    }

}


@keyframes notificationSalida {

    from {

        opacity: 1;

        transform:
            translateY(0);

    }

    to {

        opacity: 0;

        transform:
            translateY(-10px);

    }

}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 768px) {

    .consulta-modal-dialog {

        margin: 12px;

    }


    .consulta-modal-body {

        padding:
            18px
            16px
            18px;

    }


    .consulta-modal-footer {

        align-items: flex-start;

        flex-direction: column;

    }


    .consulta-seguridad {

        order: 2;

    }


    .consulta-botones {

        width: 100%;

        justify-content: flex-end;

    }

}


@media (max-width: 576px) {

    .consulta-nav-text {

        display: none !important;

    }


    .consulta-robot-container {

        transform: scale(.9);

        margin-bottom: 3px;

    }


    .consulta-modal-footer {

        padding: 14px;

    }


    .automatic-notification {

        top: 62px;

        right: 12px;

        left: 12px;

        width: auto;

    }

}

/* =========================================================
   TARJETA DE CLIMA
========================================================= */

.clima-card {
    margin-top: 2px;
    padding: 16px;
    border-radius: 16px;
    background:
        linear-gradient(
            145deg,
            #182940 0%,
            #142238 55%,
            #101c2d 100%
        );
    border: 1px solid rgba(91, 155, 230, .25);
    box-shadow:
        0 10px 28px rgba(0, 0, 0, .25);
    overflow: hidden;
}

.clima-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 14px;
}

.clima-card-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
}

.clima-card-icono {
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(77, 156, 255, .14);
    border: 1px solid rgba(77, 156, 255, .22);
    font-size: 23px;
}

.clima-card-label {
    color: #7f96b1;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .8px;
}

.clima-card-ciudad {
    margin-top: 2px;
    color: #f1f5fa;
    font-size: 14px;
    font-weight: 700;
}

.clima-card-principal {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 0;
    border-top: 1px solid rgba(120, 150, 185, .12);
    border-bottom: 1px solid rgba(120, 150, 185, .12);
}

.clima-temperatura {
    color: #ffffff;
    font-size: 42px;
    line-height: 1;
    font-weight: 800;
    letter-spacing: -1.5px;
}

.clima-grados {
    color: #7ec4ff;
    font-size: 20px;
    vertical-align: top;
    margin-left: 2px;
}

.clima-descripcion {
    margin-top: 6px;
    color: #a9bad0;
    font-size: 11px;
}

.clima-detalles {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 7px;
    margin-top: 12px;
}

.clima-detalle {
    padding: 10px 8px;
    border-radius: 11px;
    background: rgba(255, 255, 255, .035);
    border: 1px solid rgba(120, 150, 185, .12);
}

.clima-detalle-icono {
    font-size: 14px;
}

.clima-detalle-label {
    margin-top: 5px;
    color: #71859d;
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.clima-detalle-valor {
    margin-top: 2px;
    color: #dfe8f3;
    font-size: 12px;
    font-weight: 700;
}

.clima-actualizado {
    margin-top: 11px;
    color: #61758e;
    font-size: 9px;
    text-align: right;
}

@media (max-width: 576px) {

    .clima-card-principal {
        align-items: flex-start;
    }

    .clima-temperatura {
        font-size: 36px;
    }

    .clima-detalles {
        grid-template-columns: 1fr;
    }
}


/* =========================================================
   SALUDO DEL ROBOT
========================================================= */

.robot-asistente {
    position: relative;
}

.robot-asistente::after {
    content: "";
    position: absolute;
    left: 50%;
    top: calc(100% + 10px);
    transform: translateX(-50%) translateY(-4px);
    width: max-content;
    max-width: 260px;
    padding: 9px 13px;
    border-radius: 11px;
    background: #172235;
    color: #eaf2fb;
    border: 1px solid rgba(126, 196, 255, .28);
    box-shadow: 0 10px 25px rgba(0, 0, 0, .28);
    font-size: 12px;
    font-weight: 600;
    line-height: 1.35;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition:
        opacity .18s ease,
        transform .18s ease;
    z-index: 9999;
}

.robot-asistente:hover::after,
.robot-asistente:focus-visible::after {
    content: "Hola, soy Sigi. Estoy aquí para ayudarte.";
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
    animation:
        robot-tooltip-escribir 1.7s steps(30, end) forwards,
        robot-tooltip-cursor .65s steps(1, end) infinite;
}

@keyframes robot-tooltip-escribir {
    from {
        clip-path: inset(0 100% 0 0);
    }

    to {
        clip-path: inset(0 0 0 0);
    }
}

@keyframes robot-tooltip-cursor {
    0%,
    48% {
        border-right: 2px solid rgba(126, 196, 255, .9);
    }

    49%,
    100% {
        border-right: 2px solid transparent;
    }
}

@media (max-width: 576px) {

    .robot-asistente::after {
        left: auto;
        right: 0;
        transform: translateY(-4px);
        white-space: normal;
        width: 220px;
    }

    .robot-asistente:hover::after,
    .robot-asistente:focus-visible::after {
        transform: translateY(0);
    }
}


/* =========================================================
   RESPUESTA DEL ASISTENTE
========================================================= */

.resultado-mensaje {

    color: #dce5f1;

    font-size: 13px;

    line-height: 1.55;

}


.resultado-valor {

    margin-top: 9px;

    color: #5ebaff;

    font-size: 26px;

    font-weight: 800;

    letter-spacing: -.5px;

}
/* =========================================================
   REGISTROS ENCONTRADOS
========================================================= */

.consulta-registros {

    margin-top: 10px;

    display: flex;

    flex-direction: column;

    gap: 7px;

}


.consulta-registro {

    display: flex;

    align-items: center;

    gap: 10px;

    padding: 10px;

    border-radius: 10px;

    background: #101b2b;

    border: 1px solid rgba(100, 130, 165, .18);

}


.registro-icono {

    width: 32px;

    height: 32px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    border-radius: 8px;

    background: #1c3048;

}


.registro-datos {

    flex: 1;

    min-width: 0;

}


.registro-concepto {

    color: #e4ebf4;

    font-size: 12px;

    font-weight: 700;

}


.registro-detalles {

    display: flex;

    flex-wrap: wrap;

    gap: 8px;

    margin-top: 4px;

    color: #74869d;

    font-size: 9px;

}


.registro-monto {

    color: #5ebaff;

    font-size: 14px;

    font-weight: 800;

    white-space: nowrap;

}

</style>


<script>

/* =========================================================
   ASISTENTE INTELIGENTE
========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const modal =
            document.getElementById(
                'consultaInteligenteModal'
            );

        if (!modal) {

            return;

        }


        const nombreUsuario =
            @json(auth()->user()?->name ?? 'Usuario');


        const bienvenida =
            document.getElementById(
                'mensajeBienvenida'
            );


        const pregunta =
            document.getElementById(
                'mensajePregunta'
            );


        const preguntaTexto =
            document.getElementById(
                'mensajePreguntaTexto'
            );


        const campo =
            document.getElementById(
                'consultaCampo'
            );


        const textarea =
            document.getElementById(
                'consultaInteligenteTexto'
            );


        const typing =
            document.getElementById(
                'consultaTyping'
            );


        let escribiendo = false;


        function escribirTexto(elemento, texto, velocidad, callback) {

            elemento.innerHTML = '';

            const cursor =
                document.createElement('span');

            cursor.className =
                'typing-cursor';

            elemento.appendChild(cursor);


            let posicion = 0;


            function escribir() {

                if (posicion < texto.length) {

                    cursor.insertAdjacentText(
                        'beforebegin',
                        texto.charAt(posicion)
                    );

                    posicion++;

                    setTimeout(
                        escribir,
                        velocidad
                    );

                } else {

                    setTimeout(
                        function () {

                            cursor.remove();

                            if (callback) {

                                callback();

                            }

                        },
                        350
                    );

                }

            }


            escribir();

        }


        function iniciarAsistente() {

            if (escribiendo) {

                return;

            }


            escribiendo = true;


            bienvenida.innerHTML = '';

            preguntaTexto.innerHTML = '';

            pregunta.style.display = 'none';

            campo.style.opacity = '0';

            campo.style.transform =
                'translateY(8px)';


            const iconoBienvenida =
    document.createElement('i');

iconoBienvenida.className =
    'cil-hand-point-right icono-saludo';

bienvenida.innerHTML = '';

bienvenida.appendChild(
    iconoBienvenida
);

const textoBienvenida =
    document.createElement('span');

bienvenida.appendChild(
    textoBienvenida
);

escribirTexto(
    textoBienvenida,
    'Bienvenido, ' + nombreUsuario,
    55,
    function () {


                    typing.style.display =
                        'flex';


                    setTimeout(

                        function () {

                            typing.style.display =
                                'none';


                            pregunta.style.display =
                                'flex';


                            escribirTexto(

                                preguntaTexto,

                                '¿Cuál es la consulta que desea realizar?',

                                42,

                                function () {

                                    campo.style.transition =
                                        'opacity .5s ease, transform .5s ease';

                                    campo.style.opacity =
                                        '1';

                                    campo.style.transform =
                                        'translateY(0)';

                                    textarea.focus();

                                    escribiendo =
                                        false;

                                }

                            );

                        },

                        500

                    );

                }

            );

        }


        function ocultarIntroduccion() {

            const bienvenidaMensaje =
                bienvenida.closest('.consulta-mensaje');

            const preguntaMensaje =
                pregunta.closest('.consulta-mensaje');


            if (bienvenidaMensaje) {

                bienvenidaMensaje.style.display = 'none';

            }


            if (preguntaMensaje) {

                preguntaMensaje.style.display = 'none';

            }

        }


        function mostrarMensajeUsuario(texto) {

            const mensajes =
                document.querySelector('.consulta-mensajes');


            if (!mensajes) {

                return;

            }


            const mensaje =
                document.createElement('div');

            mensaje.className =
                'consulta-mensaje consulta-mensaje-usuario';


            const contenido =
                document.createElement('div');

            contenido.className =
                'mensaje-texto';

            contenido.textContent = texto;


            mensaje.appendChild(contenido);

            mensajes.appendChild(mensaje);


            mensaje.scrollIntoView({

                behavior: 'smooth',

                block: 'nearest'

            });

        }


        modal.addEventListener(
            'shown.coreui.modal',
            function () {

                iniciarAsistente();

            }
        );


        modal.addEventListener(
    'hidden.coreui.modal',
    function () {

        escribiendo = false;

        bienvenida.innerHTML = '';
        preguntaTexto.innerHTML = '';

        // =====================================================
        // LIMPIAR TODA LA CONVERSACIÓN AL CERRAR SIGI
        // =====================================================

        const mensajes =
            document.querySelector('.consulta-mensajes');

        if (mensajes) {

            mensajes
                .querySelectorAll(
                    '.consulta-mensaje-usuario, .consulta-respuesta-resultado'
                )
                .forEach(function (mensaje) {
                    mensaje.remove();
                });
        }

        // =====================================================
        // RESTAURAR PRESENTACIÓN INICIAL
        // =====================================================

        const bienvenidaMensaje =
            bienvenida.closest('.consulta-mensaje');

        const preguntaMensaje =
            pregunta.closest('.consulta-mensaje');

        if (bienvenidaMensaje) {
            bienvenidaMensaje.style.display = '';
        }

        if (preguntaMensaje) {
            preguntaMensaje.style.display = 'none';
        }

        pregunta.style.display =
            'none';

        campo.style.opacity =
            '1';

        campo.style.transform =
            'translateY(0)';

        textarea.value = '';
    }
);


const botonConsulta =
    document.getElementById(
        'btnEjecutarConsulta'
    );


async function ejecutarConsulta() {

    const consulta =
        textarea.value.trim();


    if (!consulta || escribiendo) {

        textarea.focus();

        return;

    }


    ocultarIntroduccion();

    mostrarMensajeUsuario(consulta);

    textarea.value = '';


    botonConsulta.disabled = true;

    botonConsulta.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span>' +
        'Consultando...';


    try {

        const respuesta =
            await fetch(
                '{{ route("consulta.inteligente") }}',
                {

                    method: 'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            '{{ csrf_token() }}'

                    },

                    body: JSON.stringify({

                        consulta: consulta

                    })

                }
            );


        const datos =
            await respuesta.json();


        if (!respuesta.ok) {

            throw new Error(
                datos.message ||
                'No se pudo realizar la consulta.'
            );

        }


        console.log(
            'Respuesta:',
            datos
        );


        mostrarResultadoConsulta(
            datos
        );


    } catch (error) {

        console.error(
            'Error:',
            error
        );


        mostrarErrorConsulta(
            error.message
        );

    } finally {

        botonConsulta.disabled = false;

        botonConsulta.innerHTML =
            '<i class="cil-search me-1"></i>' +
            'Consultar';

        textarea.focus();

    }

}


if (botonConsulta) {

    botonConsulta.addEventListener(
        'click',
        ejecutarConsulta
    );

}


/* =========================================================
   ENTER = ENVIAR
========================================================= */

textarea.addEventListener(
    'keydown',
    function (evento) {

        if (
            evento.key === 'Enter' &&
            !evento.shiftKey
        ) {

            evento.preventDefault();

            ejecutarConsulta();

        }

    }
);



/* =========================================================
   MOSTRAR RESULTADO
========================================================= */

function mostrarResultadoConsulta(datos) {

    const mensajes =
        document.querySelector(
            '.consulta-mensajes'
        );

    if (!mensajes) {

        return;

    }


   const mensaje =
    document.createElement('div');

mensaje.className =
    'consulta-mensaje consulta-respuesta-resultado';


    const avatar =
        document.createElement('div');

    avatar.className =
        'mensaje-avatar';

    avatar.textContent =
        '🤖';


    const contenido =
        document.createElement('div');

    contenido.className =
        'mensaje-texto';


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA NUMÉRICA
    |--------------------------------------------------------------------------
    */

    if (
        datos.tipo === 'numero' &&
        typeof datos.resultado === 'number'
    ) {

        contenido.innerHTML =

            '<div class="resultado-consulta">' +

                '<div class="resultado-mensaje">' +

                    (datos.mensaje || 'Resultado de la consulta.') +

                '</div>' +

                '<div class="resultado-valor">' +

                    'S/ ' +

                    datos.resultado.toLocaleString(
                        'es-PE',
                        {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }
                    ) +

                '</div>' +

            '</div>';

    }


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA DE LISTA
    |--------------------------------------------------------------------------
    */

else if (
    datos.tipo === 'lista' &&
    Array.isArray(datos.resultado)
) {

    const registros =
        datos.resultado;


    let html =

        '<div class="resultado-consulta">' +

            '<div class="resultado-mensaje">' +

                (
                    datos.mensaje ||
                    'Estos son los resultados encontrados.'
                ) +

            '</div>';


    if (registros.length > 0) {

        html +=

            '<div class="consulta-registros">';


        registros.forEach(
            function (registro) {


                /*
                |--------------------------------------------------------------------------
                | USUARIO
                |--------------------------------------------------------------------------
                */

                if (
                    registro.name !== undefined
                ) {

                    html +=

                        '<div class="consulta-registro">' +

                            '<div class="registro-icono">' +

                                '👤' +

                            '</div>' +

                            '<div class="registro-datos">' +

                                '<div class="registro-concepto">' +

                                    (
                                        registro.name ||
                                        'Usuario sin nombre'
                                    ) +

                                '</div>' +

                                '<div class="registro-detalles">';


                    if (
                        registro.email
                    ) {

                        html +=

                            '<span>' +

                                '✉️ ' +

                                registro.email +

                            '</span>';

                    }


                    html +=

                                '</div>' +

                            '</div>' +

                        '</div>';


                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | ROL
                |--------------------------------------------------------------------------
                */

                if (
                    registro.name !== undefined &&
                    registro.email === undefined
                ) {

                    html +=

                        '<div class="consulta-registro">' +

                            '<div class="registro-icono">' +

                                '🔐' +

                            '</div>' +

                            '<div class="registro-datos">' +

                                '<div class="registro-concepto">' +

                                    registro.name +

                                '</div>' +

                            '</div>' +

                        '</div>';


                    return;

                }


                /*
                |--------------------------------------------------------------------------
                | MOVIMIENTO
                |--------------------------------------------------------------------------
                */

                const fecha =
                    registro.fecha || '';


                const concepto =
                    registro.concepto ||
                    'Sin concepto';


                const monto =
                    Number(
                        registro.monto || 0
                    );


                const tipo =
                    registro.tipo || '';


                let categoria = '';


                if (
                    registro.categoria &&
                    registro.categoria.nombre
                ) {

                    categoria =
                        registro.categoria.nombre;

                }


                html +=

                    '<div class="consulta-registro">' +

                        '<div class="registro-icono">' +

                            '💰' +

                        '</div>' +

                        '<div class="registro-datos">' +

                            '<div class="registro-concepto">' +

                                concepto +

                            '</div>' +

                            '<div class="registro-detalles">';


                if (fecha) {

                    html +=

                        '<span>' +

                            '📅 ' +

                            fecha +

                        '</span>';

                }


                if (categoria) {

                    html +=

                        '<span>' +

                            '📂 ' +

                            categoria +

                        '</span>';

                }


                if (tipo) {

                    html +=

                        '<span>' +

                            tipo +

                        '</span>';

                }


                html +=

                            '</div>' +

                        '</div>' +

                        '<div class="registro-monto">' +

                            'S/ ' +

                            monto.toLocaleString(
                                'es-PE',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            ) +

                        '</div>' +

                    '</div>';

            }
        );


        html +=

            '</div>';

    }


    html +=

        '</div>';


    contenido.innerHTML =
        html;

}


    /*
    |--------------------------------------------------------------------------
    | RESPUESTA DE CLIMA
    |--------------------------------------------------------------------------
    */

    else if (
        datos.tipo === 'clima' &&
        datos.resultado
    ) {

        const clima =
            datos.resultado;

        const ubicacion =
            clima.ubicacion || {};

        const temperatura =
            clima.temperatura !== null &&
            clima.temperatura !== undefined
                ? Number(clima.temperatura)
                : null;

        const sensacion =
            clima.sensacion !== null &&
            clima.sensacion !== undefined
                ? Number(clima.sensacion)
                : null;

        const humedad =
            clima.humedad !== null &&
            clima.humedad !== undefined
                ? Number(clima.humedad)
                : null;

        const viento =
            clima.viento !== null &&
            clima.viento !== undefined
                ? Number(clima.viento)
                : null;

        const ciudad =
            ubicacion.nombre ||
            'Ubicación desconocida';

        const pais =
            ubicacion.pais ||
            '';

        const descripcion =
            clima.descripcion ||
            'Condiciones variables';

        let iconoClima = '🌤️';

        const codigo =
            Number(clima.codigo);

        if (codigo === 0) {

            iconoClima = '☀️';

        } else if (
            [1, 2, 3].includes(codigo)
        ) {

            iconoClima = '🌤️';

        } else if (
            [45, 48].includes(codigo)
        ) {

            iconoClima = '🌫️';

        } else if (
            [51, 53, 55, 56, 57,
             61, 63, 65, 66, 67,
             80, 81, 82].includes(codigo)
        ) {

            iconoClima = '🌧️';

        } else if (
            [71, 73, 75, 77].includes(codigo)
        ) {

            iconoClima = '❄️';

        } else if (
            [95, 96, 99].includes(codigo)
        ) {

            iconoClima = '⛈️';

        }

        const temperaturaTexto =
            temperatura !== null &&
            !Number.isNaN(temperatura)
                ? temperatura.toLocaleString(
                    'es-PE',
                    {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 1
                    }
                )
                : '--';

        const sensacionTexto =
            sensacion !== null &&
            !Number.isNaN(sensacion)
                ? sensacion.toLocaleString(
                    'es-PE',
                    {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 1
                    }
                ) + ' °C'
                : '--';

        const humedadTexto =
            humedad !== null &&
            !Number.isNaN(humedad)
                ? humedad.toLocaleString(
                    'es-PE',
                    {
                        maximumFractionDigits: 0
                    }
                ) + ' %'
                : '--';

        const vientoTexto =
            viento !== null &&
            !Number.isNaN(viento)
                ? viento.toLocaleString(
                    'es-PE',
                    {
                        maximumFractionDigits: 1
                    }
                ) + ' km/h'
                : '--';


        contenido.innerHTML =

            '<div class="clima-card">' +

                '<div class="clima-card-header">' +

                    '<div class="clima-card-titulo">' +

                        '<div class="clima-card-icono">' +
                            iconoClima +
                        '</div>' +

                        '<div>' +

                            '<div class="clima-card-label">' +
                                'Clima actual' +
                            '</div>' +

                            '<div class="clima-card-ciudad">' +
                                ciudad +
                                (
                                    pais
                                        ? ', ' + pais
                                        : ''
                                ) +
                            '</div>' +

                        '</div>' +

                    '</div>' +

                '</div>' +


                '<div class="clima-card-principal">' +

                    '<div>' +

                        '<div class="clima-temperatura">' +
                            temperaturaTexto +
                            '<span class="clima-grados">°C</span>' +
                        '</div>' +

                        '<div class="clima-descripcion">' +
                            iconoClima +
                            ' ' +
                            descripcion +
                        '</div>' +

                    '</div>' +

                '</div>' +


                '<div class="clima-detalles">' +

                    '<div class="clima-detalle">' +

                        '<div class="clima-detalle-icono">🌡️</div>' +

                        '<div class="clima-detalle-label">' +
                            'Sensación' +
                        '</div>' +

                        '<div class="clima-detalle-valor">' +
                            sensacionTexto +
                        '</div>' +

                    '</div>' +


                    '<div class="clima-detalle">' +

                        '<div class="clima-detalle-icono">💧</div>' +

                        '<div class="clima-detalle-label">' +
                            'Humedad' +
                        '</div>' +

                        '<div class="clima-detalle-valor">' +
                            humedadTexto +
                        '</div>' +

                    '</div>' +


                    '<div class="clima-detalle">' +

                        '<div class="clima-detalle-icono">💨</div>' +

                        '<div class="clima-detalle-label">' +
                            'Viento' +
                        '</div>' +

                        '<div class="clima-detalle-valor">' +
                            vientoTexto +
                        '</div>' +

                    '</div>' +

                '</div>' +


                '<div class="clima-actualizado">' +
                    'Datos meteorológicos en tiempo real' +
                '</div>' +

            '</div>';

    }


    /*
    |--------------------------------------------------------------------------
    | OTRO RESULTADO
    |--------------------------------------------------------------------------
    */

    else {

        contenido.innerHTML =

            '<div class="resultado-consulta">' +

                '<div class="resultado-mensaje">' +

                    (
                        datos.mensaje ||
                        'La consulta fue procesada correctamente.'
                    ) +

                '</div>' +

            '</div>';

    }


    mensaje.appendChild(
        avatar
    );


    mensaje.appendChild(
        contenido
    );


    mensajes.appendChild(
        mensaje
    );


    mensaje.scrollIntoView({

        behavior: 'smooth',

        block: 'nearest'

    });

}

/* =========================================================
   MOSTRAR ERROR
========================================================= */

function mostrarErrorConsulta(mensajeError) {

    const mensajes =
        document.querySelector(
            '.consulta-mensajes'
        );


    if (!mensajes) {

        return;

    }


    const mensaje =
        document.createElement('div');

    mensaje.className =
        'consulta-mensaje';


    const avatar =
        document.createElement('div');

    avatar.className =
        'mensaje-avatar';

    avatar.textContent =
        '🤖';


    const contenido =
        document.createElement('div');

    contenido.className =
        'mensaje-texto';


    contenido.innerHTML =

        '<div class="resultado-error">' +

            '<strong>No pude realizar la consulta.</strong>' +

            '<br>' +

            '<small>' +

                mensajeError +

            '</small>' +

        '</div>';


    mensaje.appendChild(
        avatar
    );

    mensaje.appendChild(
        contenido
    );

    mensajes.appendChild(
        mensaje
    );


    mensaje.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });

}

    }
);


/* =========================================================
   NOTIFICACIÓN AUTOMÁTICA
========================================================= */

function cerrarNotificacionAutomatica() {

    const notificacion =
        document.getElementById(
            'automaticNotification'
        );

    if (!notificacion) {

        return;

    }


    notificacion.style.animation =
        'notificationSalida .3s ease forwards';


    setTimeout(
        function () {

            notificacion.remove();

        },
        300
    );

}


document.addEventListener(
    'DOMContentLoaded',
    function () {

        const notificacion =
            document.getElementById(
                'automaticNotification'
            );

        if (!notificacion) {

            return;

        }


        setTimeout(
            function () {

                cerrarNotificacionAutomatica();

            },
            5000
        );

    }
);



/* =========================================================
   NOTIFICACIONES - CERRAR Y SINCRONIZAR CONTADOR
   ========================================================= */

(function () {

    const STORAGE_KEY =
        'sigefiv_notificaciones_cerradas';

    const MAX_VISIBLES =
        5;

    let animandoNotificacion =
        false;


    function obtenerCerradas() {

        try {

            const guardadas =
                localStorage.getItem(
                    STORAGE_KEY
                );

            return new Set(
                guardadas
                    ? JSON.parse(guardadas)
                    : []
            );

        } catch (error) {

            console.warn(
                'No se pudieron leer las notificaciones cerradas.',
                error
            );

            return new Set();

        }

    }


    function guardarCerradas(cerradas) {

        try {

            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(
                    Array.from(cerradas)
                )
            );

        } catch (error) {

            console.warn(
                'No se pudieron guardar las notificaciones cerradas.',
                error
            );

        }

    }


    function actualizarNotificaciones() {

        const lista =
            document.getElementById(
                'notificationList'
            );

        const badge =
            document.getElementById(
                'notificationBadge'
            );

        const more =
            document.getElementById(
                'notificationMore'
            );

        if (!lista) {
            return;
        }


        const cerradas =
            obtenerCerradas();


        const tarjetas =
            Array.from(
                lista.querySelectorAll(
                    '.notification-card-compact'
                )
            );


        tarjetas.forEach(function (tarjeta) {

            const key =
                tarjeta.dataset.notificationKey;

            if (cerradas.has(key)) {

                tarjeta.classList.add(
                    'notification-hidden'
                );

            } else {

                tarjeta.classList.remove(
                    'notification-hidden'
                );

            }

        });


        const activas =
            tarjetas.filter(function (tarjeta) {

                return !tarjeta.classList.contains(
                    'notification-hidden'
                );

            });


        /*
        | Las tarjetas vienen ordenadas desde Blade:
        | movimientos primero y luego las existentes.
        | Aquí solo dejamos visibles las 5 primeras.
        */

        activas.forEach(
            function (tarjeta, index) {

                tarjeta.classList.toggle(
                    'notification-hidden',
                    index >= MAX_VISIBLES
                );

            }
        );


        const visibles =
            activas.slice(
                0,
                MAX_VISIBLES
            );


        const restantes =
            Math.max(
                0,
                activas.length - visibles.length
            );


        if (more) {

            if (restantes > 0) {

                more.textContent =
                    'Ver todas (' +
                    restantes +
                    ' más)';

                more.classList.remove(
                    'd-none'
                );

            } else {

                more.classList.add(
                    'd-none'
                );

            }

        }


        /*
        | El contador representa TODAS las notificaciones activas,
        | no solamente las 5 visibles.
        */

        if (badge) {

            badge.textContent =
                String(
                    activas.length
                );

            badge.dataset.total =
                String(
                    activas.length
                );

            badge.classList.toggle(
                'd-none',
                activas.length === 0
            );

        }


        const empty =
            document.getElementById(
                'notificationEmpty'
            );


        if (activas.length === 0) {

            if (!empty) {

                const mensaje =
                    document.createElement(
                        'div'
                    );

                mensaje.id =
                    'notificationEmpty';

                mensaje.className =
                    'dropdown-item text-center ' +
                    'text-body-secondary ' +
                    'notification-empty';

                mensaje.textContent =
                    'No hay notificaciones';

                lista.appendChild(
                    mensaje
                );

            }

        } else if (empty) {

            empty.remove();

        }

    }


    document.addEventListener(
        'click',
        function (event) {

            const boton =
                event.target.closest(
                    '.notification-dismiss'
                );

            if (!boton) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | La X no debe cerrar el dropdown.
            |--------------------------------------------------------------------------
            */

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();


            if (animandoNotificacion) {
                return;
            }


            const tarjeta =
                boton.closest(
                    '.notification-card-compact'
                );

            if (!tarjeta) {
                return;
            }


            const key =
                tarjeta.dataset.notificationKey;

            if (!key) {
                return;
            }


            animandoNotificacion =
                true;


            const cerradas =
                obtenerCerradas();


            cerradas.add(
                key
            );


            guardarCerradas(
                cerradas
            );


            /*
            |--------------------------------------------------------------------------
            | Animación suave.
            |--------------------------------------------------------------------------
            */

            tarjeta.classList.remove(
                'notification-closing'
            );

            void tarjeta.offsetWidth;

            tarjeta.classList.add(
                'notification-closing'
            );


            /*
            |--------------------------------------------------------------------------
            | Esperamos a que termine la animación antes de actualizar
            | la lista. El panel permanece abierto.
            |--------------------------------------------------------------------------
            */

            setTimeout(
                function () {

                    tarjeta.remove();

                    animandoNotificacion =
                        false;

                    actualizarNotificaciones();

                    /*
                    | Mantenemos abierto el dropdown.
                    */

                    const menu =
                        document.getElementById(
                            'notificationMenu'
                        );

                    const botonCampana =
                        document.getElementById(
                            'notificationButton'
                        );

                    if (
                        menu &&
                        botonCampana
                    ) {

                        menu.classList.add(
                            'show'
                        );

                        botonCampana.setAttribute(
                            'aria-expanded',
                            'true'
                        );

                    }

                },
                570
            );

        },
        true
    );


    /*
    |--------------------------------------------------------------------------
    | Si se hace clic fuera del panel, CoreUI puede cerrarlo normalmente.
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        function (event) {

            if (animandoNotificacion) {
                return;
            }

            const menu =
                document.getElementById(
                    'notificationMenu'
                );

            const botonCampana =
                document.getElementById(
                    'notificationButton'
                );

            if (!menu || !botonCampana) {
                return;
            }


            if (
                !menu.contains(event.target) &&
                !botonCampana.contains(event.target)
            ) {

                menu.classList.remove(
                    'show'
                );

                botonCampana.setAttribute(
                    'aria-expanded',
                    'false'
                );

            }

        }
    );



    document.addEventListener(
        'DOMContentLoaded',
        function () {

            actualizarNotificaciones();

        }
    );


    /*
    | Permite actualizar el contador si otra parte de la página
    | necesita refrescar visualmente las notificaciones.
    */

    window.actualizarNotificacionesSIGEFIV =
        actualizarNotificaciones;

})();

</script>