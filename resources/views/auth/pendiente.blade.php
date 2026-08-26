<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Cuenta pendiente - SIGEFIV</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

    <style>

        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(
                    circle at top left,
                    rgba(50, 31, 219, 0.10),
                    transparent 35%
                ),
                radial-gradient(
                    circle at bottom right,
                    rgba(13, 110, 253, 0.08),
                    transparent 35%
                ),
                #f4f6f9;
        }

        .pending-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .pending-card {
            width: 100%;
            max-width: 620px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            box-shadow:
                0 25px 60px rgba(31, 41, 55, 0.12),
                0 8px 20px rgba(31, 41, 55, 0.06);
            overflow: hidden;
        }

        .pending-header {
            position: relative;
            padding: 34px 30px 30px;
            text-align: center;
            background:
                linear-gradient(
                    135deg,
                    #321fdb 0%,
                    #4f46e5 55%,
                    #2563eb 100%
                );
            color: white;
        }

        .pending-header::after {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: rgba(255,255,255,0.07);
            right: -70px;
            top: -80px;
        }

        .pending-header::before {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            left: -50px;
            bottom: -70px;
        }

        .brand-icon {
            position: relative;
            z-index: 2;
            width: 64px;
            height: 64px;
            margin: 0 auto 15px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.25);
            backdrop-filter: blur(8px);
        }

        .brand-icon svg {
            width: 34px;
            height: 34px;
        }

        .brand-title {
            position: relative;
            z-index: 2;
            margin: 0;
            font-size: 30px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .brand-subtitle {
            position: relative;
            z-index: 2;
            margin: 5px 0 0;
            font-size: 14px;
            color: rgba(255,255,255,0.82);
        }

        .pending-content {
            padding: 34px 38px 36px;
            text-align: center;
        }

        .status-icon-wrapper {
            position: relative;
            width: 92px;
            height: 92px;
            margin: 0 auto 22px;
        }

        .status-icon-ring {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: #fff7ed;
            border: 1px solid #fed7aa;
        }

        .status-icon {
            position: absolute;
            inset: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f59e0b;
            color: white;
            box-shadow: 0 10px 25px rgba(245,158,11,0.25);
        }

        .status-icon svg {
            width: 38px;
            height: 38px;
        }

        .welcome-title {
            margin: 0;
            color: #172033;
            font-size: 25px;
            font-weight: 700;
        }

        .welcome-title span {
            color: #321fdb;
        }

        .welcome-text {
            max-width: 480px;
            margin: 12px auto 0;
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
        }

        .status-box {
            margin-top: 27px;
            padding: 18px 20px;
            border-radius: 16px;
            border: 1px solid #fde68a;
            background: linear-gradient(
                135deg,
                #fffbeb,
                #fff7ed
            );
            text-align: left;
        }

        .status-title {
            display: flex;
            align-items: center;
            gap: 9px;
            color: #92400e;
            font-size: 15px;
            font-weight: 700;
        }

        .status-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #f59e0b;
            box-shadow: 0 0 0 4px rgba(245,158,11,0.12);
            flex-shrink: 0;
        }

        .status-description {
            margin: 9px 0 0 18px;
            color: #a16207;
            font-size: 13px;
            line-height: 1.6;
        }

        .user-card {
            margin-top: 20px;
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
            display: flex;
            align-items: center;
            gap: 16px;
            text-align: left;
        }

        .user-avatar {
            width: 58px;
            height: 58px;
            min-width: 58px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(15,23,42,0.12);
        }

        .user-avatar-fallback {
            width: 58px;
            height: 58px;
            min-width: 58px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(
                135deg,
                #321fdb,
                #2563eb
            );
            color: white;
            font-size: 21px;
            font-weight: 700;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(15,23,42,0.12);
        }

        .user-data {
            min-width: 0;
        }

        .user-label {
            margin: 0;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .user-name {
            margin: 3px 0 2px;
            color: #1e293b;
            font-size: 15px;
            font-weight: 700;
        }

        .user-email {
            margin: 0;
            color: #64748b;
            font-size: 13px;
            word-break: break-word;
        }

        .info-text {
            margin: 22px auto 0;
            max-width: 470px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.7;
        }

        .logout-button {
            width: 100%;
            margin-top: 25px;
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: 0;
            border-radius: 12px;
            background: #172033;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition:
                transform 0.2s ease,
                box-shadow 0.2s ease,
                background 0.2s ease;
        }

        .logout-button:hover {
            background: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(15,23,42,0.18);
        }

        .logout-button svg {
            width: 18px;
            height: 18px;
        }

        .footer-text {
            margin-top: 18px;
            color: #94a3b8;
            font-size: 11px;
        }

        @media (max-width: 640px) {

            .pending-wrapper {
                padding: 20px 12px;
            }

            .pending-card {
                border-radius: 20px;
            }

            .pending-header {
                padding: 28px 22px;
            }

            .pending-content {
                padding: 28px 20px 25px;
            }

            .brand-title {
                font-size: 26px;
            }

            .welcome-title {
                font-size: 22px;
            }

            .user-card {
                padding: 16px;
            }

        }

    </style>

</head>

<body>

<div class="pending-wrapper">

    <div class="pending-card">

        {{-- ============================================================
             ENCABEZADO
        ============================================================= --}}

        <div class="pending-header">

            <div class="brand-icon">

                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M3 21h18M4 18h16M5 18V9l7-4 7 4v9M8 12v3M12 12v3M16 12v3"
                    />

                </svg>

            </div>

            <h1 class="brand-title">
                SIGEFIV
            </h1>

            <p class="brand-subtitle">
                Sistema de Gestión Financiera
            </p>

        </div>


        {{-- ============================================================
             CONTENIDO
        ============================================================= --}}

        <div class="pending-content">


            {{-- Icono de espera --}}

            <div class="status-icon-wrapper">

                <div class="status-icon-ring"></div>

                <div class="status-icon">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M12 6v6l4 2"
                        />

                        <circle
                            cx="12"
                            cy="12"
                            r="9"
                        />

                    </svg>

                </div>

            </div>


            {{-- Saludo --}}

            <h2 class="welcome-title">

                Hola,
                <span>
                    {{ auth()->user()->name }}
                </span>

            </h2>


            {{-- Mensaje principal --}}

            <p class="welcome-text">

                Tu cuenta de SIGEFIV fue creada correctamente.
                Solo falta que un administrador revise y active tu cuenta
                para que puedas comenzar a utilizar el sistema.

            </p>


            {{-- Estado de la cuenta --}}

            <div class="status-box">

                <div class="status-title">

                    <span class="status-dot"></span>

                    Cuenta pendiente de aprobación

                </div>

                <p class="status-description">

                    El administrador debe revisar tu cuenta y asignarte
                    los permisos correspondientes a tu rol.

                </p>

            </div>


            {{-- ========================================================
                 INFORMACIÓN DEL USUARIO
            ========================================================= --}}

            <div class="user-card">


                {{-- Avatar --}}

                @if(auth()->user()->foto)

                    <img
                        src="{{ auth()->user()->avatar }}"
                        alt="{{ auth()->user()->name }}"
                        class="user-avatar"
                    >

                @else

                    <div class="user-avatar-fallback">

                        {{ strtoupper(
                            mb_substr(
                                auth()->user()->name,
                                0,
                                1
                            )
                        ) }}

                    </div>

                @endif


                {{-- Datos --}}

                <div class="user-data">

                    <p class="user-label">
                        Cuenta
                    </p>

                    <p class="user-name">

                        {{ auth()->user()->name }}

                    </p>

                    <p class="user-email">

                        {{ auth()->user()->email }}

                    </p>

                </div>

            </div>


            {{-- Información adicional --}}

            <p class="info-text">

                Cuando el administrador active tu cuenta,
                podrás acceder automáticamente a las funciones
                de SIGEFIV correspondientes a tu rol.

            </p>


            {{-- ========================================================
                 CERRAR SESIÓN
            ========================================================= --}}

            <form
                method="POST"
                action="{{ route('logout') }}"
            >

                @csrf

                <button
                    type="submit"
                    class="logout-button"
                >

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"
                        />

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M10 17l5-5-5-5M15 12H3"
                        />

                    </svg>

                    Cerrar sesión

                </button>

            </form>


            <p class="footer-text">

                SIGEFIV · Sistema Integrado de Gestión Financiera

            </p>

        </div>

    </div>

</div>

</body>

</html>