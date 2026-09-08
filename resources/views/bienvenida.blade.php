@extends('layouts.app')

@section('title', 'Bienvenido a SIGEFIV')

@section('content')

<style>
    .sigi-welcome {
        min-height: calc(100vh - 120px);
        padding: 2rem 0 3rem;
        background:
            radial-gradient(circle at 8% 10%, rgba(13, 110, 253, .08), transparent 28%),
            radial-gradient(circle at 92% 20%, rgba(13, 202, 240, .08), transparent 25%),
            linear-gradient(135deg, #f1f7ff 0%, #f9fbff 50%, #eef7ff 100%);
        border-radius: 24px;
    }

    .welcome-hero {
        position: relative;
        overflow: hidden;
        max-width: 1180px;
        margin: 0 auto;
        padding: 3.2rem 2rem 2.5rem;
        text-align: center;
        background: rgba(255, 255, 255, .92);
        border: 1px solid rgba(255, 255, 255, .9);
        border-radius: 28px;
        box-shadow: 0 20px 55px rgba(30, 65, 105, .12);
    }

    .welcome-hero::before,
    .welcome-hero::after {
        content: "";
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
    }

    .welcome-hero::before {
        width: 230px;
        height: 230px;
        top: -130px;
        left: -70px;
        background: rgba(13, 110, 253, .08);
    }

    .welcome-hero::after {
        width: 260px;
        height: 260px;
        right: -120px;
        bottom: -150px;
        background: rgba(25, 135, 84, .08);
    }

    .welcome-brand {
        position: relative;
        z-index: 1;
        display: inline-flex;
        align-items: center;
        gap: .75rem;
        margin-bottom: 1rem;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -.04em;
        color: #17365d;
    }

    .welcome-brand-icon {
        display: grid;
        place-items: center;
        width: 52px;
        height: 52px;
        border-radius: 15px;
        color: #fff;
        background: linear-gradient(145deg, #0d6efd, #0dcaf0);
        box-shadow: 0 10px 25px rgba(13, 110, 253, .25);
    }

    .welcome-title {
        position: relative;
        z-index: 1;
        margin: 0;
        color: #16325c;
        font-size: clamp(2.25rem, 6vw, 4rem);
        line-height: 1.03;
        font-weight: 800;
        letter-spacing: -.055em;
    }

    .welcome-subtitle {
        position: relative;
        z-index: 1;
        max-width: 760px;
        margin: 1rem auto 0;
        color: #0d6efd;
        font-size: clamp(1.05rem, 2vw, 1.35rem);
        font-weight: 700;
    }

    .welcome-description {
        position: relative;
        z-index: 1;
        max-width: 800px;
        margin: 1rem auto 0;
        color: #5d718c;
        line-height: 1.75;
        font-size: 1rem;
    }

    .welcome-section-title {
        margin: 3rem 0 1.5rem;
        color: #16325c;
        font-size: 1.75rem;
        font-weight: 800;
        letter-spacing: -.035em;
    }

    .welcome-section-title::after {
        content: "";
        display: block;
        width: 55px;
        height: 4px;
        margin: .75rem auto 0;
        border-radius: 50px;
        background: linear-gradient(90deg, #0d6efd, #20c997);
    }

    .welcome-features {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        max-width: 1100px;
        margin: 0 auto;
        text-align: left;
    }

    .welcome-card {
        position: relative;
        overflow: hidden;
        min-height: 205px;
        padding: 1.35rem;
        border: 1px solid #e2eaf4;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(30, 65, 105, .06);
        opacity: 0;
        animation: welcomeCardIn .65s ease forwards;
        transition:
            transform .28s ease,
            box-shadow .28s ease,
            border-color .28s ease;
    }

    .welcome-card:nth-child(2) { animation-delay: .06s; }
    .welcome-card:nth-child(3) { animation-delay: .12s; }
    .welcome-card:nth-child(4) { animation-delay: .18s; }
    .welcome-card:nth-child(5) { animation-delay: .24s; }
    .welcome-card:nth-child(6) { animation-delay: .30s; }
    .welcome-card:nth-child(7) { animation-delay: .36s; }
    .welcome-card:nth-child(8) { animation-delay: .42s; }

    @keyframes welcomeCardIn {
        from {
            opacity: 0;
            transform: translateY(18px) scale(.98);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .welcome-card:hover {
        transform: translateY(-7px);
        border-color: #c9dbf7;
        box-shadow: 0 18px 34px rgba(30, 65, 105, .13);
    }

    .welcome-icon {
        display: grid;
        place-items: center;
        width: 52px;
        height: 52px;
        margin-bottom: 1rem;
        border-radius: 16px;
        background: #eff6ff;
        font-size: 1.5rem;
        transition: transform .28s ease;
    }

    .welcome-card:hover .welcome-icon {
        transform: rotate(-4deg) scale(1.08);
    }

    .welcome-card:nth-child(2) .welcome-icon { background: #ecfeff; }
    .welcome-card:nth-child(3) .welcome-icon { background: #eef2ff; }
    .welcome-card:nth-child(4) .welcome-icon { background: #f5f3ff; }
    .welcome-card:nth-child(5) .welcome-icon { background: #fff7ed; }
    .welcome-card:nth-child(6) .welcome-icon { background: #fffbeb; }
    .welcome-card:nth-child(7) .welcome-icon { background: #fdf2f8; }
    .welcome-card:nth-child(8) .welcome-icon { background: #ecfdf5; }

    .welcome-card h3 {
        margin: 0 0 .55rem;
        color: #17365d;
        font-size: 1.05rem;
        font-weight: 750;
    }

    .welcome-card p {
        margin: 0;
        color: #62748b;
        font-size: .92rem;
        line-height: 1.55;
    }

    .welcome-sigi {
        display: grid;
        grid-template-columns: 1fr 1fr;
        align-items: center;
        gap: 2rem;
        max-width: 1100px;
        margin: 1.5rem auto 0;
        padding: 1.8rem;
        text-align: left;
        border: 1px solid #dceafb;
        border-radius: 24px;
        background: linear-gradient(135deg, #eef6ff, #f3fbff);
    }

    .welcome-sigi h2 {
        margin: 0 0 .6rem;
        color: #17365d;
        font-size: 1.55rem;
        font-weight: 800;
    }

    .welcome-sigi p {
        margin: 0;
        color: #62748b;
        line-height: 1.65;
    }

    .welcome-questions {
        display: grid;
        gap: .55rem;
        margin-top: 1rem;
    }

    .welcome-question {
        padding: .65rem .8rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, .82);
        color: #405a7a;
        font-size: .92rem;
    }

    .welcome-robot {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 210px;
    }

    .welcome-robot-body {
        position: relative;
        width: 160px;
        height: 128px;
        border: 3px solid #cbdcf5;
        border-radius: 42px;
        background: linear-gradient(145deg, #fff, #e7f1ff);
        box-shadow: 0 18px 35px rgba(30, 65, 105, .13);
        animation: welcomeFloat 3.8s ease-in-out infinite;
    }

    @keyframes welcomeFloat {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-8px); }
    }

    .welcome-eye {
        position: absolute;
        top: 43px;
        width: 13px;
        height: 18px;
        border-radius: 50%;
        background: #1d4ed8;
    }

    .welcome-eye.left { left: 48px; }
    .welcome-eye.right { right: 48px; }

    .welcome-smile {
        position: absolute;
        top: 66px;
        left: 50%;
        width: 38px;
        height: 18px;
        transform: translateX(-50%);
        border-bottom: 4px solid #1d4ed8;
        border-radius: 0 0 50px 50px;
    }

    .welcome-antenna {
        position: absolute;
        top: -27px;
        left: 50%;
        width: 5px;
        height: 28px;
        transform: translateX(-50%);
        border-radius: 99px;
        background: #94b8e8;
    }

    .welcome-antenna::after {
        content: "";
        position: absolute;
        top: -8px;
        left: 50%;
        width: 12px;
        height: 12px;
        transform: translateX(-50%);
        border-radius: 50%;
        background: #2563eb;
        box-shadow: 0 0 0 7px rgba(37, 99, 235, .08);
    }

    .welcome-robot-label {
        position: absolute;
        bottom: -25px;
        left: 50%;
        transform: translateX(-50%);
        color: #2563eb;
        font-weight: 800;
        letter-spacing: .08em;
    }

    .welcome-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        max-width: 1100px;
        margin: 1.5rem auto 0;
        padding: 1.3rem 1.4rem;
        border: 1px solid #e2eaf4;
        border-radius: 22px;
        background: rgba(255, 255, 255, .92);
        text-align: left;
    }

    .welcome-transparency {
        display: flex;
        align-items: center;
        gap: .8rem;
        color: #526b88;
        font-size: .94rem;
        line-height: 1.5;
    }

    .welcome-shield {
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: #ecfdf5;
        font-size: 1.25rem;
    }

    .welcome-start {
        position: relative;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 54px;
        padding: 0 1.6rem;
        border: 0;
        border-radius: 15px;
        color: #fff;
        background: linear-gradient(135deg, #0d6efd, #1d4ed8);
        font-weight: 800;
        text-decoration: none;
        box-shadow: 0 10px 22px rgba(13, 110, 253, .24);
        transition: transform .2s ease, box-shadow .2s ease;
        cursor: pointer;
    }

    .welcome-start::after {
        content: "";
        position: absolute;
        top: 0;
        left: -80%;
        width: 50%;
        height: 100%;
        transform: skewX(-20deg);
        background: rgba(255, 255, 255, .2);
        transition: left .55s ease;
    }

    .welcome-start:hover {
        color: #fff;
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(13, 110, 253, .30);
    }

    .welcome-start:hover::after {
        left: 140%;
    }

    .welcome-start:active {
        transform: translateY(1px) scale(.99);
    }

    @media (max-width: 980px) {
        .welcome-features {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .welcome-sigi {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .sigi-welcome {
            padding: 1rem 0 2rem;
        }

        .welcome-hero {
            padding: 2.3rem 1rem 1.7rem;
            border-radius: 20px;
        }

        .welcome-features {
            grid-template-columns: 1fr;
        }

        .welcome-card {
            min-height: auto;
        }

        .welcome-footer {
            flex-direction: column;
            align-items: stretch;
        }

        .welcome-start {
            width: 100%;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .welcome-card,
        .welcome-robot-body,
        .welcome-start {
            animation: none !important;
            transition: none !important;
        }
    }
</style>

<div class="sigi-welcome">

```
<section class="welcome-hero">

    <div class="welcome-brand">
        <span class="welcome-brand-icon">🏘️</span>
        <span>SIGEFIV</span>
    </div>

    <h1 class="welcome-title">
        ¡Bienvenido a SIGEFIV!
    </h1>

    <div class="welcome-subtitle">
        El sistema de gestión financiera de tu junta vecinal
    </div>

    <p class="welcome-description">
        SIGEFIV es una plataforma creada para facilitar la gestión,
        organización y transparencia de la información de nuestra junta
        vecinal. Aquí podrás consultar información financiera, movimientos,
        recibos, períodos, asambleas y otros datos de interés para los vecinos.
    </p>

    <h2 class="welcome-section-title">
        ¿Qué puedes hacer en SIGEFIV?
    </h2>

    <div class="welcome-features">

        <article class="welcome-card">
            <div class="welcome-icon">💰</div>
            <h3>Ingresos y egresos</h3>
            <p>
                Registra y consulta los movimientos económicos de la junta vecinal.
            </p>
        </article>

        <article class="welcome-card">
            <div class="welcome-icon">🧾</div>
            <h3>Recibos y comprobantes</h3>
            <p>
                Mantén identificados y organizados los movimientos y sus comprobantes.
            </p>
        </article>

        <article class="welcome-card">
            <div class="welcome-icon">📊</div>
            <h3>Resúmenes financieros</h3>
            <p>
                Consulta ingresos, egresos, saldo anterior, disponible y saldo de caja.
            </p>
        </article>

        <article class="welcome-card">
            <div class="welcome-icon">📅</div>
            <h3>Gestión de períodos</h3>
            <p>
                Organiza la información por meses y períodos manteniendo la continuidad de los saldos.
            </p>
        </article>

        <article class="welcome-card">
            <div class="welcome-icon">🏠</div>
            <h3>Predios y propietarios</h3>
            <p>
                Administra la información relacionada con los vecinos y sus predios.
            </p>
        </article>

        <article class="welcome-card">
            <div class="welcome-icon">👥</div>
            <h3>Asambleas y citaciones</h3>
            <p>
                Gestiona información relacionada con las asambleas de vecinos.
            </p>
        </article>

        <article class="welcome-card">
            <div class="welcome-icon">🤖</div>
            <h3>SIGI, asistente inteligente</h3>
            <p>
                Realiza consultas sobre la información del sistema utilizando lenguaje natural.
            </p>
        </article>

        <article class="welcome-card">
            <div class="welcome-icon">🔐</div>
            <h3>Acceso seguro</h3>
            <p>
                Cada usuario accede a las funciones e información que le corresponden.
            </p>
        </article>

    </div>

    <section class="welcome-sigi">

        <div>
            <h2>🤖 Conoce a SIGI</h2>

            <p>
                Nuestro asistente inteligente entiende el lenguaje natural
                y te ayuda a obtener la información que necesitas de forma
                rápida y sencilla.
            </p>

            <div class="welcome-questions">
                <div class="welcome-question">
                    💬 ¿Cuánto ingresó en enero?
                </div>

                <div class="welcome-question">
                    💬 ¿Cuánto se gastó este mes?
                </div>

                <div class="welcome-question">
                    💬 ¿Cuál es el saldo de caja?
                </div>

                <div class="welcome-question">
                    💬 Muéstrame los ingresos de febrero.
                </div>
            </div>
        </div>

        <div class="welcome-robot">

            <div class="welcome-robot-body">

                <span class="welcome-antenna"></span>

                <span class="welcome-eye left"></span>
                <span class="welcome-eye right"></span>

                <span class="welcome-smile"></span>

                <span class="welcome-robot-label">
                    SIGI
                </span>

            </div>

        </div>

    </section>

    <div class="welcome-footer">

        <div class="welcome-transparency">

            <div class="welcome-shield">
                ✓
            </div>

            <div>
                <strong>SIGEFIV promueve la transparencia</strong><br>
                La organización y la participación de todos los vecinos.
            </div>

        </div>

        <form
            method="POST"
            action="{{ route('bienvenida.completar') }}"
            style="margin: 0;"
        >
            @csrf

            <button
                type="submit"
                class="welcome-start"
            >
                Comenzar a usar SIGEFIV&nbsp; →
            </button>
        </form>

    </div>

</section>
```

</div>

@endsection
