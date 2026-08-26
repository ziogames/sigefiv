@extends('layouts.app')

@section('title', 'SIGI — Asistente Inteligente')

@section('content')

<div class="sigi-shell">
<div class="sigi-layout">

        <aside class="sigi-sidebar">
            <div class="sigi-sidebar-hero">
                <div class="sigi-orbit orbit-one"></div>
                <div class="sigi-orbit orbit-two"></div>
                <div class="sigi-orbit orbit-three"></div>
                <div class="sigi-bot">
                    <div class="sigi-bot-antenna"><span></span></div>
                    <div class="sigi-bot-head">
                        <div class="sigi-bot-glass">
                            <span class="sigi-bot-eye"></span>
                            <span class="sigi-bot-eye"></span>
                            <span class="sigi-bot-mouth"></span>
                        </div>
                    </div>
                    <div class="sigi-bot-neck"></div>
                    <div class="sigi-bot-body">
                        <div class="sigi-bot-logo">SIGI</div>
                        <div class="sigi-bot-light"></div>
                    </div>
                    <div class="sigi-bot-arm left"></div>
                    <div class="sigi-bot-arm right"></div>
                </div>

                <div class="sigi-bot-status">
                    <span class="sigi-bot-status-item">
                        <span class="sigi-live-dot"></span>
                        <span>En línea</span>
                    </span>

                    <span class="sigi-bot-status-divider"></span>

                    <span class="sigi-bot-status-item sigi-bot-secure">
                        <i class="cil-shield-alt"></i>
                        <span>Sesión segura</span>
                    </span>
                </div>

                <div
                    class="sigi-bot-personality"
                    id="sigiBotPersonality"
                    aria-live="polite"
                >
                    <span class="sigi-bot-personality-dot"></span>
                    <span id="sigiBotPersonalityText">
                        Estoy aquí. Te escucho...
                    </span>
                </div>
            </div>

            <div class="sigi-sidebar-welcome">
                <span class="sigi-mini-label">TU ASISTENTE</span>
                <h1>Hola, {{ auth()->user()?->name ?? 'amigo' }}.</h1>
                <p>Estoy listo para conversar contigo y ayudarte a entender SIGEFIV.</p>
            </div>

            <div class="sigi-sidebar-section">
                <div class="sigi-section-title">Puedo ayudarte con</div>
                <div class="sigi-capability-list">
                    <div class="sigi-capability"><span class="cap-icon blue"><i class="cil-wallet"></i></span><div><strong>Finanzas</strong><small>Saldos, ingresos y egresos</small></div></div>
                    <div class="sigi-capability"><span class="cap-icon violet"><i class="cil-chart-line"></i></span><div><strong>Movimientos</strong><small>Consultas y resúmenes</small></div></div>
                    <div class="sigi-capability"><span class="cap-icon cyan"><i class="cil-calendar"></i></span><div><strong>Periodos</strong><small>Estado y cierres</small></div></div>
                    <div class="sigi-capability"><span class="cap-icon amber"><i class="cil-bullhorn"></i></span><div><strong>Asambleas</strong><small>Información y seguimiento</small></div></div>
                    <button type="button" class="sigi-capability sigi-capability-document" data-sigi-open-pdf aria-label="Abrir Estatutos del Grupo 21"><span class="cap-icon emerald"><i class="cil-description"></i></span><div><strong>Estatutos</strong><small>Documento oficial del Grupo 21</small></div><i class="cil-arrow-right sigi-capability-arrow"></i></button>
                </div>
            </div>

            <div class="sigi-sidebar-section">
                <div class="sigi-section-title">Esta sesión</div>
                <div class="sigi-stats-grid">
                    <div class="sigi-stat"><span id="sigiMensajes">1</span><small>Mensajes</small></div>
                    <div class="sigi-stat"><span id="sigiConsultas">0</span><small>Consultas</small></div>
                    <div class="sigi-stat sigi-stat-wide"><span id="sigiTiempo">00:00:00</span><small>Tiempo activo</small></div>
                </div>
            </div>

            <div class="sigi-sidebar-tip">
                <div class="tip-icon"><i class="cil-lightbulb"></i></div>
                <div><strong>Consejo</strong><p>Háblame como lo harías con una persona. No necesitas usar comandos especiales.</p></div>
            </div>
        </aside>

        <main class="sigi-main">
            <div class="sigi-main-header">
                <div>
                    <div class="sigi-breadcrumb"><span>Inicio</span><i class="cil-chevron-right"></i><strong>Conversación</strong></div>
                    <h2>¿En qué puedo ayudarte?</h2>
                    <p>Pregunta, continúa una conversación o pide que analice información de SIGEFIV.</p>
                </div>
                <div class="sigi-main-actions">
                    <div class="sigi-main-badge">
                        <i class="cil-sparkles"></i>
                        <span>IA activa</span>
                    </div>

                    <button
                        type="button"
                        class="sigi-top-action sigi-header-button sigi-clear-button"
                        title="Eliminar conversación"
                        aria-label="Eliminar conversación"
                    >
                        <i class="cil-trash"></i>
                    </button>
                </div>
            </div>

            <section class="sigi-chat-card">
                <div class="sigi-chat-toolbar">
                    <div class="sigi-chat-identity">
                        <div class="sigi-chat-avatar"><i class="cil-bolt"></i><span></span></div>
                        <div><strong>SIGI</strong><small>Asistente de SIGEFIV · Responde en lenguaje natural</small></div>
                    </div>
                    <div class="sigi-chat-tools">
                        <span class="sigi-tool-status"><i class="cil-check-circle"></i> Disponible</span>
                    </div>
                </div>

                <div class="sigi-conversation-scroll" id="sigiConversation">
                    <div class="sigi-welcome-block">
                        <div class="sigi-welcome-icon"><i class="cil-sparkles"></i></div>
                        <div>
                            <span class="sigi-welcome-kicker">BUENAS {{ strtoupper(auth()->user()?->name ?? 'AMIGO') }}</span>
                            <h3>Soy SIGI. Vamos a conversar.</h3>
                            <p>Puedo responder preguntas sobre la gestión de SIGEFIV y ayudarte a encontrar información sin que tengas que navegar por varios módulos.</p>
                        </div>
                    </div>

                    <div class="sigi-message sigi-message-assistant">
                        <div class="sigi-message-content">
                            <div class="sigi-message-meta"><span class="sigi-meta-avatar">S</span><strong>SIGI</strong><span>Ahora</span></div>
                            <div class="sigi-bubble sigi-bubble-assistant">
                                <p>¡Hola, {{ auth()->user()?->name ?? 'amigo' }}! 👋</p>
                                <p class="mb-0">Estoy listo. Puedes preguntarme, por ejemplo, <strong>cuál es el saldo de caja</strong>, cuánto se recaudó, cuáles fueron los últimos egresos o continuar una pregunta anterior.</p>
                            </div>
                        </div>
                    </div>

                    <div class="sigi-suggestions" id="sigiSuggestions">
                        <div class="sigi-suggestions-title">Empieza con una consulta</div>
                        <div class="sigi-suggestion-grid">
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuál es el saldo de caja actual?"><span><i class="cil-wallet"></i></span><div><strong>Saldo de caja</strong><small>¿Cuánto tenemos disponible?</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuánto se recaudó este mes?"><span><i class="cil-chart-line"></i></span><div><strong>Ingresos del período</strong><small>¿Cuánto ingresamos?</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuánto se gastó este mes?"><span><i class="cil-transfer"></i></span><div><strong>Egresos del período</strong><small>¿Cuánto gastamos?</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuáles fueron los últimos movimientos?"><span><i class="cil-list"></i></span><div><strong>Últimos movimientos</strong><small>Revisa los movimientos recientes</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Qué puedo consultar en SIGI?"><span><i class="cil-lightbulb"></i></span><div><strong>Qué puedes hacer</strong><small>Conoce las capacidades de SIGI</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion sigi-suggestion-document" data-sigi-open-pdf><span><i class="cil-description"></i></span><div><strong>Estatutos del Grupo 21</strong><small>Consulta el documento oficial en PDF</small></div><i class="cil-arrow-right"></i></button>
                        </div>
                    </div>
                </div>

                <div class="sigi-composer-wrap">
                    <div class="sigi-composer">
                        <div class="sigi-composer-top">
                            <textarea id="sigiConsulta" class="sigi-textarea" rows="2" placeholder="Escribe aquí lo que quieres saber..." aria-label="Consulta para SIGI"></textarea>
                        </div>
                        <div class="sigi-composer-bottom">
                            <div class="sigi-composer-hint"><span class="hint-dot"></span><span>Lenguaje natural</span><span class="hint-separator">·</span><span>Enter para enviar</span></div>
                            <button type="button" class="sigi-send-button" id="btnSigiConsultar"><span>Enviar</span><i class="cil-arrow-right"></i></button>
                        </div>
                    </div>
                    <div class="sigi-disclaimer"><i class="cil-info"></i> SIGI puede cometer errores. Verifica la información importante antes de tomar decisiones.</div>
                </div>
            </section>
        </main>
    </div>
</div>

