@extends('layouts.app')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/chat/chat.css') }}">

    <style>
        /* ==============================================================
            PESTAÑA ZOE
            ============================================================== */

        .chat-media-picker .chat-emoji-picker.d-none,
        .chat-media-picker .chat-gif-picker.d-none,
        .chat-media-picker .chat-sigi-picker.d-none {
            display: none !important;
        }

        .chat-media-tab-sigi {
            position: relative;
            transition:
                transform .2s ease,
                background-color .2s ease,
                box-shadow .2s ease;
        }

        .chat-media-tab-sigi:hover {
            transform: translateY(-2px);
        }

        .chat-media-tab-sigi.is-active {
            box-shadow: 0 6px 18px rgba(13, 110, 253, .12);
        }

        .chat-sigi-help-panel {
            padding: 18px;
            text-align: center;
        }

        .chat-sigi-help-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd, #6f42c1);
            color: #fff;
            font-size: 25px;
            box-shadow: 0 8px 22px rgba(13, 110, 253, .22);
            animation: chatSigiFloat 2.8s ease-in-out infinite;
        }

        .chat-sigi-help-title {
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 1rem;
        }

        .chat-sigi-help-text {
            max-width: 520px;
            margin: 0 auto 14px;
            color: #6c757d;
            font-size: .88rem;
            line-height: 1.5;
        }

        .chat-sigi-examples {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 7px;
            margin-bottom: 16px;
        }

        .chat-sigi-example {
            padding: 6px 10px;
            border: 1px solid rgba(13, 110, 253, .14);
            border-radius: 999px;
            background: rgba(13, 110, 253, .05);
            color: #495057;
            font-size: .76rem;
        }

        .chat-sigi-talk-button {
            border: 0;
            border-radius: 999px;
            padding: 10px 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, #0d6efd, #6f42c1);
            color: #fff;
            font-weight: 600;
            box-shadow: 0 8px 18px rgba(13, 110, 253, .22);
            transition:
                transform .2s ease,
                box-shadow .2s ease;
        }

        .chat-sigi-talk-button:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 11px 24px rgba(13, 110, 253, .28);
        }

        .chat-sigi-talk-button:active {
            transform: scale(.97);
        }

        @keyframes chatSigiFloat {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-4px);
            }
        }
    </style>
@endpush

@section('content')

