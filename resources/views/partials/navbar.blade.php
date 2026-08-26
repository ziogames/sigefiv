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

    $notificacionesMovimiento = collect(session('notificaciones_movimientos', []))
        ->map(function ($item) {
            $createdAt = $item['created_at'] ?? null;

            $item['tiempo'] = $createdAt
                ? \Carbon\Carbon::createFromTimestamp($createdAt)->diffForHumans()
                : 'Hace unos segundos';

            $item['_origen'] = 'movimiento';

            return $item;
        })
        ->sortByDesc('created_at')
        ->values();

    $notificacionesExistentes = collect($notificaciones ?? [])->map(function ($item) {
        $item['_origen'] = 'sistema';

        return $item;
    });

    $notificaciones = $notificacionesMovimiento->concat($notificacionesExistentes)->values();
    $cantidadNotificaciones = $notificaciones->count();
@endphp

<header class="header header-sticky p-0 mb-4">
    <div class="container-fluid border-bottom px-4">
        <button class="header-toggler" type="button" onclick="toggleSidebarMovil()">
            <i class="cil-menu icon icon-lg"></i>
        </button>

        <ul class="header-nav ms-auto">
            {{-- =====================================================
                 NOTIFICACIONES
            ====================================================== --}}
            <li class="nav-item dropdown">
                <button class="btn btn-link nav-link py-2 px-2 position-relative" id="notificationButton" data-coreui-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones">
                    <i class="cil-bell fs-5"></i>
                    <span id="notificationBadge"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $cantidadNotificaciones > 0 ? '' : 'd-none' }}"
                        data-total="{{ $cantidadNotificaciones }}">
                        {{ $cantidadNotificaciones }}
                    </span>
                </button>

                <div id="notificationMenu" class="dropdown-menu dropdown-menu-end shadow notifications-menu"
                    style="width:340px;border-radius:16px;">

                    <div class="dropdown-header fw-bold d-flex align-items-center justify-content-between">
                        <span>
                            <i class="cil-bell me-2 text-primary"></i>
                            Notificaciones
                        </span>
                        <button type="button" id="btnActivarNotificaciones" class="btn btn-sm btn-primary"
                            style="border-radius:8px;">
                            <i class="cil-bell me-1"></i>
                            Activar
                        </button>
                    </div>

                    <div class="dropdown-divider"></div>

                    <div id="notificationList">
                        @forelse($notificaciones as $item)
                            @php
                                $notificationKey = sha1(
                                    json_encode([
                                        $item['_origen'] ?? 'sistema',
                                        $item['titulo'] ?? '',
                                        $item['mensaje'] ?? '',
                                        $item['created_at'] ?? '',
                                    ])
                                );
                            @endphp

                            <div class="dropdown-item notification-card notification-card-compact"
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

                                <button type="button" class="notification-dismiss"
                                    data-notification-key="{{ $notificationKey }}" aria-label="Cerrar notificación"
                                    title="Cerrar">
                                    <i class="cil-x"></i>
                                </button>
                            </div>
                        @empty
                            <div id="notificationEmpty"
                                class="dropdown-item text-center text-body-secondary notification-empty">
                                No hay notificaciones
                            </div>
                        @endforelse
                    </div>

                    <button type="button" id="notificationMore" class="notification-more d-none">
                        Ver todas
                    </button>
                </div>
            </li>

            {{-- =====================================================
                 TEMA
            ===================================================== --}}
            <li class="nav-item dropdown">
                <button class="btn btn-link nav-link py-2 px-2 d-flex align-items-center" data-coreui-toggle="dropdown">
                    <i class="cil-contrast icon icon-lg"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <button class="dropdown-item" type="button" data-coreui-theme-value="light">Claro</button>
                    </li>
                    <li>
                        <button class="dropdown-item" type="button" data-coreui-theme-value="dark">Oscuro</button>
                    </li>
                    <li>
                        <button class="dropdown-item active" type="button" data-coreui-theme-value="auto">Automático</button>
                    </li>
                </ul>
            </li>

            {{-- =====================================================
                 USUARIO
            ===================================================== --}}
            <li class="nav-item dropdown">
                <a class="nav-link py-0 pe-0" href="#" data-coreui-toggle="dropdown">
                    <x-avatar :user="auth()->user()" size="40" />
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                    <div class="dropdown-header">
                        <strong>{{ auth()->user()?->name ?? 'Invitado' }}</strong>
                        <br>
                        <small>{{ auth()->user()?->email ?? '' }}</small>
                    </div>
                    <a class="dropdown-item" href="#">
                        <i class="cil-user me-2"></i> Mi Perfil
                    </a>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="dropdown-item" type="submit">
                            <i class="cil-account-logout me-2"></i> Cerrar sesión
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</header>