@push('styles')
<style>
:root { --sigi-ink:#172033; --sigi-muted:#718096; --sigi-line:#e7ebf2; --sigi-bg:#f4f7fb; --sigi-blue:#2563eb; --sigi-violet:#6d5dfc; }
/* Tema oscuro: solo cambia colores; no modifica la estructura de SIGI. */
[data-coreui-theme="dark"] .sigi-shell{
    --sigi-ink:#e8eefc;
    --sigi-muted:#9aa9c7;
    --sigi-line:#263653;
    --sigi-bg:#0b1426;
    background:radial-gradient(circle at 82% 8%,rgba(99,102,241,.13),transparent 27%),linear-gradient(135deg,#0b1426 0%,#0f1b31 100%);
    color:var(--sigi-ink);
}
[data-coreui-theme="dark"] 




[data-coreui-theme="dark"] .sigi-main-header h2{color:#f1f5ff}
[data-coreui-theme="dark"] .sigi-main-header p{color:#9aa9c7}
[data-coreui-theme="dark"] .sigi-breadcrumb{color:#7f8fab}
[data-coreui-theme="dark"] .sigi-breadcrumb strong{color:#c3cee3}
[data-coreui-theme="dark"] .sigi-main-badge{background:#171b3b;border-color:#3a3d72;color:#a99cff}
[data-coreui-theme="dark"] .sigi-chat-card{background:rgba(15,27,49,.94);border-color:#263653;box-shadow:0 18px 55px rgba(0,0,0,.25)}
[data-coreui-theme="dark"] .sigi-chat-toolbar{background:rgba(15,27,49,.94);border-bottom-color:#263653}
[data-coreui-theme="dark"] .sigi-chat-identity strong{color:#eef3ff}
[data-coreui-theme="dark"] .sigi-chat-identity small,
[data-coreui-theme="dark"] .sigi-tool-status{color:#9aa9c7}
[data-coreui-theme="dark"] .sigi-chat-avatar span{border-color:#14213a}
[data-coreui-theme="dark"] .sigi-conversation-scroll{background:linear-gradient(180deg,#0d182b 0%,#0b1426 100%)}
[data-coreui-theme="dark"] .sigi-conversation-scroll::-webkit-scrollbar-thumb{background:#30405f}
[data-coreui-theme="dark"] .sigi-welcome-block{background:linear-gradient(110deg,#14213a,#17243f);border-color:#2b3b5b}
[data-coreui-theme="dark"] .sigi-welcome-icon{background:linear-gradient(145deg,#1c3154,#29255a);color:#a99cff}
[data-coreui-theme="dark"] .sigi-welcome-kicker{color:#aebbd2}
[data-coreui-theme="dark"] .sigi-welcome-block h3{color:#f1f5ff}
[data-coreui-theme="dark"] .sigi-welcome-block p{color:#9aa9c7}
[data-coreui-theme="dark"] .sigi-message-meta{color:#8190aa}
[data-coreui-theme="dark"] .sigi-message-meta strong{color:#cbd6ea}
[data-coreui-theme="dark"] .sigi-meta-avatar{background:#1d2b49;color:#a99cff}
[data-coreui-theme="dark"] .sigi-bubble-assistant{background:#14213a;border-color:#2b3b5b;color:#d7e0f1}
[data-coreui-theme="dark"] .sigi-message-time{color:#71809b}
[data-coreui-theme="dark"] .sigi-suggestions-title{color:#7f8fab}
[data-coreui-theme="dark"] .sigi-suggestion{background:#14213a;border-color:#2b3b5b;color:#dbe5f7}
[data-coreui-theme="dark"] .sigi-suggestion>span{background:#1b2c4c;color:#9da8ff}
[data-coreui-theme="dark"] .sigi-suggestion small{color:#8f9db7}
[data-coreui-theme="dark"] .sigi-suggestion>i{color:#71809b}
[data-coreui-theme="dark"] .sigi-suggestion:hover{border-color:#4b5f91;box-shadow:0 9px 22px rgba(37,99,235,.14)}
[data-coreui-theme="dark"] .sigi-suggestion-document{background:linear-gradient(135deg,#17243f,#1b2947);border-color:#344466}
[data-coreui-theme="dark"] .sigi-suggestion-document>span{background:linear-gradient(145deg,#263866,#302d68)}
[data-coreui-theme="dark"] .sigi-composer-wrap{background:#0f1b31;border-top-color:#263653}
[data-coreui-theme="dark"] .sigi-composer{background:#0d182b;border-color:#314463;box-shadow:0 8px 28px rgba(0,0,0,.18)}
[data-coreui-theme="dark"] .sigi-textarea{color:#e8eefc}
[data-coreui-theme="dark"] .sigi-textarea::placeholder{color:#6f7e99}
[data-coreui-theme="dark"] .sigi-composer-hint,
[data-coreui-theme="dark"] .sigi-disclaimer{color:#71809b}
[data-coreui-theme="dark"] .hint-separator{color:#43516b}
[data-coreui-theme="dark"] .sigi-typing{background:#14213a;border-color:#2b3b5b}
[data-coreui-theme="dark"] .sigi-result-card{background:#14213a;border-color:#2b3b5b;color:#d7e0f1}
[data-coreui-theme="dark"] .sigi-result-title{color:#dbe5f7}
[data-coreui-theme="dark"] .sigi-result-table-wrap{background:#0d182b;border-color:#2b3b5b}
[data-coreui-theme="dark"] .sigi-result-table th{background:#17243f;color:#aebbd2;border-bottom-color:#2b3b5b}
[data-coreui-theme="dark"] .sigi-result-table td{color:#c7d2e6;border-bottom-color:#22314d}
[data-coreui-theme="dark"] .sigi-result-json{background:#070e1c}
[data-coreui-theme="dark"] .sigi-safe-notice{background:#121f35;border-color:#2b3b5b;color:#aebbd2}
[data-coreui-theme="dark"] .sigi-summary-item{background:#14213a;border-color:#2b3b5b}
[data-coreui-theme="dark"] .sigi-summary-item span,
[data-coreui-theme="dark"] .sigi-weather-head small,
[data-coreui-theme="dark"] .sigi-weather-grid span{color:#7f8fab}
[data-coreui-theme="dark"] .sigi-summary-item strong,
[data-coreui-theme="dark"] .sigi-weather-head strong,
[data-coreui-theme="dark"] .sigi-weather-grid strong{color:#dbe5f7}
[data-coreui-theme="dark"] .sigi-weather-card{background:linear-gradient(145deg,#14213a,#17243f);border-color:#2b3b5b}
[data-coreui-theme="dark"] .sigi-weather-icon{background:#1b2c4c;color:#9da8ff}
[data-coreui-theme="dark"] .sigi-weather-grid>div{background:#14213a;border-color:#2b3b5b}
[data-coreui-theme="dark"] .sigi-weather-temperature{color:#f1f5ff}
[data-coreui-theme="dark"] .sigi-weather-description{color:#aebbd2}
[data-coreui-theme="dark"] .sigi-simple-result{color:#dbe5f7}
[data-coreui-theme="dark"] .sigi-error{background:#2a171b;border-color:#71343e;color:#fecaca}
[data-coreui-theme="dark"] .sigi-error .sigi-result-title{color:#fca5a5}
[data-coreui-theme="dark"] .sigi-theme-toggle{color:#facc15}

.sigi-shell{min-height:calc(100vh - 64px);background:radial-gradient(circle at 82% 8%,rgba(79,70,229,.08),transparent 27%),linear-gradient(135deg,#f8fafc 0%,#f2f5fa 100%);color:var(--sigi-ink);padding-bottom:28px}
.sigi-topbar{height:76px;background:rgba(255,255,255,.88);backdrop-filter:blur(16px);border-bottom:1px solid rgba(226,232,240,.9);display:flex;align-items:center;justify-content:space-between;padding:0 30px;position:sticky;top:0;z-index:20;box-shadow:0 5px 25px rgba(15,23,42,.04)}

.sigi-layout{display:grid;grid-template-columns:310px minmax(0,1fr);max-width:1500px;margin:0 auto;min-height:calc(100vh - 92px);padding:22px 24px;gap:22px}.sigi-sidebar{background:linear-gradient(180deg,#101a33 0%,#172341 100%);border-radius:24px;color:#fff;padding:24px 20px;box-shadow:0 18px 45px rgba(15,23,42,.16);position:relative;overflow:hidden}.sigi-sidebar:after{content:"";position:absolute;inset:auto -70px -100px auto;width:240px;height:240px;border-radius:50%;background:rgba(99,102,241,.13);filter:blur(10px)}.sigi-sidebar-hero{height:165px;position:relative;display:grid;place-items:center}.sigi-orbit{position:absolute;border:1px solid rgba(129,140,248,.18);border-radius:50%;transform:rotate(-18deg)}.orbit-one{width:150px;height:72px}.orbit-two{width:185px;height:96px;transform:rotate(34deg)}.orbit-three{width:125px;height:125px;border-style:dashed;animation:sigiSpin 16s linear infinite}.sigi-bot{width:104px;height:130px;position:relative;z-index:2;filter:drop-shadow(0 16px 18px rgba(0,0,0,.25))}.sigi-bot-antenna{position:absolute;left:50%;top:-13px;width:2px;height:18px;background:#7c8cff;transform:translateX(-50%)}.sigi-bot-antenna span{position:absolute;top:-5px;left:50%;transform:translateX(-50%);width:8px;height:8px;border-radius:50%;background:#67e8f9;box-shadow:0 0 14px #67e8f9}.sigi-bot-head{position:absolute;top:4px;left:14px;width:76px;height:55px;border-radius:23px 23px 18px 18px;background:linear-gradient(145deg,#dbeafe,#fff);border:3px solid #93c5fd}.sigi-bot-glass{position:absolute;inset:8px;border-radius:15px;background:linear-gradient(145deg,#0b1730,#1e2d55);display:flex;align-items:center;justify-content:center;gap:18px;box-shadow:inset 0 0 20px rgba(96,165,250,.12)}.sigi-bot-eye{width:9px;height:12px;border-radius:50%;background:#67e8f9;box-shadow:0 0 10px rgba(103,232,249,.9)}.sigi-bot-mouth{position:absolute;width:20px;height:8px;border-bottom:2px solid #818cf8;border-radius:0 0 20px 20px;bottom:9px;left:50%;transform:translateX(-50%)}.sigi-bot-neck{position:absolute;left:43px;top:57px;width:18px;height:9px;background:#a5b4fc}.sigi-bot-body{position:absolute;left:20px;top:64px;width:64px;height:58px;border-radius:20px 20px 15px 15px;background:linear-gradient(145deg,#c7d2fe,#fff);border:3px solid #93c5fd;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px}.sigi-bot-logo{font-size:12px;font-weight:900;letter-spacing:2px;color:#26386e}.sigi-bot-light{width:22px;height:4px;border-radius:10px;background:#67e8f9;box-shadow:0 0 10px #67e8f9}.sigi-bot-arm{position:absolute;top:74px;width:14px;height:42px;border-radius:10px;background:#a5b4fc}.sigi-bot-arm.left{left:8px;transform:rotate(16deg)}.sigi-bot-arm.right{right:8px;transform:rotate(-16deg)}
.sigi-sidebar-welcome{padding:0 4px 20px;border-bottom:1px solid rgba(255,255,255,.09)}.sigi-mini-label{font-size:9px;letter-spacing:1.8px;font-weight:800;color:#8ea1c9}.sigi-sidebar-welcome h1{font-size:24px;letter-spacing:-.5px;margin:7px 0 5px;color:#fff}.sigi-sidebar-welcome p{font-size:12px;line-height:1.65;color:#aab6d0;margin:0}.sigi-online{display:inline-flex;align-items:center;gap:6px;margin-top:11px;padding:5px 9px;border-radius:20px;background:rgba(34,197,94,.09);color:#9ae6b4;font-size:10px;font-weight:700}.sigi-online span{width:6px;height:6px;border-radius:50%;background:#4ade80}.sigi-sidebar-section{padding-top:18px}.sigi-section-title{text-transform:uppercase;letter-spacing:1.1px;font-size:9px;font-weight:800;color:#788aad;margin-bottom:10px}.sigi-capability-list{display:grid;gap:7px}.sigi-capability{display:flex;align-items:center;gap:10px;padding:9px;border-radius:12px;background:rgba(255,255,255,.035);border:1px solid rgba(255,255,255,.05)}.sigi-capability strong{display:block;font-size:11px;color:#eaf0ff}.sigi-capability small{display:block;font-size:9px;color:#8999ba;margin-top:2px}.sigi-capability-document{width:100%;font:inherit;color:inherit;text-align:left;cursor:pointer;transition:.2s;position:relative}.sigi-capability-document:hover{background:rgba(255,255,255,.075);border-color:rgba(129,140,248,.22);transform:translateY(-1px)}.sigi-capability-arrow{margin-left:auto;font-size:8px;color:#7f90b3;transition:.2s}.sigi-capability-document:hover .sigi-capability-arrow{color:#c7d2fe;transform:translateX(2px)}.cap-icon.emerald{background:rgba(16,185,129,.14);color:#6ee7b7}.cap-icon{width:29px;height:29px;border-radius:9px;display:grid;place-items:center;font-size:12px}.cap-icon.blue{background:rgba(59,130,246,.16);color:#7dd3fc}.cap-icon.violet{background:rgba(124,58,237,.16);color:#c4b5fd}.cap-icon.cyan{background:rgba(6,182,212,.14);color:#67e8f9}.cap-icon.amber{background:rgba(245,158,11,.14);color:#fcd34d}.sigi-stats-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}.sigi-stat{border:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.035);border-radius:11px;padding:9px}.sigi-stat-wide{grid-column:1/-1}.sigi-stat span{display:block;font-size:16px;font-weight:800;color:#fff}.sigi-stat small{font-size:9px;color:#7f90b3}.sigi-sidebar-tip{display:flex;gap:9px;margin-top:18px;padding:11px;border-radius:13px;background:linear-gradient(145deg,rgba(99,102,241,.16),rgba(59,130,246,.07));border:1px solid rgba(129,140,248,.13);position:relative;z-index:2}.tip-icon{width:27px;height:27px;border-radius:8px;background:rgba(250,204,21,.12);color:#fde68a;display:grid;place-items:center;flex:0 0 auto}.sigi-sidebar-tip strong{font-size:10px}.sigi-sidebar-tip p{font-size:9px;line-height:1.55;color:#9baacd;margin:3px 0 0}
.sigi-main{min-width:0;display:flex;flex-direction:column}.sigi-main-header{display:flex;justify-content:space-between;align-items:flex-end;padding:5px 7px 18px}.sigi-breadcrumb{display:flex;align-items:center;gap:7px;font-size:10px;color:#9aa4b5;margin-bottom:7px}.sigi-breadcrumb i{font-size:8px}.sigi-breadcrumb strong{color:#5c6678}.sigi-main-header h2{font-size:25px;letter-spacing:-.8px;margin:0;color:#162033}.sigi-main-header p{font-size:12px;color:#7b8798;margin:5px 0 0}.sigi-main-badge{display:flex;align-items:center;gap:7px;padding:8px 11px;border:1px solid #e0e7ff;background:#f5f7ff;color:#4f46e5;border-radius:10px;font-size:10px;font-weight:800}.sigi-chat-card{background:rgba(255,255,255,.92);border:1px solid #e4e8ef;border-radius:24px;box-shadow:0 18px 55px rgba(15,23,42,.08);overflow:hidden;display:flex;flex-direction:column;min-height:700px;flex:1}.sigi-chat-toolbar{height:70px;display:flex;align-items:center;justify-content:space-between;padding:0 22px;border-bottom:1px solid #edf0f4;background:rgba(255,255,255,.9)}.sigi-chat-identity{display:flex;align-items:center;gap:10px}.sigi-chat-avatar{width:37px;height:37px;border-radius:12px;background:linear-gradient(145deg,#1d4ed8,#6366f1);color:#fff;display:grid;place-items:center;position:relative;box-shadow:0 7px 16px rgba(79,70,229,.2)}.sigi-chat-avatar span{position:absolute;width:7px;height:7px;border-radius:50%;right:-2px;bottom:-2px;background:#22c55e;border:2px solid #fff}.sigi-chat-identity strong{display:block;font-size:13px}.sigi-chat-identity small{display:block;color:#8b95a5;font-size:9px;margin-top:2px}.sigi-tool-status{font-size:10px;color:#64748b;display:flex;align-items:center;gap:5px}.sigi-tool-status i{color:#22c55e}.sigi-conversation-scroll{flex:1;overflow:auto;padding:30px 34px 22px;scroll-behavior:smooth;background:linear-gradient(180deg,#fff 0%,#fbfcfe 100%)}.sigi-conversation-scroll::-webkit-scrollbar{width:7px}.sigi-conversation-scroll::-webkit-scrollbar-thumb{background:#dce2eb;border-radius:10px}.sigi-welcome-block{max-width:780px;margin:0 auto 24px;padding:18px 20px;border-radius:18px;background:linear-gradient(110deg,#f4f7ff,#f8fbff);border:1px solid #e6ebf8;display:flex;gap:14px;align-items:flex-start}.sigi-welcome-icon{width:38px;height:38px;flex:0 0 auto;border-radius:12px;background:linear-gradient(145deg,#dbeafe,#ede9fe);color:#4f46e5;display:grid;place-items:center}.sigi-welcome-kicker{font-size:9px;font-weight:900;letter-spacing:1.4px;color:#667085}.sigi-welcome-block h3{font-size:16px;margin:3px 0 5px;letter-spacing:-.2px}.sigi-welcome-block p{font-size:11px;line-height:1.65;color:#667085;margin:0}.sigi-message{display:flex;max-width:860px;margin:0 auto 17px}.sigi-message-assistant{justify-content:flex-start}.sigi-message-user{justify-content:flex-end}.sigi-message-content{max-width:78%;min-width:0}.sigi-message-user .sigi-message-content{display:flex;flex-direction:column;align-items:flex-end}.sigi-message-meta{display:flex;align-items:center;gap:6px;margin:0 0 6px 3px;color:#8993a4;font-size:9px}.sigi-message-meta strong{font-size:10px;color:#475467}.sigi-message-meta span:last-child{margin-left:3px}.sigi-meta-avatar{width:20px;height:20px;border-radius:7px;background:#eef2ff;color:#4f46e5;display:grid;place-items:center;font-weight:900}.sigi-bubble{padding:13px 15px;border-radius:16px;font-size:12px;line-height:1.7;box-shadow:0 4px 14px rgba(15,23,42,.035)}.sigi-bubble p{margin:0 0 7px}.sigi-bubble-assistant{background:#f7f9fc;border:1px solid #e9edf3;border-top-left-radius:5px;color:#3e4a5d}.sigi-message-user .sigi-bubble{background:linear-gradient(145deg,#2563eb,#4f46e5);color:#fff;border-top-right-radius:5px;box-shadow:0 9px 22px rgba(37,99,235,.17)}.sigi-message-time{font-size:8px;color:#a2abba;margin-top:5px}.sigi-suggestions{max-width:860px;margin:5px auto 0}.sigi-suggestions-title{font-size:9px;text-transform:uppercase;letter-spacing:1.1px;color:#9aa3b2;font-weight:800;margin:0 0 9px 2px}.sigi-suggestion-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}.sigi-suggestion{display:grid;grid-template-columns:34px 1fr 15px;align-items:center;gap:9px;text-align:left;padding:11px;border:1px solid #e6eaf0;background:#fff;border-radius:13px;cursor:pointer;transition:.2s;color:#253047}.sigi-suggestion>span{width:32px;height:32px;border-radius:9px;background:#f0f4ff;color:#4f46e5;display:grid;place-items:center}.sigi-suggestion strong{display:block;font-size:10px}.sigi-suggestion small{display:block;font-size:9px;color:#8a94a5;margin-top:2px}.sigi-suggestion>i{font-size:8px;color:#a1a9b7;transition:.2s}.sigi-suggestion:hover{border-color:#cbd5ff;transform:translateY(-2px);box-shadow:0 9px 22px rgba(37,99,235,.08)}.sigi-suggestion:hover>i{color:#4f46e5;transform:translateX(2px)}.sigi-suggestion-document{border-color:#dfe5f7;background:linear-gradient(135deg,#fbfcff,#f7f9ff)}.sigi-suggestion-document>span{background:linear-gradient(145deg,#e0e7ff,#ede9fe);color:#4f46e5}.sigi-suggestion-document:hover{border-color:#bfcaff;background:#f8f9ff}.sigi-composer-wrap{padding:17px 22px 15px;border-top:1px solid #edf0f4;background:#fff}.sigi-composer{border:1.5px solid #dbe1eb;border-radius:17px;background:#fff;box-shadow:0 8px 28px rgba(15,23,42,.05);transition:.2s}.sigi-composer:focus-within{border-color:#9bb5ff;box-shadow:0 0 0 4px rgba(37,99,235,.07),0 10px 30px rgba(15,23,42,.06)}.sigi-composer-top{padding:13px 15px 4px}.sigi-textarea{width:100%;border:0!important;outline:0!important;resize:none;background:transparent;color:#1f2937;font-size:12px;line-height:1.6;box-shadow:none!important;min-height:46px}.sigi-textarea::placeholder{color:#a1a9b7}.sigi-composer-bottom{display:flex;align-items:center;justify-content:space-between;padding:5px 9px 9px 14px}.sigi-composer-hint{display:flex;align-items:center;gap:6px;font-size:9px;color:#9aa3b2}.hint-dot{width:5px;height:5px;border-radius:50%;background:#22c55e}.hint-separator{color:#d1d5db}.sigi-send-button{border:0;background:linear-gradient(145deg,#2563eb,#4f46e5);color:#fff;height:36px;padding:0 13px 0 15px;border-radius:11px;display:flex;align-items:center;gap:8px;font-size:10px;font-weight:800;cursor:pointer;box-shadow:0 7px 17px rgba(37,99,235,.2);transition:.2s}.sigi-send-button:hover{transform:translateY(-1px);box-shadow:0 10px 20px rgba(37,99,235,.25)}.sigi-send-button:disabled{opacity:.65;cursor:not-allowed;transform:none}.sigi-disclaimer{display:flex;justify-content:center;align-items:center;gap:5px;margin-top:8px;color:#a0a8b5;font-size:8px}.sigi-disclaimer i{font-size:9px}
.sigi-typing{display:flex;gap:4px;padding:12px 14px;border-radius:14px;border-top-left-radius:5px;background:#f7f9fc;border:1px solid #e9edf3;width:max-content}.sigi-typing span{width:5px;height:5px;border-radius:50%;background:#98a2b3;animation:sigiTyping 1.1s infinite}.sigi-typing span:nth-child(2){animation-delay:.15s}.sigi-typing span:nth-child(3){animation-delay:.3s}.sigi-result-card{background:#f7f9fc;border:1px solid #e4e9f0;border-radius:14px;border-top-left-radius:5px;padding:13px 15px;font-size:12px;line-height:1.65;color:#3e4a5d}.sigi-result-title{font-size:10px;font-weight:900;color:#344054;margin-bottom:8px}.sigi-result-table-wrap{margin-top:11px;overflow:auto;border:1px solid #e4e9f0;border-radius:10px;background:#fff;max-height:330px}.sigi-result-table{border-collapse:collapse;width:100%;font-size:9px;white-space:nowrap}.sigi-result-table th{background:#f5f7fa;color:#667085;font-weight:800;text-align:left;padding:8px;border-bottom:1px solid #e4e9f0}.sigi-result-table td{padding:8px;border-bottom:1px solid #eef1f5;color:#475467}.sigi-result-table td:first-child{font-variant-numeric:tabular-nums;white-space:nowrap}.sigi-result-table tr:last-child td{border-bottom:0}.sigi-result-json{margin-top:10px;padding:11px;background:#101828;color:#d1e9ff;border-radius:10px;overflow:auto;font:10px/1.6 ui-monospace,SFMono-Regular,Menlo,monospace;white-space:pre-wrap}.sigi-error{background:#fff7f7;border-color:#fecaca;color:#991b1b}.sigi-error .sigi-result-title{color:#b91c1c}
.sigi-safe-notice{margin-top:10px;padding:11px 12px;border-radius:10px;background:#f8fafc;border:1px solid #e5e7eb;color:#667085;display:flex;align-items:flex-start;gap:8px;font-size:10px;line-height:1.55}.sigi-safe-notice i{color:#4f46e5;margin-top:2px}.sigi-summary-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin-top:10px}.sigi-summary-item{padding:9px 10px;background:#fff;border:1px solid #e5e7eb;border-radius:9px}.sigi-summary-item span{display:block;font-size:8px;color:#98a2b3;text-transform:uppercase;letter-spacing:.6px}.sigi-summary-item strong{display:block;margin-top:3px;font-size:10px;color:#344054}.sigi-weather-card{margin-top:10px;padding:14px;border-radius:14px;background:linear-gradient(145deg,#f5f8ff,#fff);border:1px solid #e2e8f0}.sigi-weather-head{display:flex;align-items:center;gap:9px}.sigi-weather-icon{width:34px;height:34px;border-radius:10px;background:#eef2ff;color:#4f46e5;display:grid;place-items:center}.sigi-weather-head strong{display:block;font-size:11px;color:#344054}.sigi-weather-head small{display:block;margin-top:2px;font-size:9px;color:#98a2b3}.sigi-weather-main{display:flex;align-items:end;gap:12px;margin-top:13px}.sigi-weather-temperature{font-size:25px;font-weight:900;color:#172033}.sigi-weather-description{font-size:10px;color:#667085;padding-bottom:3px}.sigi-weather-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-top:12px}.sigi-weather-grid>div{padding:8px;border-radius:9px;background:#fff;border:1px solid #edf0f4}.sigi-weather-grid span{display:block;font-size:8px;color:#98a2b3}.sigi-weather-grid strong{display:block;margin-top:3px;font-size:9px;color:#475467}.sigi-simple-result{margin-top:8px;font-weight:800;color:#344054}
@keyframes sigiPulse{0%,100%{transform:scale(.88);opacity:.8}50%{transform:scale(1.1);opacity:.35}}@keyframes sigiSpin{to{transform:rotate(342deg)}}@keyframes sigiTyping{0%,60%,100%{transform:translateY(0);opacity:.45}30%{transform:translateY(-4px);opacity:1}}
@media(max-width:1100px){.sigi-layout{grid-template-columns:255px 1fr}.sigi-sidebar{padding:18px 15px}.sigi-sidebar-hero{height:135px}.sigi-bot{transform:scale(.82)}}
@media(max-width:800px){.sigi-layout{display:block;padding:12px}.sigi-sidebar{display:none}.sigi-main-header{padding:10px 3px 14px}.sigi-main-header h2{font-size:21px}.sigi-main-header p{font-size:10px}.sigi-chat-card{min-height:calc(100vh - 145px);border-radius:18px}.sigi-conversation-scroll{padding:22px 15px}.sigi-suggestion-grid{grid-template-columns:1fr}.sigi-message-content{max-width:90%}.sigi-composer-wrap{padding:12px}.sigi-chat-toolbar{padding:0 14px}.sigi-chat-identity small{display:none}.sigi-main-badge{display:none}}

/*
|--------------------------------------------------------------------------
| SCROLL INTERNO DE LA CONVERSACIÓN DE SIGI
|--------------------------------------------------------------------------
|
| Mantiene fija la interfaz de SIGI y permite desplazar únicamente
| el historial de mensajes cuando la conversación crece.
|
*/
.sigi-conversation {
    max-height: 60vh;
    overflow-y: auto;
    overflow-x: hidden;
    scroll-behavior: smooth;
    overscroll-behavior: contain;
    scrollbar-width: thin;
}

/*
 * En pantallas grandes aprovechamos mejor el espacio disponible.
 */
@media (min-width: 992px) {
    .sigi-conversation {
        max-height: 65vh;
    }
}

/*
 * En móviles dejamos un área cómoda para escribir y leer.
 */
@media (max-width: 991.98px) {
    .sigi-conversation {
        max-height: 55vh;
    }
}


/*
|--------------------------------------------------------------------------
| ALTURA REAL DEL CHAT
|--------------------------------------------------------------------------
|
| El contenedor padre debe tener una altura limitada para que
| .sigi-conversation-scroll pueda convertirse en un scroll interno.
| No modificamos la estructura HTML ni el funcionamiento del chat.
|
*/
.sigi-chat-card {
    height: calc(100vh - 145px);
    min-height: 0;
}

.sigi-conversation-scroll {
    min-height: 0;
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: hidden;
}

/*
 * En pantallas pequeñas dejamos que el chat use prácticamente
 * toda la altura disponible, respetando el encabezado de la página.
 */
@media (max-width: 991.98px) {
    .sigi-chat-card {
        height: calc(100vh - 110px);
        min-height: 0;
    }
}


/*
|--------------------------------------------------------------------------
| AJUSTE FINAL: SCROLL SOLO EN EL CHAT
|--------------------------------------------------------------------------
|
| El layout completo queda limitado al alto visible de la ventana.
| El scroll vertical pertenece exclusivamente a #sigiConversation.
|
*/
.sigi-layout {
    height: calc(100vh - 92px);
    min-height: 0;
    box-sizing: border-box;
}

.sigi-main {
    min-height: 0;
    height: 100%;
}

.sigi-chat-card {
    height: auto;
    min-height: 0;
    flex: 1 1 auto;
}

.sigi-conversation-scroll {
    min-height: 0;
    height: auto;
    flex: 1 1 auto;
    overflow-y: auto;
    overflow-x: hidden;
    overscroll-behavior: contain;
    -webkit-overflow-scrolling: touch;
}

/*
 * El composer permanece fuera del área desplazable.
 */
.sigi-composer-wrap {
    flex: 0 0 auto;
}

/*
 * Evita que el contenido interno obligue al flex-item a crecer.
 */
.sigi-conversation-scroll > * {
    min-width: 0;
}

/*
 * En pantallas pequeñas el layout también ocupa solo el espacio visible.
 */
@media (max-width: 800px) {
    .sigi-layout {
        height: calc(100vh - 92px);
        min-height: 0;
    }
}


/*
|--------------------------------------------------------------------------
| ENLACE FUNCIONAL A LOS ESTATUTOS
|--------------------------------------------------------------------------
*/
.sigi-document-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
    padding: 8px 13px;
    border: 1px solid rgba(99, 102, 241, .35);
    border-radius: 9px;
    background: rgba(99, 102, 241, .10);
    color: var(--cui-primary, #321fdb);
    font-weight: 600;
    text-decoration: none;
    transition: all .2s ease;
}

.sigi-document-link:hover {
    background: rgba(99, 102, 241, .18);
    text-decoration: none;
    transform: translateY(-1px);
}

[data-coreui-theme="dark"] .sigi-document-link,
.dark .sigi-document-link {
    color: #9da9ff;
    border-color: rgba(157, 169, 255, .35);
    background: rgba(99, 102, 241, .14);
}

[data-coreui-theme="dark"] .sigi-document-link:hover,
.dark .sigi-document-link:hover {
    background: rgba(99, 102, 241, .24);
}


/*
|--------------------------------------------------------------------------
| PERSONALIDAD DE SIGI — BURBUJA DEL ROBOT
|--------------------------------------------------------------------------
*/
.sigi-sidebar-hero {
    position: relative;
}

.sigi-bot-personality {
    position: absolute;
    top: 50%;
    left: calc(50% + 48px);
    transform: translateY(-50%) translateY(8px) scale(.96);
    width: 165px;
    padding: 10px 13px;
    border: 1px solid rgba(99, 102, 241, .24);
    border-radius: 14px;
    background: rgba(255, 255, 255, .94);
    color: #25314a;
    font-size: 11px;
    line-height: 1.45;
    box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition:
        opacity .28s ease,
        transform .28s ease,
        border-color .28s ease,
        background .28s ease;
    z-index: 8;
}

.sigi-bot-personality::before {
    content: "";
    position: absolute;
    left: -7px;
    top: 50%;
    width: 13px;
    height: 13px;
    transform: translateY(-50%) rotate(45deg);
    background: inherit;
    border-left: 1px solid rgba(99, 102, 241, .24);
    border-bottom: 1px solid rgba(99, 102, 241, .24);
    z-index: -1;
}

.sigi-bot-personality.is-visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(-50%) translateY(0) scale(1);
}

.sigi-bot-personality.is-listening {
    border-color: rgba(37, 99, 235, .42);
    box-shadow: 0 12px 32px rgba(37, 99, 235, .16);
}

.sigi-bot-personality.is-thinking {
    border-color: rgba(109, 93, 252, .46);
    box-shadow: 0 12px 34px rgba(109, 93, 252, .20);
}

.sigi-bot-personality.is-ready {
    border-color: rgba(16, 185, 129, .40);
    box-shadow: 0 12px 32px rgba(16, 185, 129, .14);
}

.sigi-bot-personality-dot {
    display: inline-block;
    width: 7px;
    height: 7px;
    margin-right: 7px;
    border-radius: 50%;
    background: #94a3b8;
    vertical-align: middle;
    transition: background .25s ease, box-shadow .25s ease;
}

.sigi-bot-personality.is-listening .sigi-bot-personality-dot {
    background: #2563eb;
    box-shadow: 0 0 0 5px rgba(37, 99, 235, .10);
}

.sigi-bot-personality.is-thinking .sigi-bot-personality-dot {
    background: #6d5dfc;
    box-shadow: 0 0 0 5px rgba(109, 93, 252, .10);
}

.sigi-bot-personality.is-ready .sigi-bot-personality-dot {
    background: #10b981;
    box-shadow: 0 0 0 5px rgba(16, 185, 129, .10);
}

/*
 * El robot también reacciona ligeramente cuando SIGI está atento.
 */
.sigi-bot.is-listening .sigi-bot-antenna span {
    animation: sigiAntennaListen 1s ease-in-out infinite;
}

.sigi-bot.is-thinking .sigi-bot-antenna span {
    animation: sigiAntennaThink .55s ease-in-out infinite alternate;
}

@keyframes sigiAntennaListen {
    0%, 100% {
        transform: scale(1);
        opacity: .75;
    }
    50% {
        transform: scale(1.45);
        opacity: 1;
    }
}

@keyframes sigiAntennaThink {
    from {
        transform: scale(.8);
    }
    to {
        transform: scale(1.5);
    }
}

[data-coreui-theme="dark"] .sigi-bot-personality {
    background: rgba(20, 33, 58, .96);
    color: #dbe5f7;
    border-color: #304365;
    box-shadow: 0 14px 34px rgba(0, 0, 0, .30);
}

[data-coreui-theme="dark"] .sigi-bot-personality::before {
    border-left-color: #304365;
    border-bottom-color: #304365;
}

[data-coreui-theme="dark"] .sigi-bot-personality.is-listening {
    border-color: rgba(99, 132, 220, .58);
}

[data-coreui-theme="dark"] .sigi-bot-personality.is-thinking {
    border-color: rgba(157, 143, 255, .58);
}

[data-coreui-theme="dark"] .sigi-bot-personality.is-ready {
    border-color: rgba(52, 211, 153, .48);
}

@media (max-width: 991.98px) {
    .sigi-bot-personality {
        left: 50%;
        top: auto;
        bottom: -7px;
        transform: translateX(-50%) translateY(8px) scale(.96);
        width: 175px;
    }

    .sigi-bot-personality::before {
        left: 50%;
        top: -6px;
        transform: translateX(-50%) rotate(45deg);
        border-left: 1px solid rgba(99, 102, 241, .24);
        border-bottom: 0;
        border-top: 1px solid rgba(99, 102, 241, .24);
        border-right: 0;
    }

    .sigi-bot-personality.is-visible {
        transform: translateX(-50%) translateY(0) scale(1);
    }
}


/*
|--------------------------------------------------------------------------
| CABECERA LIMPIA DE SIGI
|--------------------------------------------------------------------------
|
| El navbar duplicado se elimina. El estado de SIGI vive junto al robot
| y las acciones principales quedan junto a "IA activa".
|
*/
.sigi-bot-status {
    position: absolute;
    left: 50%;
    bottom: -2px;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    padding: 5px 9px;
    border: 1px solid rgba(129, 140, 248, .18);
    border-radius: 999px;
    background: rgba(255, 255, 255, .045);
    color: #9fb0d1;
    font-size: 9px;
    font-weight: 700;
    z-index: 4;
}

.sigi-bot-status-item {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.sigi-bot-status-divider {
    width: 1px;
    height: 12px;
    background: rgba(255,255,255,.16);
}

.sigi-bot-secure i {
    font-size: 9px;
    color: #93c5fd;
}

.sigi-main-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.sigi-clear-button {
    width: 38px;
    height: 38px;
    border: 1px solid #e3e8ef;
    background: #fff;
    border-radius: 11px;
    color: #667085;
    cursor: pointer;
    transition: .2s;
}

.sigi-clear-button:hover {
    color: #dc2626;
    border-color: #fecaca;
    background: #fff5f5;
    transform: translateY(-1px);
}

[data-coreui-theme="dark"] .sigi-main-actions .sigi-clear-button {
    background: #14213a;
    border-color: #2b3b5b;
    color: #aebbd2;
}

[data-coreui-theme="dark"] .sigi-main-actions .sigi-clear-button:hover {
    color: #fca5a5;
    border-color: #71343e;
    background: #2a171b;
}


/*
|--------------------------------------------------------------------------
| NUBE DE PERSONALIDAD — ANIMACIÓN SUAVE
|--------------------------------------------------------------------------
*/
.sigi-bot-personality {
    will-change: opacity, transform;
}

.sigi-bot-personality.is-visible {
    animation: sigiBubbleIn .28s ease-out both;
}

.sigi-bot-personality.is-thinking {
    animation: sigiBubbleThink 1.4s ease-in-out infinite;
}

@keyframes sigiBubbleIn {
    from {
        opacity: 0;
        transform: translateY(-50%) translateY(9px) scale(.94);
    }

    to {
        opacity: 1;
        transform: translateY(-50%) translateY(0) scale(1);
    }
}

@keyframes sigiBubbleThink {
    0%, 100% {
        box-shadow: 0 12px 30px rgba(15, 23, 42, .12);
    }

    50% {
        box-shadow: 0 14px 34px rgba(109, 93, 252, .22);
    }
}

@media (max-width: 991.98px) {
    .sigi-bot-personality.is-visible {
        animation: sigiBubbleInMobile .28s ease-out both;
    }

    @keyframes sigiBubbleInMobile {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(9px) scale(.94);
        }

        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0) scale(1);
        }
    }
}

/*
|--------------------------------------------------------------------------
| ANTENA DE SIGI — ÚNICA PARTE ANIMADA DEL ROBOT
|--------------------------------------------------------------------------
*/
.sigi-bot-antenna span {
    transform-origin: 50% 100%;
    transition:
        transform .25s ease,
        box-shadow .25s ease,
        opacity .25s ease;
}

.sigi-bot.is-listening .sigi-bot-antenna span {
    animation: sigiAntennaListening 1.15s ease-in-out infinite;
}

.sigi-bot.is-thinking .sigi-bot-antenna span {
    animation: sigiAntennaThinking .55s ease-in-out infinite alternate;
}

@keyframes sigiAntennaListening {
    0%, 100% {
        transform: translateX(-50%) scale(1);
        opacity: .78;
        box-shadow: 0 0 10px #67e8f9;
    }

    50% {
        transform: translateX(-50%) scale(1.65);
        opacity: 1;
        box-shadow:
            0 0 8px #67e8f9,
            0 0 22px rgba(103,232,249,.95);
    }
}

@keyframes sigiAntennaThinking {
    0% {
        transform: translateX(-50%) scale(.85);
        opacity: .65;
        box-shadow: 0 0 8px #67e8f9;
    }

    100% {
        transform: translateX(-50%) scale(1.55);
        opacity: 1;
        box-shadow:
            0 0 10px #a5b4fc,
            0 0 24px rgba(165,180,252,.95);
    }
}

@media (max-width: 800px) {
    .sigi-bot-status {
        bottom: -7px;
        font-size: 8px;
    }

    .sigi-main-actions {
        gap: 6px;
    }

    .sigi-clear-button {
        width: 34px;
        height: 34px;
    }
}


/*
|--------------------------------------------------------------------------
| MEJORA VISUAL — LEGIBILIDAD DEL CHAT
|--------------------------------------------------------------------------
*/

/* Título principal: más luminoso y contrastado */
.sigi-main-header h2 {
    color: #f8fafc;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: -.9px;
    line-height: 1.15;
    text-shadow: 0 2px 18px rgba(99, 102, 241, .18);
}

.sigi-main-header p {
    color: #aebbd2;
    font-size: 12px;
}

/* Migas de navegación */
.sigi-breadcrumb {
    color: #8291ad;
}

.sigi-breadcrumb strong {
    color: #d4dded;
}

/* Estado "Disponible" del chat */
.sigi-tool-status {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 10px;
    border-radius: 999px;
    background: rgba(22, 163, 74, .14);
    border: 1px solid rgba(34, 197, 94, .32);
    color: #86efac !important;
    font-size: 10px;
    font-weight: 800;
}

.sigi-tool-status i {
    color: #4ade80;
}

/* Estado "En línea" del robot */
.sigi-bot-status-item:first-child {
    padding: 5px 9px;
    border-radius: 999px;
    background: rgba(22, 163, 74, .18);
    border: 1px solid rgba(34, 197, 94, .38);
    color: #86efac;
}

.sigi-live-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 9px rgba(34, 197, 94, .8);
    flex: 0 0 auto;
}

/* Mensajes de SIGI: más grandes, claros y contrastados */
.sigi-message {
    max-width: 920px;
    margin-bottom: 20px;
}

.sigi-message-content {
    max-width: 82%;
}

.sigi-message-meta {
    color: #93a4bf;
    font-size: 10px;
    margin-bottom: 7px;
}

.sigi-message-meta strong {
    color: #f1f5ff;
    font-size: 11px;
}

.sigi-meta-avatar {
    width: 22px;
    height: 22px;
    border-radius: 7px;
    background: linear-gradient(145deg, #263b68, #302a69);
    color: #c4b5fd;
    box-shadow: 0 4px 12px rgba(79, 70, 229, .18);
}

.sigi-bubble {
    padding: 15px 17px;
    border-radius: 17px;
    font-size: 13px;
    line-height: 1.75;
    letter-spacing: .02px;
}

.sigi-bubble p {
    color: inherit;
    margin-bottom: 8px;
}

.sigi-bubble-assistant {
    background: linear-gradient(145deg, #172641, #14213a);
    border: 1px solid #304364;
    color: #edf3ff;
    box-shadow: 0 8px 24px rgba(0, 0, 0, .16);
}

.sigi-message-user .sigi-bubble {
    color: #ffffff;
    font-weight: 600;
}

/* Tarjetas de resultados, incluyendo estatutos */
.sigi-result-card {
    background: linear-gradient(145deg, #172641, #14213a);
    border-color: #304364;
    color: #edf3ff;
}

.sigi-result-card,
.sigi-result-card p,
.sigi-result-card div {
    color: #edf3ff;
}

.sigi-result-title {
    color: #ffffff;
    font-size: 12px;
    font-weight: 800;
}

.sigi-message-time {
    color: #8291ad;
    font-size: 9px;
}

/* Botón de estatutos */
.sigi-document-link,
.sigi-result-card a,
.sigi-result-card button {
    color: #ffffff;
}

/* Nube del robot: texto más visible */
.sigi-bot-personality {
    color: #eef4ff;
    font-size: 10px;
    line-height: 1.45;
    font-weight: 700;
    background: linear-gradient(145deg, #1c2d4c, #182641);
    border-color: #485da0;
    box-shadow: 0 12px 30px rgba(0, 0, 0, .24);
}

/* IA activa */
.sigi-main-badge {
    background: #1b1f4a;
    border-color: #4b4b91;
    color: #c4b5fd;
}

/* Cabecera del chat */
.sigi-chat-toolbar {
    min-height: 70px;
}

.sigi-chat-identity strong {
    color: #f8fafc;
    font-size: 13px;
}

.sigi-chat-identity small {
    color: #9eacc4;
    font-size: 9px;
}

/* Campo para escribir */
.sigi-composer-wrap {
    background: #0f1b31;
}

.sigi-composer textarea {
    color: #eef4ff;
    font-size: 13px;
}

.sigi-composer textarea::placeholder {
    color: #71809b;
}

/* Accesibilidad: contraste extra para enlaces dentro del resultado */
.sigi-result-card a:hover,
.sigi-result-card button:hover {
    filter: brightness(1.12);
}

/* Responsive */
@media (max-width: 800px) {
    .sigi-main-header h2 {
        font-size: 23px;
    }

    .sigi-message-content {
        max-width: 88%;
    }

    .sigi-bubble {
        font-size: 12.5px;
    }
}


/*
|--------------------------------------------------------------------------
| ESPACIADO EXTRA PARA UNA LECTURA MÁS CÓMODA
|--------------------------------------------------------------------------
*/

/* Más espacio entre cada mensaje */
.sigi-message {
    max-width: 920px;
    margin-bottom: 30px;
}

/* Más aire entre el nombre de SIGI y su mensaje */
.sigi-message-content {
    max-width: 82%;
}

.sigi-message-meta {
    margin-bottom: 10px;
}

/* Burbujas más amplias y con mejor separación de líneas */
.sigi-bubble {
    padding: 18px 20px;
    border-radius: 18px;
    font-size: 13.5px;
    line-height: 1.85;
    letter-spacing: .01px;
}

/* Separación entre párrafos de las respuestas */
.sigi-bubble p {
    margin-top: 0;
    margin-bottom: 12px;
}

.sigi-bubble p:last-child {
    margin-bottom: 0;
}

/* Más separación dentro de resultados largos */
.sigi-result-card {
    padding: 18px 20px;
    line-height: 1.8;
}

.sigi-result-card p {
    margin-bottom: 12px;
}

/* Los títulos de los resultados no quedan pegados al contenido */
.sigi-result-title {
    margin-bottom: 11px;
}

/* Más separación alrededor de las tarjetas de resultados */
.sigi-message .sigi-result-card {
    margin-top: 12px;
}

/* El clima y otros paneles internos respiran mejor */
.sigi-summary-grid {
    gap: 12px;
}

.sigi-summary-item {
    padding: 12px 14px;
}

/* Más espacio entre la consulta del usuario y la respuesta de SIGI */
.sigi-message-user {
    margin-bottom: 34px;
}

/* Separación visual del tiempo */
.sigi-message-time {
    margin-top: 8px;
}

/* En pantallas grandes aprovechamos un poco más el ancho,
   pero conservamos una columna cómoda para leer. */
@media (min-width: 1200px) {
    .sigi-message-content {
        max-width: 78%;
    }

    .sigi-bubble {
        font-size: 14px;
        line-height: 1.9;
    }
}

/* En pantallas pequeñas evitamos que el texto se vea comprimido. */
@media (max-width: 800px) {
    .sigi-message {
        margin-bottom: 24px;
    }

    .sigi-message-content {
        max-width: 90%;
    }

    .sigi-bubble {
        padding: 15px 16px;
        font-size: 13px;
        line-height: 1.75;
    }
}


/*
|--------------------------------------------------------------------------
| SIDEBAR SIGI MÁS ANCHO
|--------------------------------------------------------------------------
|
| Objetivo:
| - Dar más espacio al robot y a su nube.
| - Permitir que el texto de SIGI se vea completo.
| - Mantener el chat y sus funciones intactos.
|
*/

.sigi-layout {
    grid-template-columns: 340px minmax(0, 1fr);
}

/* La columna izquierda gana espacio sin cambiar su contenido */
.sigi-sidebar {
    width: 340px;
    min-width: 340px;
}

/* El robot dispone de más espacio horizontal */
.sigi-bot-area {
    padding-left: 10px;
    padding-right: 10px;
}

/* La nube puede crecer un poco sin ser cortada */
.sigi-bot-personality {
    width: 205px;
    max-width: 205px;
}

/* El texto de la nube queda en una sola zona cómoda de lectura */
.sigi-bot-personality-text {
    max-width: 165px;
}

/* Más espacio para el saludo y descripción */
.sigi-sidebar h1,
.sigi-sidebar h2,
.sigi-sidebar h3 {
    max-width: 300px;
}

/* Evita que las opciones del sidebar se compriman */
.sigi-sidebar .sigi-capability,
.sigi-sidebar .sigi-capability-card,
.sigi-sidebar .sigi-side-card {
    min-width: 0;
}

/* En pantallas medianas reducimos un poco el ancho para conservar
   espacio suficiente para la conversación */
@media (max-width: 1200px) and (min-width: 992px) {
    .sigi-layout {
        grid-template-columns: 315px minmax(0, 1fr);
    }

    .sigi-sidebar {
        width: 315px;
        min-width: 315px;
    }

    .sigi-bot-personality {
        width: 185px;
        max-width: 185px;
    }
}

/* En móvil vuelve a ocupar todo el ancho disponible */
@media (max-width: 991.98px) {
    .sigi-layout {
        grid-template-columns: 1fr;
    }

    .sigi-sidebar {
        width: 100%;
        min-width: 0;
    }

    .sigi-bot-personality {
        width: 175px;
        max-width: 175px;
    }
}


/*
|--------------------------------------------------------------------------
| MENSAJES DE SIGI AL LADO IZQUIERDO
|--------------------------------------------------------------------------
|
| Las respuestas de SIGI quedan alineadas a la izquierda.
| Los mensajes del usuario continúan a la derecha.
|
*/

/* Contenedor general de mensajes del asistente */
.sigi-message-assistant {
    width: 100%;
    display: flex;
    justify-content: flex-start;
}

/* Contenido de SIGI alineado a la izquierda */
.sigi-message-assistant .sigi-message-content {
    margin-left: 0;
    margin-right: auto;
    max-width: 82%;
}

/* La burbuja de SIGI empieza siempre desde la izquierda */
.sigi-message-assistant .sigi-bubble,
.sigi-message-assistant .sigi-result-card {
    margin-left: 0;
    margin-right: auto;
    text-align: left;
}

/* Nombre SIGI */
.sigi-message-assistant .sigi-message-meta {
    text-align: left;
}

/* Hora de SIGI */
.sigi-message-assistant .sigi-message-time {
    text-align: left;
}

/* El usuario conserva sus mensajes a la derecha */
.sigi-message-user {
    width: 100%;
    display: flex;
    justify-content: flex-end;
}

.sigi-message-user .sigi-message-content {
    margin-left: auto;
    margin-right: 0;
    max-width: 72%;
}

/* En pantallas grandes dejamos una anchura cómoda para leer */
@media (min-width: 1200px) {
    .sigi-message-assistant .sigi-message-content {
        max-width: 76%;
    }

    .sigi-message-user .sigi-message-content {
        max-width: 62%;
    }
}

/* En pantallas pequeñas */
@media (max-width: 800px) {
    .sigi-message-assistant .sigi-message-content,
    .sigi-message-user .sigi-message-content {
        max-width: 88%;
    }
}


/*
|--------------------------------------------------------------------------
| CORRECCIÓN DEFINITIVA — SIGI A LA IZQUIERDA
|--------------------------------------------------------------------------
|
| El problema anterior era que .sigi-message tenía:
|
|     max-width: 860px;
|     margin: 0 auto;
|
| Por eso el bloque completo se centraba antes de aplicar
| justify-content:flex-start.
|
| Ahora SIGI ocupa todo el ancho disponible y su contenido
| comienza realmente desde el borde izquierdo del historial.
| El usuario continúa a la derecha.
|
*/

/* SIGI: bloque completo pegado al lado izquierdo */
.sigi-conversation-scroll .sigi-message.sigi-message-assistant {
    width: 100% !important;
    max-width: none !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    justify-content: flex-start !important;
    align-self: flex-start !important;
}

/* Contenido de SIGI: siempre comienza desde la izquierda */
.sigi-conversation-scroll .sigi-message.sigi-message-assistant .sigi-message-content {
    width: auto !important;
    max-width: 78% !important;
    margin-left: 0 !important;
    margin-right: auto !important;
    align-items: flex-start !important;
}

/* Meta de SIGI */
.sigi-conversation-scroll .sigi-message.sigi-message-assistant .sigi-message-meta {
    margin-left: 0 !important;
    margin-right: 0 !important;
    justify-content: flex-start !important;
    text-align: left !important;
}

/* Burbuja de SIGI */
.sigi-conversation-scroll .sigi-message.sigi-message-assistant .sigi-bubble {
    margin-left: 0 !important;
    margin-right: auto !important;
    text-align: left !important;
}

/* Tarjetas de resultados de SIGI */
.sigi-conversation-scroll .sigi-message.sigi-message-assistant .sigi-result-card {
    margin-left: 0 !important;
    margin-right: auto !important;
    text-align: left !important;
}

/* Hora de SIGI */
.sigi-conversation-scroll .sigi-message.sigi-message-assistant .sigi-message-time {
    margin-left: 0 !important;
    margin-right: auto !important;
    text-align: left !important;
}

/* Usuario: sigue completamente a la derecha */
.sigi-conversation-scroll .sigi-message.sigi-message-user {
    width: 100% !important;
    max-width: none !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    justify-content: flex-end !important;
    align-self: flex-end !important;
}

.sigi-conversation-scroll .sigi-message.sigi-message-user .sigi-message-content {
    max-width: 62% !important;
    margin-left: auto !important;
    margin-right: 0 !important;
    align-items: flex-end !important;
}

.sigi-conversation-scroll .sigi-message.sigi-message-user .sigi-message-meta {
    justify-content: flex-end !important;
    text-align: right !important;
}

.sigi-conversation-scroll .sigi-message.sigi-message-user .sigi-bubble {
    margin-left: auto !important;
    margin-right: 0 !important;
    text-align: left !important;
}

.sigi-conversation-scroll .sigi-message.sigi-message-user .sigi-message-time {
    margin-left: auto !important;
    margin-right: 0 !important;
    text-align: right !important;
}

/* Pantallas grandes: dejamos un ancho cómodo para las respuestas */
@media (min-width: 1200px) {
    .sigi-conversation-scroll .sigi-message.sigi-message-assistant .sigi-message-content {
        max-width: 76% !important;
    }

    .sigi-conversation-scroll .sigi-message.sigi-message-user .sigi-message-content {
        max-width: 58% !important;
    }
}

/* Móviles */
@media (max-width: 800px) {
    .sigi-conversation-scroll .sigi-message.sigi-message-assistant .sigi-message-content,
    .sigi-conversation-scroll .sigi-message.sigi-message-user .sigi-message-content {
        max-width: 88% !important;
    }
}


/*
|--------------------------------------------------------------------------
| CORRECCIÓN — NUBE DE SIGI A LA IZQUIERDA DEL ROBOT
|--------------------------------------------------------------------------
|
| IMPORTANTE:
| Esta es la nube de personalidad que aparece junto al robot.
| NO es la burbuja de los mensajes del chat.
|
| Antes estaba a la derecha:
|     left: calc(50% + 48px);
|
| Ahora queda a la izquierda:
|     right: calc(50% + 48px);
|
*/

/* La nube completa se coloca a la izquierda del robot */
.sigi-sidebar-hero .sigi-bot-personality {
    left: auto !important;
    right: calc(50% + 48px) !important;

    width: 205px !important;
    max-width: 205px !important;

    transform:
        translateY(-50%)
        translateY(8px)
        scale(.96);
}

/* Cuando aparece */
.sigi-sidebar-hero .sigi-bot-personality.is-visible {
    transform:
        translateY(-50%)
        translateY(0)
        scale(1);
}

/* La pequeña punta ahora apunta hacia el robot por la derecha */
.sigi-sidebar-hero .sigi-bot-personality::before {
    left: auto !important;
    right: -7px !important;

    border-left: 0 !important;
    border-bottom: 1px solid rgba(99, 102, 241, .24);
    border-right: 1px solid rgba(99, 102, 241, .24);

    transform:
        translateY(-50%)
        rotate(45deg);
}

/* En pantallas más pequeñas acercamos la nube al robot */
@media (max-width: 1200px) {
    .sigi-sidebar-hero .sigi-bot-personality {
        right: calc(50% + 38px) !important;
        width: 185px !important;
        max-width: 185px !important;
    }
}

@media (max-width: 991.98px) {
    .sigi-sidebar-hero .sigi-bot-personality {
        right: calc(50% + 32px) !important;
        width: 175px !important;
        max-width: 175px !important;
    }
}


/*
|--------------------------------------------------------------------------
| CORRECCIÓN REAL — NUBE A LA IZQUIERDA Y ROBOT A LA DERECHA
|--------------------------------------------------------------------------
|
| El problema era geométrico:
| el robot estaba centrado en una columna estrecha y la nube,
| al ponerse a su izquierda, salía fuera del sidebar.
|
| Ahora:
|   [ NUBE ]   [ ROBOT ]
|
| La nube queda completamente dentro del sidebar.
*/

/* El área superior permite posicionar ambos elementos */
.sigi-sidebar-hero {
    position: relative !important;
    overflow: visible !important;
}

/* Movemos el robot hacia la derecha del área del robot */
.sigi-sidebar-hero .sigi-bot {
    position: absolute !important;
    left: auto !important;
    right: 18px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    margin: 0 !important;
    z-index: 2 !important;
}

/* La nube queda completamente a la izquierda del robot */
.sigi-sidebar-hero .sigi-bot-personality {
    left: 8px !important;
    right: auto !important;

    top: 50% !important;

    width: 145px !important;
    max-width: 145px !important;

    padding: 9px 11px !important;

    transform:
        translateY(-50%)
        translateY(8px)
        scale(.96) !important;
}

/* Estado visible */
.sigi-sidebar-hero .sigi-bot-personality.is-visible {
    transform:
        translateY(-50%)
        translateY(0)
        scale(1) !important;
}

/* La punta apunta desde la derecha de la nube hacia el robot */
.sigi-sidebar-hero .sigi-bot-personality::before {
    left: auto !important;
    right: -7px !important;

    border-left: 0 !important;
    border-bottom: 1px solid rgba(99, 102, 241, .24);
    border-right: 1px solid rgba(99, 102, 241, .24);

    transform:
        translateY(-50%)
        rotate(45deg) !important;
}

/* El texto de la nube se adapta a su nuevo ancho */
.sigi-sidebar-hero .sigi-bot-personality-text,
.sigi-sidebar-hero #sigiBotPersonalityText {
    display: inline !important;
    max-width: 118px !important;
    overflow-wrap: anywhere;
}

/* Pantallas medianas */
@media (max-width: 1200px) and (min-width: 801px) {
    .sigi-sidebar-hero .sigi-bot {
        right: 8px !important;
        transform: translateY(-50%) scale(.88) !important;
    }

    .sigi-sidebar-hero .sigi-bot-personality {
        left: 4px !important;
        width: 130px !important;
        max-width: 130px !important;
    }
}

/* Móvil: el sidebar se oculta según el diseño actual */
@media (max-width: 800px) {
    .sigi-sidebar-hero .sigi-bot {
        right: 18px !important;
    }

    .sigi-sidebar-hero .sigi-bot-personality {
        left: 8px !important;
        width: 135px !important;
        max-width: 135px !important;
    }
}


/*
|--------------------------------------------------------------------------
| AJUSTE FINAL DEL ROBOT Y NUBE DE PERSONALIDAD
|--------------------------------------------------------------------------
|
| Distribución solicitada:
|
|   [ ROBOT ]          [ NUBE ]
|
| Además:
| - se separa la base del robot de "En línea / Sesión segura";
| - se mantiene todo dentro del sidebar;
| - no se modifica el chat ni JavaScript.
|
*/

/* Área superior más alta para que el robot y el estado respiren */
.sigi-sidebar-hero {
    position: relative !important;
    height: 190px !important;
    overflow: visible !important;
    display: block !important;
}

/* Robot a la IZQUIERDA */
.sigi-sidebar-hero .sigi-bot {
    position: absolute !important;
    left: 18px !important;
    right: auto !important;
    top: 12px !important;

    width: 104px !important;
    height: 130px !important;

    margin: 0 !important;
    transform: none !important;
    z-index: 3 !important;
}

/* Nube a la DERECHA del robot */
.sigi-sidebar-hero .sigi-bot-personality {
    position: absolute !important;

    left: auto !important;
    right: 5px !important;
    top: 42px !important;

    width: 145px !important;
    max-width: 145px !important;

    padding: 10px 11px !important;

    transform:
        translateY(8px)
        scale(.96) !important;
}

/* Nube visible */
.sigi-sidebar-hero .sigi-bot-personality.is-visible {
    transform:
        translateY(0)
        scale(1) !important;
}

/* Punta de la nube apuntando hacia el robot */
.sigi-sidebar-hero .sigi-bot-personality::before {
    left: -7px !important;
    right: auto !important;

    top: 50% !important;

    width: 13px !important;
    height: 13px !important;

    border-left: 1px solid #304365 !important;
    border-bottom: 1px solid #304365 !important;
    border-right: 0 !important;

    transform:
        translateY(-50%)
        rotate(45deg) !important;
}

/* Texto de la nube */
.sigi-sidebar-hero #sigiBotPersonalityText {
    display: inline !important;
    max-width: 112px !important;
    line-height: 1.45 !important;
    overflow-wrap: anywhere;
}

/* Estado debajo del robot, con separación real */
.sigi-sidebar-hero .sigi-bot-status {
    position: absolute !important;

    left: 50% !important;
    right: auto !important;
    bottom: 0 !important;

    transform: translateX(-50%) !important;

    z-index: 5 !important;

    margin: 0 !important;
}

/* Un poco más de separación visual entre el robot y el estado */
.sigi-sidebar-hero .sigi-bot-status {
    padding-top: 6px !important;
    padding-bottom: 6px !important;
}

/* Evitamos que el siguiente bloque quede pegado al estado */
.sigi-sidebar-welcome {
    padding-top: 4px !important;
}

/* En pantallas medianas */
@media (max-width: 1200px) and (min-width: 801px) {
    .sigi-sidebar-hero {
        height: 180px !important;
    }

    .sigi-sidebar-hero .sigi-bot {
        left: 8px !important;
        transform: scale(.9) !important;
        transform-origin: top left !important;
    }

    .sigi-sidebar-hero .sigi-bot-personality {
        right: 2px !important;
        width: 130px !important;
        max-width: 130px !important;
    }
}

/* Móvil */
@media (max-width: 800px) {
    .sigi-sidebar-hero {
        height: 175px !important;
    }

    .sigi-sidebar-hero .sigi-bot {
        left: 12px !important;
        transform: scale(.9) !important;
        transform-origin: top left !important;
    }

    .sigi-sidebar-hero .sigi-bot-personality {
        right: 2px !important;
        width: 135px !important;
        max-width: 135px !important;
    }
}

</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const textarea = document.getElementById('sigiConsulta');
    const conversation = document.getElementById('sigiConversation');
    const btn = document.getElementById('btnSigiConsultar');
    const suggestions = document.getElementById('sigiSuggestions');

    const mensajesEl = document.getElementById('sigiMensajes');
    const consultasEl = document.getElementById('sigiConsultas');
    const tiempoEl = document.getElementById('sigiTiempo');

    const consultaUrl = @json(route('consulta.inteligente'));
    const estatutosUrl = @json(asset('documentos/estatutos-grupo-21.pdf'));
    const csrfToken = @json(csrf_token());

    let mensajes = 1;
    let consultas = 0;
    let enviando = false;

    const inicio = Date.now();

    if (mensajesEl) {
        mensajesEl.textContent = mensajes;
    }

    if (consultasEl) {
        consultasEl.textContent = consultas;
    }


    function actualizarTiempo() {

        if (!tiempoEl) {
            return;
        }

        const segundos = Math.floor(
            (Date.now() - inicio) / 1000
        );

        const horas = String(
            Math.floor(segundos / 3600)
        ).padStart(2, '0');

        const minutos = String(
            Math.floor((segundos % 3600) / 60)
        ).padStart(2, '0');

        const resto = String(
            segundos % 60
        ).padStart(2, '0');

        tiempoEl.textContent =
            `${horas}:${minutos}:${resto}`;
    }

    setInterval(actualizarTiempo, 1000);
    actualizarTiempo();

    function ajustarAlturaTextarea() {
        if (!textarea) return;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 150) + 'px';
    }

    if (textarea) {
        textarea.addEventListener('input', ajustarAlturaTextarea);
        ajustarAlturaTextarea();
    }



    /*
     * ================================================================
     * PERSONALIDAD DE SIGI
     * ================================================================
     *
     * El robot reacciona al estado real de la conversación:
     * - el usuario escribe -> "Te estoy escuchando..."
     * - SIGI consulta      -> "Estoy pensando..."
     * - respuesta lista    -> "Listo, encontré la información."
     */
    const botPersonality =
        document.getElementById('sigiBotPersonality');

    const botPersonalityText =
        document.getElementById('sigiBotPersonalityText');

    const botElement =
        document.querySelector('.sigi-bot');

    let botReadyTimer = null;

    function cambiarEstadoRobot(
        texto,
        estado = 'listening',
        visible = true
    ) {
        if (!botPersonality || !botPersonalityText) {
            return;
        }

        botPersonalityText.textContent = texto;

        botPersonality.classList.remove(
            'is-listening',
            'is-thinking',
            'is-ready',
            'is-visible'
        );

        if (estado) {
            botPersonality.classList.add(
                `is-${estado}`
            );
        }

        if (botElement) {
            botElement.classList.remove(
                'is-listening',
                'is-thinking'
            );

            if (
                estado === 'listening' ||
                estado === 'thinking'
            ) {
                botElement.classList.add(
                    `is-${estado}`
                );
            }
        }

        if (visible) {
            botPersonality.classList.add(
                'is-visible'
            );
        }
    }

    /*
     * ================================================================
     * MENSAJE DE PERSONALIDAD SEGÚN LA CONSULTA
     * ================================================================
     *
     * SIGI no muestra siempre "Estoy pensando...".
     * El texto de la nube intenta explicar qué está haciendo.
     */
    function obtenerMensajeProcesando(consulta) {
        const texto = String(consulta || '').toLowerCase();

        if (
            texto.includes('estatuto') ||
            texto.includes('estatutos') ||
            texto.includes('artículo') ||
            texto.includes('articulo')
        ) {
            return 'Estoy revisando los estatutos... 📄';
        }

        if (
            texto.includes('clima') ||
            texto.includes('tiempo') ||
            texto.includes('temperatura') ||
            texto.includes('llueve') ||
            texto.includes('llover') ||
            texto.includes('lluvia')
        ) {
            return 'Estoy consultando el clima... 🌤️';
        }

        if (
            texto.includes('saldo') ||
            texto.includes('ingreso') ||
            texto.includes('ingresos') ||
            texto.includes('egreso') ||
            texto.includes('egresos') ||
            texto.includes('gasto') ||
            texto.includes('gastos') ||
            texto.includes('movimiento') ||
            texto.includes('movimientos') ||
            texto.includes('dinero') ||
            texto.includes('recaud')
        ) {
            return 'Estoy consultando la información financiera... 💰';
        }

        if (
            texto.includes('buscar') ||
            texto.includes('último') ||
            texto.includes('ultimos') ||
            texto.includes('últimos') ||
            texto.includes('información') ||
            texto.includes('informacion')
        ) {
            return 'Estoy buscando la información... 🔎';
        }

        return 'Estoy pensando... 🧠';
    }


    function mostrarEstadoRobotListo() {
        cambiarEstadoRobot(
            'Listo, aquí estoy. 😊',
            'ready',
            true
        );

        clearTimeout(botReadyTimer);

        botReadyTimer = setTimeout(
            function () {
                if (
                    botPersonality &&
                    !enviando &&
                    (!textarea || !textarea.value.trim())
                ) {
                    botPersonality.classList.remove(
                        'is-visible',
                        'is-ready'
                    );

                    if (botElement) {
                        botElement.classList.remove(
                            'is-ready',
                            'is-listening',
                            'is-thinking'
                        );
                    }
                }
            },
            2600
        );
    }

    if (textarea) {
        textarea.addEventListener(
            'input',
            function () {
                clearTimeout(botReadyTimer);

                const escrito =
                    textarea.value.trim();

                if (!escrito) {
                    if (!enviando) {
                        cambiarEstadoRobot(
                            'Estoy aquí. Te escucho...',
                            'listening',
                            false
                        );

                        if (botPersonality) {
                            botPersonality.classList.remove(
                                'is-visible'
                            );
                        }

                        if (botElement) {
                            botElement.classList.remove(
                                'is-listening',
                                'is-thinking'
                            );
                        }
                    }

                    return;
                }

                if (!enviando) {
                    cambiarEstadoRobot(
                        'Te estoy escuchando... 👀',
                        'listening',
                        true
                    );
                }
            }
        );
    }

        function scrollConversation() {

        if (!conversation) {
            return;
        }

        conversation.scrollTo({
            top: conversation.scrollHeight,
            behavior: 'smooth'
        });
    }


    function escapeHtml(text) {

        const div = document.createElement('div');

        div.textContent =
            text == null ? '' : String(text);

        return div.innerHTML;
    }


    function formatearTexto(texto) {

        const limpio =
            texto == null ? '' : String(texto);

        return escapeHtml(limpio)
            .replace(
                /📄 Ver estatutos completos: \/documentos\/estatutos-grupo-21\.pdf/g,
                '<a class="sigi-document-link" href="/documentos/estatutos-grupo-21.pdf" target="_blank" rel="noopener noreferrer">📄 Ver estatutos completos</a>'
            )
            .replace(/\n\n+/g, '</p><p>')
            .replace(/\n/g, '<br>');
    }


    function agregarMensajeUsuario(texto) {

        const mensaje =
            document.createElement('div');

        mensaje.className =
            'sigi-message sigi-message-user';

        mensaje.innerHTML = `
            <div class="sigi-message-content">

                <div class="sigi-bubble">

                    ${formatearTexto(texto)}

                </div>

                <div class="sigi-message-time"
                    style="text-align:right;">

                    Ahora

                </div>

            </div>
        `;

        conversation.appendChild(mensaje);

        mensajes++;

        if (mensajesEl) {
            mensajesEl.textContent = mensajes;
        }

        scrollConversation();
    }


    function mostrarEscribiendo() {

        const mensaje =
            document.createElement('div');

        mensaje.id =
            'sigiTypingMessage';

        mensaje.className =
            'sigi-message sigi-message-assistant';

        mensaje.innerHTML = `
            <div class="sigi-avatar">

                <div class="sigi-avatar-face">
                    <span></span>
                    <span></span>
                </div>

            </div>

            <div class="sigi-message-content">

                <div class="sigi-message-author">
                    SIGI
                </div>

                <div class="sigi-typing"
                    aria-label="SIGI está escribiendo">

                    <span></span>
                    <span></span>
                    <span></span>

                </div>

            </div>
        `;

        conversation.appendChild(mensaje);

        scrollConversation();
    }


    function quitarEscribiendo() {

        const typing =
            document.getElementById(
                'sigiTypingMessage'
            );

        if (typing) {
            typing.remove();
        }
    }


    function obtenerIconoTipo(tipo) {

        const iconos = {
            texto: '💬',
            greeting: '👋',
            conversation: '💬',
            movimiento: '💰',
            movimientos: '💰',
            resumen: '📊',
            periodo: '📅',
            asamblea: '📢',
            clima: '🌤️',
            joke: '😂',
            memory: '🧠'
        };

        return iconos[tipo] || '🤖';
    }


    /*
     * ================================================================
     * SEGURIDAD DE PRESENTACIÓN
     * ================================================================
     *
     * SIGI nunca debe mostrar objetos completos devueltos por Laravel.
     * Tampoco mostramos IDs internos, correos, timestamps, observaciones,
     * coordenadas, tokens u otros metadatos.
     */

    const CAMPOS_SENSIBLES = new Set([
        'id',
        'user_id',
        'usuario_id',
        'categoria_id',
        'created_at',
        'updated_at',
        'deleted_at',
        'email',
        'password',
        'remember_token',
        'token',
        'api_token',
        'observaciones',
        'observation',
        'latitud',
        'longitud',
        'latitude',
        'longitude'
    ]);


    const ETIQUETAS_CAMPOS = {
        anio: 'Año',
        mes: 'Mes',
        nombre: 'Periodo',
        saldo_inicial: 'Saldo inicial',
        total_ingresos: 'Ingresos',
        total_egresos: 'Egresos',
        saldo_final: 'Saldo final',
        estado: 'Estado',
        fecha_cierre: 'Fecha de cierre',

        fecha: 'Fecha',
        tipo: 'Tipo',
        concepto: 'Concepto',
        monto: 'Monto',
        categoria: 'Categoría',

        name: 'Nombre',
        roles: 'Roles'
    };


    function esCampoSeguro(campo) {
        return !CAMPOS_SENSIBLES.has(
            String(campo).toLowerCase()
        );
    }


    function etiquetaCampo(campo) {
        return ETIQUETAS_CAMPOS[campo]
            || String(campo)
                .replaceAll('_', ' ')
                .replace(/\b\w/g, letra => letra.toUpperCase());
    }


    function formatearFecha(valor) {

        if (valor === null || valor === undefined || valor === '') {
            return '—';
        }

        const texto = String(valor).trim();

        /*
         * SIGI recibe algunas fechas desde Laravel/Carbon con formato
         * ISO, por ejemplo:
         * 2025-07-29T00:00:00.000000Z
         *
         * Las convertimos a una fecha corta y fácil de leer.
         */
        const fecha = new Date(texto);

        if (!Number.isNaN(fecha.getTime())) {

            return new Intl.DateTimeFormat(
                'es-PE',
                {
                    timeZone: 'UTC',
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                }
            ).format(fecha);
        }

        /*
         * Si el valor no es una fecha ISO válida, lo dejamos como
         * texto para no alterar información legítima.
         */
        return texto;
    }


    function formatearValorCampo(campo, valor) {

        if (valor === null || valor === undefined || valor === '') {
            return '—';
        }

        if (
            campo === 'fecha'
            || campo === 'fecha_cierre'
            || campo === 'created_at'
            || campo === 'updated_at'
        ) {
            return formatearFecha(valor);
        }

        if (
            [
                'saldo_inicial',
                'total_ingresos',
                'total_egresos',
                'saldo_final',
                'monto'
            ].includes(campo)
            && !Number.isNaN(Number(valor))
        ) {
            return 'S/ ' + Number(valor).toLocaleString(
                'es-PE',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );
        }

        if (campo === 'estado') {
            return String(valor);
        }

        if (campo === 'roles') {
            if (Array.isArray(valor)) {
                return valor
                    .map(rol => String(rol))
                    .filter(Boolean)
                    .join(', ');
            }

            return String(valor);
        }

        return String(valor);
    }


    function detectarTipoFila(fila) {

        const claves =
            Object.keys(fila || {});


        if (
            claves.includes('saldo_final') ||
            claves.includes('total_ingresos') ||
            claves.includes('total_egresos') ||
            claves.includes('saldo_inicial')
        ) {
            return 'periodo';
        }


        if (
            claves.includes('monto') ||
            claves.includes('concepto') ||
            claves.includes('tipo')
        ) {
            return 'movimiento';
        }


        if (
            claves.includes('email') ||
            claves.includes('nombre') ||
            (
                claves.includes('name') &&
                claves.includes('email')
            )
        ) {
            return 'usuario';
        }


        if (
            claves.includes('guard_name') ||
            (
                claves.includes('name') &&
                !claves.includes('email') &&
                !claves.includes('nombre') &&
                !claves.includes('monto') &&
                !claves.includes('concepto') &&
                !claves.includes('tipo')
            )
        ) {
            return 'rol';
        }


        return 'desconocido';
    }


    function normalizarFilaSegura(fila) {

        if (
            !fila ||
            typeof fila !== 'object' ||
            Array.isArray(fila)
        ) {
            return null;
        }


        const tipoFila =
            detectarTipoFila(fila);


        let camposPermitidos = [];


        if (tipoFila === 'periodo') {

            camposPermitidos = [
                'nombre',
                'anio',
                'mes',
                'saldo_inicial',
                'total_ingresos',
                'total_egresos',
                'saldo_final',
                'estado',
                'fecha_cierre'
            ];

        } else if (tipoFila === 'movimiento') {

            camposPermitidos = [
                'fecha',
                'tipo',
                'concepto',
                'categoria',
                'monto'
            ];

        } else if (tipoFila === 'usuario') {

            camposPermitidos = [
                'nombre',
                'name',
                'email',
                'estado',
                'roles'
            ];

        } else if (tipoFila === 'rol') {

            camposPermitidos = [
                'name'
            ];

        } else {

            /*
             * No conocemos la estructura.
             * Por seguridad NO mostramos el objeto.
             */
            return null;
        }


        const salida = {};


        camposPermitidos.forEach(campo => {

            if (
                Object.prototype.hasOwnProperty.call(
                    fila,
                    campo
                )
                && esCampoSeguro(campo)
            ) {
                salida[campo] =
                    formatearValorCampo(
                        campo,
                        fila[campo]
                    );
            }

        });


        return Object.keys(salida).length
            ? salida
            : null;
    }


    function renderizarTabla(resultado) {

        if (
            !Array.isArray(resultado) ||
            resultado.length === 0
        ) {
            return '';
        }


        const filasSeguras =
            resultado
                .map(normalizarFilaSegura)
                .filter(Boolean);


        if (!filasSeguras.length) {

            return `
                <div class="sigi-safe-notice">
                    <i class="cil-lock-locked"></i>
                    <span>
                        La información encontrada no contiene
                        datos que SIGI pueda mostrar en esta conversación.
                    </span>
                </div>
            `;
        }


        const esTablaUsuarios = filasSeguras.every(
            fila => detectarTipoFila(fila) === 'usuario'
        );

        const columnas = esTablaUsuarios
            ? ['nombre', 'estado', 'roles'].filter(
                columna => filasSeguras.some(
                    fila => Object.prototype.hasOwnProperty.call(fila, columna)
                )
            )
            : [
                ...new Set(
                    filasSeguras.flatMap(
                        fila => Object.keys(fila)
                    )
                )
            ];


        const encabezados =
            columnas.map(
                columna => `
                    <th>
                        ${escapeHtml(
                            esTablaUsuarios && columna === 'nombre'
                                ? 'Nombre'
                                : etiquetaCampo(columna)
                        )}
                    </th>
                `
            ).join('');


        const cuerpo =
            filasSeguras
                .slice(0, 50)
                .map(
                    fila => `
                        <tr>
                            ${
                                columnas.map(
                                    columna => `
                                        <td>
                                            ${escapeHtml(
                                                columna === 'nombre'
                                                    ? (fila.nombre ?? fila.name ?? '—')
                                                    : (fila[columna] ?? '—')
                                            )}
                                        </td>
                                    `
                                ).join('')
                            }
                        </tr>
                    `
                ).join('');


        return `
            <div class="sigi-result-table-wrap">

                <table class="sigi-result-table">

                    <thead>
                        <tr>
                            ${encabezados}
                        </tr>
                    </thead>

                    <tbody>
                        ${cuerpo}
                    </tbody>

                </table>

            </div>
        `;
    }


    function renderizarClimaSeguro(resultado) {

        if (
            !resultado ||
            typeof resultado !== 'object'
        ) {
            return '';
        }


        const ubicacion =
            resultado.ubicacion || {};


        const nombre =
            ubicacion.nombre
            || ubicacion.city
            || 'Ubicación consultada';


        const pais =
            ubicacion.pais
            || ubicacion.country
            || '';


        const temperatura =
            resultado.temperatura
            ?? resultado.temperature
            ?? null;


        const sensacion =
            resultado.sensacion
            ?? resultado.feels_like
            ?? null;


        const humedad =
            resultado.humedad
            ?? resultado.humidity
            ?? null;


        const viento =
            resultado.viento
            ?? resultado.wind
            ?? null;


        const descripcion =
            resultado.descripcion
            || resultado.description
            || 'Condiciones actuales';


        return `
            <div class="sigi-weather-card">

                <div class="sigi-weather-head">

                    <div class="sigi-weather-icon">
                        <i class="cil-sun"></i>
                    </div>

                    <div>
                        <strong>${escapeHtml(nombre)}</strong>
                        <small>${escapeHtml(pais)}</small>
                    </div>

                </div>


                <div class="sigi-weather-main">

                    <div class="sigi-weather-temperature">

                        ${
                            temperatura !== null
                                ? escapeHtml(String(temperatura)) + ' °C'
                                : '—'
                        }

                    </div>

                    <div class="sigi-weather-description">

                        ${escapeHtml(descripcion)}

                    </div>

                </div>


                <div class="sigi-weather-grid">

                    <div>
                        <span>Sensación</span>
                        <strong>
                            ${
                                sensacion !== null
                                    ? escapeHtml(String(sensacion)) + ' °C'
                                    : '—'
                            }
                        </strong>
                    </div>

                    <div>
                        <span>Humedad</span>
                        <strong>
                            ${
                                humedad !== null
                                    ? escapeHtml(String(humedad)) + '%'
                                    : '—'
                            }
                        </strong>
                    </div>

                    <div>
                        <span>Viento</span>
                        <strong>
                            ${
                                viento !== null
                                    ? escapeHtml(String(viento)) + ' km/h'
                                    : '—'
                            }
                        </strong>
                    </div>

                </div>

            </div>
        `;
    }


    function renderizarObjetoSeguro(resultado, tipo) {

        if (
            !resultado ||
            typeof resultado !== 'object' ||
            Array.isArray(resultado)
        ) {
            return '';
        }


        if (
            tipo === 'clima' ||
            Object.prototype.hasOwnProperty.call(
                resultado,
                'temperatura'
            ) ||
            Object.prototype.hasOwnProperty.call(
                resultado,
                'humedad'
            )
        ) {
            return renderizarClimaSeguro(resultado);
        }


        /*
         * Las respuestas de tipo "texto" ya vienen preparadas por el
         * servicio correspondiente y se muestran mediante "mensaje".
         *
         * En particular, las consultas de estatutos pueden devolver
         * el artículo completo en el mensaje y un objeto auxiliar en
         * "resultado". Ese objeto no debe pasar por el filtro de filas,
         * porque produciría el aviso de "información adicional".
         */
        if (tipo === 'texto') {
            return '';
        }


        const fila =
            normalizarFilaSegura(resultado);


        if (!fila) {

            return `
                <div class="sigi-safe-notice">
                    <i class="cil-lock-locked"></i>
                    <span>
                        SIGI recibió información adicional,
                        pero no mostrará datos internos o sensibles.
                    </span>
                </div>
            `;
        }


        return `
            <div class="sigi-summary-grid">

                ${
                    Object.entries(fila)
                        .map(
                            ([campo, valor]) => `
                                <div class="sigi-summary-item">

                                    <span>
                                        ${escapeHtml(
                                            etiquetaCampo(campo)
                                        )}
                                    </span>

                                    <strong>
                                        ${escapeHtml(
                                            String(valor)
                                        )}
                                    </strong>

                                </div>
                            `
                        ).join('')
                }

            </div>
        `;
    }


    function renderizarResultado(resultado, tipo) {

        if (resultado == null) {
            return '';
        }


        if (Array.isArray(resultado)) {

            return renderizarTabla(resultado);
        }


        if (typeof resultado === 'object') {

            return renderizarObjetoSeguro(
                resultado,
                tipo
            );
        }


        if (
            typeof resultado === 'string' &&
            resultado.trim() !== ''
        ) {

            return `
                <div class="mt-2">
                    ${formatearTexto(resultado)}
                </div>
            `;
        }


        if (
            typeof resultado === 'number' ||
            typeof resultado === 'boolean'
        ) {

            return `
                <div class="sigi-simple-result">
                    ${escapeHtml(String(resultado))}
                </div>
            `;
        }


        return '';
    }


    function agregarRespuesta(
        mensaje,
        resultado = null,
        tipo = null,
        error = false
    ) {

        const wrapper =
            document.createElement('div');

        wrapper.className =
            'sigi-message sigi-message-assistant';

        const titulo =
            error
                ? 'SIGI · Atención'
                : 'SIGI';

        const contenido =
            error
                ? `
                    <div class="sigi-result-card sigi-error">
                        <div class="sigi-result-title">
                            ⚠️ ${titulo}
                        </div>
                        <div>
                            ${formatearTexto(mensaje)}
                        </div>
                    </div>
                `
                : `
                    <div class="sigi-result-card">

                        <div class="sigi-result-title">
                            ${obtenerIconoTipo(tipo)}
                            ${titulo}
                        </div>

                        <div>
                            ${formatearTexto(mensaje)}
                        </div>

                        ${renderizarResultado(
                            resultado,
                            tipo
                        )}

                    </div>
                `;

        wrapper.innerHTML = `
            <div class="sigi-avatar">

                <div class="sigi-avatar-face">
                    <span></span>
                    <span></span>
                </div>

            </div>

            <div class="sigi-message-content">

                <div class="sigi-message-author">
                    SIGI
                </div>

                ${contenido}

                <div class="sigi-message-time">
                    Ahora
                </div>

            </div>
        `;

        conversation.appendChild(wrapper);

        mensajes++;

        if (mensajesEl) {
            mensajesEl.textContent = mensajes;
        }

        scrollConversation();
    }


    async function enviarConsulta(textoForzado = null) {

        if (enviando || !textarea || !conversation) {
            return;
        }

        const texto =
            textoForzado !== null
                ? String(textoForzado).trim()
                : textarea.value.trim();

        if (!texto) {
            textarea.focus();
            return;
        }

        enviando = true;

        if (btn) {
            btn.disabled = true;

            btn.innerHTML = `
                <span class="spinner-border spinner-border-sm"
                    aria-hidden="true"></span>
                <span>Consultando...</span>
            `;
        }

        textarea.disabled = true;

        if (suggestions) {
            suggestions
                .querySelectorAll('button')
                .forEach(
                    button => button.disabled = true
                );
        }

        agregarMensajeUsuario(texto);

        if (textoForzado === null) {
            textarea.value = '';
        }

        cambiarEstadoRobot(
            obtenerMensajeProcesando(texto),
            'thinking',
            true
        );

        mostrarEscribiendo();

        try {

            const response =
                await fetch(
                    consultaUrl,
                    {
                        method: 'POST',

                        headers: {
                            'Content-Type':
                                'application/json',

                            'Accept':
                                'application/json',

                            'X-CSRF-TOKEN':
                                csrfToken,

                            'X-Requested-With':
                                'XMLHttpRequest'
                        },

                        body: JSON.stringify({
                            consulta: texto
                        })
                    }
                );

            let data = null;

            try {
                data = await response.json();
            } catch (jsonError) {
                data = null;
            }

            quitarEscribiendo();

            if (!response.ok) {

                let mensajeError =
                    'No pude procesar la consulta.';

                if (data?.message) {
                    mensajeError =
                        data.message;
                }

                if (data?.errors?.consulta?.[0]) {
                    mensajeError =
                        data.errors.consulta[0];
                }

                throw new Error(mensajeError);
            }

            if (!data) {
                throw new Error(
                    'SIGI no devolvió una respuesta válida.'
                );
            }

            consultas++;

            if (consultasEl) {
                consultasEl.textContent = consultas;
            }

            agregarRespuesta(
                data.mensaje ||
                'Consulta procesada.',
                data.resultado ?? null,
                data.tipo ?? null,
                data.success === false
            );

            if (
                data.tipo === 'clima'
            ) {
                cambiarEstadoRobot(
                    'Listo, encontré el clima. 🌤️',
                    'ready',
                    true
                );
            } else if (
                data.tipo === 'movimiento' ||
                data.tipo === 'movimientos' ||
                data.tipo === 'resumen' ||
                data.tipo === 'periodo'
            ) {
                cambiarEstadoRobot(
                    'Listo, encontré la información. 💰',
                    'ready',
                    true
                );
            } else if (
                String(texto).toLowerCase().includes('estatuto') ||
                String(texto).toLowerCase().includes('artículo') ||
                String(texto).toLowerCase().includes('articulo')
            ) {
                cambiarEstadoRobot(
                    'Listo, revisé los estatutos. 📄',
                    'ready',
                    true
                );
            } else {
                mostrarEstadoRobotListo();
            }

        } catch (error) {

            quitarEscribiendo();

            agregarRespuesta(
                error.message ||
                'No pude conectarme con SIGI en este momento.',
                null,
                'error',
                true
            );

            cambiarEstadoRobot(
                'Tuve un pequeño problema. Inténtalo otra vez.',
                'ready',
                true
            );

        } finally {

            enviando = false;

            textarea.disabled = false;

            if (btn) {

                btn.disabled = false;

                btn.innerHTML = `
                    <i class="cil-send"></i>
                    <span>Enviar</span>
                `;
            }

            if (suggestions) {
                suggestions
                    .querySelectorAll('button')
                    .forEach(
                        button => button.disabled = false
                    );
            }

            textarea.focus();
        }
    }


    if (btn) {
        btn.addEventListener(
            'click',
            function () {
                enviarConsulta();
            }
        );
    }


    if (textarea) {

        textarea.addEventListener(
            'keydown',
            function (event) {

                if (
                    event.key === 'Enter' &&
                    !event.shiftKey
                ) {

                    event.preventDefault();

                    enviarConsulta();
                }
            }
        );
    }


    function abrirEstatutos() {
        window.open(
            estatutosUrl,
            '_blank',
            'noopener,noreferrer'
        );
    }


    document.addEventListener('click', function (event) {

        const botonPdf =
            event.target.closest('[data-sigi-open-pdf]');

        if (!botonPdf) {
            return;
        }

        event.preventDefault();
        abrirEstatutos();
    });


    if (suggestions) {

        suggestions.addEventListener(
            'click',
            function (event) {

                const button =
                    event.target.closest(
                        '[data-sigi-query]'
                    );

                if (!button) {
                    return;
                }

                const consulta =
                    button.dataset.sigiQuery;

                if (
                    !consulta ||
                    enviando
                ) {
                    return;
                }

                enviarConsulta(consulta);
            }
        );
    }


    const limpiar =
        document.querySelector(
            '.sigi-header-button'
        );

    if (limpiar) {

        limpiar.addEventListener(
            'click',
            function () {

                if (enviando) {
                    return;
                }

                conversation.innerHTML = `
                    <div class="sigi-message sigi-message-assistant">
                        <div class="sigi-message-content">
                            <div class="sigi-message-meta">
                                <span class="sigi-meta-avatar">S</span>
                                <strong>SIGI</strong>
                                <span>Ahora</span>
                            </div>
                            <div class="sigi-bubble sigi-bubble-assistant">
                                <p>¡Hola, ${escapeHtml(@json(auth()->user()?->name ?? 'amigo'))}! 👋</p>
                                <p class="mb-0">He limpiado la conversación. Empecemos de nuevo. ¿Qué deseas consultar?</p>
                            </div>
                        </div>
                    </div>
                    <div class="sigi-suggestions" id="sigiSuggestions">
                        <div class="sigi-suggestions-title">Empieza con una consulta</div>
                        <div class="sigi-suggestion-grid">
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuál es el saldo de caja actual?"><span><i class="cil-wallet"></i></span><div><strong>Saldo de caja</strong><small>¿Cuánto tenemos disponible?</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuánto se recaudó este mes?"><span><i class="cil-chart-line"></i></span><div><strong>Ingresos del período</strong><small>¿Cuánto ingresamos?</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuánto se gastó este mes?"><span><i class="cil-transfer"></i></span><div><strong>Egresos del período</strong><small>¿Cuánto gastamos?</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Cuáles fueron los últimos movimientos?"><span><i class="cil-list"></i></span><div><strong>Últimos movimientos</strong><small>Revisa los movimientos recientes</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion" data-sigi-query="¿Qué puedo consultar en SIGI?"><span><i class="cil-lightbulb"></i></span><div><strong>Qué puedes hacer</strong><small>Conoce las capacidades de SIGI</small></div><i class="cil-arrow-right"></i></button>
                            <button type="button" class="sigi-suggestion sigi-suggestion-document" data-sigi-open-pdf><span><i class="cil-description"></i></span><div><strong>Estatutos del Grupo 21</strong><small>Consulta el documento oficial en PDF</small></div><i class="cil-arrow-right"></i></button>
                        </div>
                    </div>
                `;

                mensajes = 1;
                consultas = 0;

                if (mensajesEl) {
                    mensajesEl.textContent = mensajes;
                }

                if (consultasEl) {
                    consultasEl.textContent = consultas;
                }

                textarea.value = '';
                ajustarAlturaTextarea();

                if (botPersonality) {
                    botPersonalityText.textContent =
                        'Conversación limpia. Estoy listo...';

                    botPersonality.classList.remove(
                        'is-listening',
                        'is-thinking'
                    );

                    botPersonality.classList.add(
                        'is-ready',
                        'is-visible'
                    );
                }

                if (botElement) {
                    botElement.classList.remove(
                        'is-listening',
                        'is-thinking'
                    );
                }

                scrollConversation();
            }
        );
    }

});
</script>
@endpush

@endsection