<div class="container-fluid py-4 chat-page">

    <div class="row justify-content-center">

        <div class="col-12 col-xl-10">

            <div class="card border-0 shadow-sm overflow-hidden chat-card">

                {{-- ==========================================================
                     HEADER
                =========================================================== --}}

                <div class="card-header bg-white border-bottom py-3 chat-header">

                    <div class="d-flex align-items-center justify-content-between">

                        <div class="d-flex align-items-center">

                            <div class="chat-brand-icon me-3">
                                <i class="bi bi-chat-dots-fill"></i>
                            </div>

                            <div>

                                <div class="chat-title-row"><h5 class="mb-0 fw-bold chat-title">{{ $chat->nombre }}</h5><span class="chat-live-badge"><span class="chat-live-pulse"></span>Activo</span></div>

                                <div class="chat-header-meta">

                                    <span class="chat-online-dot"></span>

                                    <strong id="chatOnlineCount">
                                        {{ $personasEnLinea ?? 0 }}
                                    </strong>

                                    <span id="chatOnlineLabel">
                                        {{ ($personasEnLinea ?? 0) === 1 ? 'en línea' : 'en línea' }}
                                    </span>

                                    <span class="mx-1">•</span>

                                    <strong id="chatPeopleCount">
                                        {{ $personas }}
                                    </strong>

                                    <span id="chatPeopleLabel">
                                        {{ $personas === 1 ? 'persona' : 'personas' }}
                                    </span>

                                </div>

                            </div>

                        </div>

                        <div>

                            <button
                                type="button"
                                class="btn btn-light btn-sm rounded-circle"
                                title="Personas del chat"
                                id="chatInfoButton"
                                aria-label="Personas del chat"
                            >
                                <i class="bi bi-info-circle"></i>
                            </button>

                        </div>

                    </div>

                </div>


                {{-- ==========================================================
                     MENSAJES
                =========================================================== --}}

                <div
                    class="chat-messages"
                    id="chatMessages"
                >

                    @forelse($mensajes as $mensaje)

                        @php

                            $esPropio =
                                auth()->check() &&
                                $mensaje->user_id === auth()->id();

                            $esSigi =
                                $mensaje->tipo === 'sigi';

                            $esSistema =
                                $mensaje->tipo === 'sistema';

                        @endphp


                        {{-- ==================================================
                             MENSAJE DEL SISTEMA
                        =================================================== --}}

                        @if($esSistema)

                            <div
                                class="chat-system-message"
                                data-message-id="{{ $mensaje->id }}"
                            >

                                <span>{{ trim($mensaje->mensaje) }}</span>

                            </div>


                        {{-- ==================================================
                             MENSAJE DE ZOE
                        =================================================== --}}

                        @elseif($esSigi)

                            <div
                                class="chat-message chat-message-sigi"
                                data-message-id="{{ $mensaje->id }}"
                            >

                                <div class="chat-avatar chat-avatar-sigi">

                                    <i class="bi bi-robot"></i>

                                </div>

                                <div class="chat-message-content">

                                    <div class="chat-message-name">
                                        ZOE
                                    </div>

                                    <div class="chat-bubble chat-bubble-sigi-content" data-sigi-message>{{ trim($mensaje->mensaje) }}</div>

                                    <div class="chat-message-time">
                                        {{ $mensaje->created_at->format('H:i') }}
                                    </div>

                                </div>

                            </div>


                        {{-- ==================================================
                             MENSAJE DE USUARIO
                        =================================================== --}}

                        @else

                            <div
                                class="chat-message {{ $esPropio ? 'chat-message-own' : '' }}"
                                data-message-id="{{ $mensaje->id }}"
                            >

                                <div class="chat-avatar">

                                    @if($mensaje->usuario)

                                        <img
                                            src="{{ $mensaje->usuario->avatar }}"
                                            alt="{{ $mensaje->usuario->name }}"
                                        >

                                    @else

                                        <i class="bi bi-person-fill"></i>

                                    @endif

                                </div>

                                <div class="chat-message-content">

                                    @unless($esPropio)

                                        <div class="chat-message-name">
                                            {{ $mensaje->usuario?->name ?? 'Usuario' }}
                                        </div>

                                    @endunless

                                    <div class="chat-bubble">{{ trim($mensaje->mensaje) }}</div>

                                    <div class="chat-message-time">

                                        {{ $mensaje->created_at->format('H:i') }}

                                        @if($mensaje->editado)

                                            <span class="ms-1">
                                                (editado)
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        @endif

                    @empty

                        <div
                            class="chat-empty"
                            id="chatEmpty"
                        >

                            <div class="chat-empty-icon">

                                <i class="bi bi-chat-square-text"></i>

                            </div>

                            <h6 class="fw-bold mt-3">
                                Todavía no hay mensajes
                            </h6>

                            <p class="text-muted small mb-0">
                                Sé el primero en iniciar la conversación.
                            </p>

                        </div>

                    @endforelse

                </div>


                {{-- ==========================================================
                     COMPOSITOR
                =========================================================== --}}

                <div class="card-footer bg-white border-top p-3 chat-composer">

                    <div
                        id="chatTypingIndicator"
                        class="chat-typing-indicator"
                        aria-live="polite"
                        aria-atomic="true"
                    >
                        <div class="chat-typing-content">
                            <span class="chat-typing-name" id="chatTypingName"></span>
                            <span class="chat-typing-dots" aria-hidden="true">
                                <span class="chat-typing-dot"></span>
                                <span class="chat-typing-dot"></span>
                                <span class="chat-typing-dot"></span>
                            </span>
                        </div>
                    </div>

                    <div
                        id="chatAttachmentPreview"
                        class="chat-attachment-preview d-none"
                        aria-live="polite"
                    ></div>

                    <form
                        id="chatForm"
                        action="{{ route('chat.store') }}"
                        method="POST"
                        class="d-flex align-items-end gap-2"
                    >

                        @csrf

                        <button
                            type="button"
                            class="chat-media-button"
                            title="Emojis y GIFs"
                            id="chatMediaButton"
                            aria-label="Abrir emojis y GIFs"
                            aria-expanded="false"
                        >
                            <span>😊</span>
                        </button>

                        <button
                            type="button"
                            class="chat-attach-button"
                            title="Adjuntar archivo"
                            id="chatAttachButton"
                        >

                            <i class="bi bi-paperclip"></i>

                        </button>

                        <input
                            type="file"
                            id="chatFileInput"
                            class="d-none"
                            aria-label="Seleccionar archivo"
                        >


                        <div class="chat-input-wrapper flex-grow-1">

                            <textarea
                                id="chatInput"
                                name="mensaje"
                                class="form-control"
                                rows="1"
                                maxlength="5000"
                                placeholder="Escribe un mensaje..."
                                autocomplete="off"
                            ></textarea>

                        </div>


                        <button
                            type="submit"
                            class="chat-send-button"
                            title="Enviar mensaje"
                            id="chatSendButton"
                        >

                            <i class="bi bi-send-fill"></i>

                        </button>

                    </form>

                    <div
                        id="chatError"
                        class="text-danger small mt-2 d-none"
                    ></div>

                    <div
                        class="chat-media-picker d-none"
                        id="chatMediaPicker"
                        role="dialog"
                        aria-label="Emojis y GIFs"
                    >
                        <div class="chat-media-tabs" role="tablist" aria-label="Emojis y GIFs">
                            <button
                                type="button"
                                class="chat-media-tab is-active"
                                id="chatMediaEmojiTab"
                                role="tab"
                                aria-selected="true"
                                aria-controls="chatEmojiPicker"
                            >
                                😊 <span>Emojis</span>
                            </button>

                            <button
                                type="button"
                                class="chat-media-tab"
                                id="chatMediaGifTab"
                                role="tab"
                                aria-selected="false"
                                aria-controls="chatGifPicker"
                            >
                                <span>GIF</span>
                            </button>

                            <button
                                type="button"
                                class="chat-media-tab chat-media-tab-sigi"
                                id="chatMediaSigiTab"
                                role="tab"
                                aria-selected="false"
                                aria-controls="chatSigiPicker"
                                title="Hablar con ZOE"
                                aria-label="Hablar con ZOE"
                            >
                                🤖 <span>ZOE</span>
                            </button>

                            <button
                                type="button"
                                class="chat-media-close"
                                id="chatMediaClose"
                                aria-label="Cerrar emojis y GIFs"
                                title="Cerrar"
                            >
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <div
                            class="chat-emoji-picker"
                            id="chatEmojiPicker"
                            role="tabpanel"
                            aria-label="Selector de emojis"
                        >
                            <div
                                class="chat-emoji-grid"
                                id="chatEmojiGrid"
                            ></div>
                        </div>

                        <div
                            class="chat-gif-picker d-none"
                            id="chatGifPicker"
                            role="tabpanel"
                            aria-label="Selector de GIFs"
                        >
                            <div class="chat-gif-header">
                                <div>
                                    <strong>GIFs</strong>
                                    <span class="chat-gif-powered">Powered by GIPHY</span>
                                </div>
                            </div>

                            <div class="chat-gif-search">
                                <div class="chat-gif-search-wrapper">
                                    <i class="bi bi-search"></i>
                                    <input
                                        type="search"
                                        id="chatGifSearch"
                                        class="chat-gif-search-input"
                                        placeholder="Buscar GIF..."
                                        autocomplete="off"
                                        maxlength="80"
                                    >
                                </div>

                                <button
                                    type="button"
                                    class="chat-gif-search-button"
                                    id="chatGifSearchButton"
                                    title="Buscar GIFs"
                                    aria-label="Buscar GIFs"
                                >
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>

                            <div
                                class="chat-gif-status"
                                id="chatGifStatus"
                            ></div>

                            <div
                                class="chat-gif-grid"
                                id="chatGifGrid"
                            ></div>
                        </div>

                        {{-- ======================================================
                             PANEL DE ZOE
                        ======================================================= --}}

                        <div
                            class="chat-sigi-picker d-none"
                            id="chatSigiPicker"
                            role="tabpanel"
                            aria-label="Hablar con ZOE"
                        >
                            <div class="chat-sigi-help-panel">

                                <div class="chat-sigi-help-icon" aria-hidden="true">
                                    <i class="bi bi-robot"></i>
                                </div>

                                <div class="chat-sigi-help-title">
                                    Habla con ZOE
                                </div>

                                <p class="chat-sigi-help-text">
                                    Pregúntale sobre los ingresos, egresos, movimientos,
                                    saldos, periodos y otros datos de SIGEFIV.
                                    Puedes escribir o hablar con naturalidad.
                                </p>

                                <div class="chat-sigi-examples" aria-label="Ejemplos de preguntas">
                                    <span class="chat-sigi-example">
                                        “Muéstrame los ingresos de básquet”
                                    </span>

                                    <span class="chat-sigi-example">
                                        “¿Cuánto gastamos en enero?”
                                    </span>

                                    <span class="chat-sigi-example">
                                        “Dame los movimientos de marzo”
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="chat-sigi-talk-button"
                                    id="chatSigiTalkButton"
                                    title="Hablar con ZOE"
                                    aria-label="Hablar con ZOE"
                                >
                                    <i class="bi bi-mic-fill"></i>
                                    <span>Hablar con ZOE</span>
                                </button>

                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