{{-- =====================================================
     NOTIFICACIÓN AUTOMÁTICA (FLASHEO)
===================================================== --}}
@if (session('success') || session('error') || session('warning'))
    <div id="automaticNotification" class="automatic-notification-container">
        <div class="automatic-notification-icon">
            @if (session('success'))
                <i class="cil-check-circle"></i>
            @elseif(session('error'))
                <i class="cil-x-circle"></i>
            @else
                <i class="cil-warning"></i>
            @endif
        </div>

        <div class="automatic-notification-content">
            <div class="automatic-notification-title">
                @if (session('success'))
                    Acción realizada
                @elseif(session('error'))
                    Se produjo un error
                @else
                    Atención
                @endif
            </div>

            <div class="automatic-notification-message">
                {{ session('success') ?? (session('error') ?? session('warning')) }}
            </div>
        </div>

        <button type="button" class="automatic-notification-close" onclick="cerrarNotificacionAutomatica()">
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
        transition: background .15s ease, color .15s ease, opacity .15s ease, transform .15s ease;
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
</style>

<script>
    /* =========================================================
       NOTIFICACIÓN AUTOMÁTICA
    ========================================================= */
    function cerrarNotificacionAutomatica() {
        const notificacion = document.getElementById('automaticNotification');
        if (!notificacion) {
            return;
        }

        notificacion.style.animation = 'notificationSalida .3s ease forwards';

        setTimeout(function() {
            notificacion.remove();
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const notificacion = document.getElementById('automaticNotification');
        if (!notificacion) {
            return;
        }

        setTimeout(function() {
            cerrarNotificacionAutomatica();
        }, 5000);
    });

    /* =========================================================
       NOTIFICACIONES - CERRAR Y SINCRONIZAR CONTADOR
    ========================================================= */
    (function() {
        const STORAGE_KEY = 'sigefiv_notificaciones_cerradas';
        const MAX_VISIBLES = 5;
        let animandoNotificacion = false;

        function obtenerCerradas() {
            try {
                const guardadas = localStorage.getItem(STORAGE_KEY);
                return new Set(guardadas ? JSON.parse(guardadas) : []);
            } catch (error) {
                console.warn('No se pudieron leer las notificaciones cerradas.', error);
                return new Set();
            }
        }

        function guardarCerradas(cerradas) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(cerradas)));
            } catch (error) {
                console.warn('No se pudieron guardar las notificaciones cerradas.', error);
            }
        }

        function actualizarNotificaciones() {
            const lista = document.getElementById('notificationList');
            const badge = document.getElementById('notificationBadge');
            const more = document.getElementById('notificationMore');

            if (!lista) {
                return;
            }

            const cerradas = obtenerCerradas();
            const tarjetas = Array.from(lista.querySelectorAll('.notification-card-compact'));

            tarjetas.forEach(function(tarjeta) {
                const key = tarjeta.dataset.notificationKey;
                if (cerradas.has(key)) {
                    tarjeta.classList.add('notification-hidden');
                } else {
                    tarjeta.classList.remove('notification-hidden');
                }
            });

            const activas = tarjetas.filter(function(tarjeta) {
                return !tarjeta.classList.contains('notification-hidden');
            });

            activas.forEach(function(tarjeta, index) {
                tarjeta.classList.toggle('notification-hidden', index >= MAX_VISIBLES);
            });

            const visibles = activas.slice(0, MAX_VISIBLES);
            const restantes = Math.max(0, activas.length - visibles.length);

            if (more) {
                if (restantes > 0) {
                    more.textContent = 'Ver todas (' + restantes + ' más)';
                    more.classList.remove('d-none');
                } else {
                    more.classList.add('d-none');
                }
            }

            if (badge) {
                badge.textContent = String(activas.length);
                badge.dataset.total = String(activas.length);
                badge.classList.toggle('d-none', activas.length === 0);
            }

            const empty = document.getElementById('notificationEmpty');

            if (activas.length === 0) {
                if (!empty) {
                    const mensaje = document.createElement('div');
                    mensaje.id = 'notificationEmpty';
                    mensaje.className = 'dropdown-item text-center text-body-secondary notification-empty';
                    mensaje.textContent = 'No hay notificaciones';
                    lista.appendChild(mensaje);
                }
            } else if (empty) {
                empty.remove();
            }
        }

        document.addEventListener('click', function(event) {
            const boton = event.target.closest('.notification-dismiss');
            if (!boton) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();

            if (animandoNotificacion) {
                return;
            }

            const tarjeta = boton.closest('.notification-card-compact');
            if (!tarjeta) {
                return;
            }

            const key = tarjeta.dataset.notificationKey;
            if (!key) {
                return;
            }

            animandoNotificacion = true;
            const cerradas = obtenerCerradas();
            cerradas.add(key);
            guardarCerradas(cerradas);

            tarjeta.classList.remove('notification-closing');
            void tarjeta.offsetWidth;
            tarjeta.classList.add('notification-closing');

            setTimeout(function() {
                tarjeta.remove();
                animandoNotificacion = false;
                actualizarNotificaciones();

                const menu = document.getElementById('notificationMenu');
                const botonCampana = document.getElementById('notificationButton');

                if (menu && botonCampana) {
                    menu.classList.add('show');
                    botonCampana.setAttribute('aria-expanded', 'true');
                }
            }, 570);
        }, true);

        document.addEventListener('click', function(event) {
            if (animandoNotificacion) {
                return;
            }

            const menu = document.getElementById('notificationMenu');
            const botonCampana = document.getElementById('notificationButton');

            if (!menu || !botonCampana) {
                return;
            }

            if (!menu.contains(event.target) && !botonCampana.contains(event.target)) {
                menu.classList.remove('show');
                botonCampana.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            actualizarNotificaciones();
        });

        window.actualizarNotificacionesSIGEFIV = actualizarNotificaciones;
    })();