{{-- ==========================================================
     PANEL DE PERSONAS
=========================================================== --}}

<div
    class="chat-people-overlay d-none"
    id="chatPeopleOverlay"
    aria-hidden="true"
>
    <div
        class="chat-people-panel"
        id="chatPeoplePanel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="chatPeopleTitle"
    >
        <div class="chat-people-header">
            <div>
                <div class="chat-people-title" id="chatPeopleTitle">
                    Personas del Chat Vecinal
                </div>

                <div class="chat-people-summary">
                    <span class="chat-online-dot"></span>
                    <strong id="chatPanelOnlineCount">
                        {{ $personasEnLinea ?? 0 }}
                    </strong>
                    <span>en línea</span>
                    <span class="mx-1">•</span>
                    <strong id="chatPanelPeopleCount">
                        {{ $personas }}
                    </strong>
                    <span>personas</span>
                </div>
            </div>

            <button
                type="button"
                class="btn btn-light btn-sm rounded-circle chat-people-close"
                id="chatPeopleClose"
                title="Cerrar"
                aria-label="Cerrar panel"
            >
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div
            class="chat-people-list"
            id="chatPeopleList"
        ></div>
    </div>
</div>

{{-- ======================================================================
     CONFIGURACIÓN DEL CHAT PARA JAVASCRIPT
     ======================================================================= --}}
<div
    id="chatConfig"
    data-user-id="{{ auth()->id() ?? '' }}"
    data-mensajes-url="{{ route('chat.nuevos') }}"
    data-presencia-url="{{ route('chat.presencia') }}"
    data-escribiendo-url="{{ route('chat.escribiendo') }}"
    data-estado-escribiendo-url="{{ route('chat.escribiendo') }}"
    data-giphy-key="{{ config('services.giphy.key') }}"
    data-sigi-voice-url="{{ route('sigi.voz') }}"
    hidden
></div>

@push('scripts')
    <script src="{{ asset('js/chat/chat.js') }}"></script>
@endpush

@endsection