</script>

<script>
    document.addEventListener('DOMContentLoaded', async function() {
        const boton = document.getElementById('btnActivarNotificaciones');
        if (!boton) {
            return;
        }

        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
            boton.disabled = true;
            boton.textContent = 'No disponible';
            return;
        }

        try {
            const registro = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            const suscripcionExistente = await registro.pushManager.getSubscription();

            if (suscripcionExistente && Notification.permission === 'granted') {
                boton.innerHTML = '<i class="cil-check me-1"></i>Activadas';
                boton.classList.remove('btn-primary');
                boton.classList.add('btn-success');
                boton.disabled = true;
            }
        } catch (error) {
            console.warn('SIGEFIV: no se pudo comprobar la suscripción Push.', error);
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        boton.addEventListener('click', async function() {
            boton.disabled = true;
            const textoOriginal = boton.innerHTML;
            boton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Activando...';

            try {
                const permiso = await Notification.requestPermission();
                if (permiso !== 'granted') {
                    throw new Error('No se concedió permiso para las notificaciones.');
                }

                const registro = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;

                const publicKey = @json(config('services.vapid.public_key'));
                if (!publicKey) {
                    throw new Error('La clave pública VAPID no está configurada.');
                }

                let suscripcion = await registro.pushManager.getSubscription();
                if (!suscripcion) {
                    suscripcion = await registro.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: urlBase64ToUint8Array(publicKey)
                    });
                }

                const datos = suscripcion.toJSON();

                const respuesta = await fetch('{{ route('push-subscriptions.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        endpoint: datos.endpoint,
                        keys: {
                            p256dh: datos.keys.p256dh,
                            auth: datos.keys.auth
                        },
                        contentEncoding: datos.contentEncoding || 'aes128gcm'
                    })
                });

                const resultado = await respuesta.json();
                if (!respuesta.ok) {
                    throw new Error(resultado.message || 'No se pudo guardar la suscripción.');
                }

                boton.innerHTML = '<i class="cil-check me-1"></i>Activadas';
                boton.classList.remove('btn-primary');
                boton.classList.add('btn-success');
            } catch (error) {
                console.error('SIGEFIV Push:', error);
                boton.disabled = false;
                boton.innerHTML = textoOriginal;
                alert(error.message || 'No se pudieron activar las notificaciones.');
            }
        });
    });
</script>