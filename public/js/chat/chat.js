document.addEventListener('DOMContentLoaded', function () {

    /* ======================================================================
       ELEMENTOS
    ====================================================================== */

    const form =
        document.getElementById('chatForm');

    const input =
        document.getElementById('chatInput');

    const messages =
        document.getElementById('chatMessages');

    const sendButton =
        document.getElementById('chatSendButton');

    const typingIndicator =
        document.getElementById('chatTypingIndicator');

    const typingName =
        document.getElementById('chatTypingName');

    /* ======================================================================
       VOZ DE SIGI — MODO CONVERSACIÓN
    ====================================================================== */

    const voiceButton =
        document.getElementById('chatVoiceButton');

    const SigiSpeechRecognition =
        window.SpeechRecognition ||
        window.webkitSpeechRecognition;

    const chatConfig =
        document.getElementById('chatConfig');

    const urlSigiVoz =
        chatConfig?.dataset.sigiVoiceUrl ||
        '/sigi/voz';

    let sigiRecognition = null;
    let sigiConversationActive = false;
    let sigiIsSpeaking = false;
    let sigiWaitingForResponse = false;
    let sigiRespuestasVozPendientes = 0;
    let sigiRestartTimer = null;
    let sigiAudio = null;
    let sigiAudioUrl = null;

    function sigiNormalizar(texto) {
        return String(texto ?? '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[¿?¡!.,;:]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function sigiLimpiarAudio() {
        if (sigiAudio) {
            try {
                sigiAudio.pause();
                sigiAudio.removeAttribute('src');
                sigiAudio.load();
            } catch (error) {
                console.warn('[SIGI Voz] No se pudo limpiar el audio:', error);
            }
            sigiAudio = null;
        }

        if (sigiAudioUrl) {
            URL.revokeObjectURL(sigiAudioUrl);
            sigiAudioUrl = null;
        }
    }

    function sigiHablarNavegador(texto, alTerminar = null) {
        if (!window.speechSynthesis) {
            if (typeof alTerminar === 'function') alTerminar();
            return;
        }

        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(texto);
        utterance.lang = 'es-PE';
        utterance.rate = 1;
        utterance.pitch = 1;
        sigiIsSpeaking = true;

        utterance.onend = utterance.onerror = function () {
            sigiIsSpeaking = false;
            if (typeof alTerminar === 'function') alTerminar();
            if (sigiConversationActive) sigiProgramarEscucha();
        };

        window.speechSynthesis.speak(utterance);
    }

    async function sigiHablar(texto, alTerminar = null) {
        const mensaje = String(texto ?? '').trim();
        if (!mensaje) {
            if (typeof alTerminar === 'function') alTerminar();
            return;
        }

        sigiLimpiarAudio();
        if (window.speechSynthesis) window.speechSynthesis.cancel();
        sigiIsSpeaking = true;

        try {
            const respuesta = await fetch(urlSigiVoz, {
                method: 'POST',
                headers: {
                    'Accept': 'audio/mpeg',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin',
                body: JSON.stringify({ text: mensaje })
            });

            if (!respuesta.ok) throw new Error(`HTTP ${respuesta.status}`);

            const audioBlob = await respuesta.blob();
            if (!audioBlob || audioBlob.size === 0) throw new Error('Kokoro devolvió un audio vacío.');

            sigiAudioUrl = URL.createObjectURL(audioBlob);
            sigiAudio = new Audio(sigiAudioUrl);
            sigiAudio.preload = 'auto';

            sigiAudio.onended = sigiAudio.onerror = function () {
                sigiIsSpeaking = false;
                sigiLimpiarAudio();
                if (typeof alTerminar === 'function') alTerminar();
                if (sigiConversationActive) sigiProgramarEscucha();
            };

            await sigiAudio.play();
        } catch (error) {
            sigiIsSpeaking = false;
            sigiLimpiarAudio();
            sigiHablarNavegador(mensaje, alTerminar);
        }
    }

    async function sigiHablarRespuesta(texto) {
        if (sigiRespuestasVozPendientes > 0) sigiRespuestasVozPendientes--;
        sigiWaitingForResponse = sigiRespuestasVozPendientes > 0;
        await sigiHablar(texto);
    }

    function sigiActualizarBoton() {
        if (!voiceButton) return;
        if (sigiConversationActive) {
            voiceButton.classList.add('is-listening');
            voiceButton.setAttribute('aria-pressed', 'true');
        } else {
            voiceButton.classList.remove('is-listening');
            voiceButton.setAttribute('aria-pressed', 'false');
        }
    }

    function sigiDetenerReconocimiento() {
        if (sigiRestartTimer) {
            clearTimeout(sigiRestartTimer);
            sigiRestartTimer = null;
        }
        if (sigiRecognition) {
            try {
                sigiRecognition.onend = null;
                sigiRecognition.onerror = null;
                sigiRecognition.stop();
            } catch (e) {}
        }
        sigiRecognition = null;
    }

    function sigiProgramarEscucha() {
        if (!sigiConversationActive || sigiIsSpeaking) return;
        if (sigiRestartTimer) clearTimeout(sigiRestartTimer);
        sigiRestartTimer = setTimeout(() => {
            sigiRestartTimer = null;
            sigiIniciarEscucha();
        }, 250);
    }

    function sigiResponderConversacion(texto) {
        const normalizado = sigiNormalizar(texto);
        if (/^(hola|hola sigi|buenas|buenos dias|buenas tardes|buenas noches)$/.test(normalizado)) {
            sigiHablar('Hola. ¿Cómo estás? Me alegra escucharte. ¿En qué conversamos?');
            return true;
        }
        if (/^(como estas|como estas sigi|que tal|como te va)$/.test(normalizado)) {
            sigiHablar('Estoy muy bien, gracias. Aquí listo para conversar contigo. ¿Qué hacemos?');
            return true;
        }
        return false;
    }

    function sigiProcesarTexto(texto) {
        const mensaje = String(texto ?? '').trim();
        if (!mensaje || !sigiConversationActive) return;
        if (sigiResponderConversacion(mensaje)) return;
        if (!input || !form) return;

        sigiRespuestasVozPendientes++;
        sigiWaitingForResponse = true;
        input.value = mensaje;
        ajustarTextarea();
        form.requestSubmit();
    }

    function sigiIniciarEscucha() {
        if (!sigiConversationActive || sigiIsSpeaking || !SigiSpeechRecognition || sigiRecognition) return;

        const recognition = new SigiSpeechRecognition();
        sigiRecognition = recognition;
        recognition.lang = 'es-ES';
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.maxAlternatives = 3;

        recognition.onstart = () => sigiActualizarBoton();
        recognition.onresult = (event) => {
            let transcript = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                if (event.results[i].isFinal) transcript += event.results[i][0].transcript + ' ';
            }
            transcript = transcript.trim();
            if (transcript) sigiProcesarTexto(transcript);
        };

        recognition.onerror = () => {
            sigiRecognition = null;
            if (sigiConversationActive && !sigiIsSpeaking) sigiProgramarEscucha();
        };

        recognition.onend = () => {
            sigiRecognition = null;
            if (sigiConversationActive && !sigiIsSpeaking) sigiProgramarEscucha();
        };

        try { recognition.start(); } catch (e) { sigiRecognition = null; if (sigiConversationActive) sigiProgramarEscucha(); }
    }

    function sigiActivarConversacion() {
        if (!SigiSpeechRecognition) {
            alert('Tu navegador no admite reconocimiento de voz.');
            return;
        }
        sigiConversationActive = true;
        sigiIsSpeaking = false;
        sigiWaitingForResponse = false;
        sigiRespuestasVozPendientes = 0;
        sigiLimpiarAudio();
        window.speechSynthesis?.cancel();
        sigiActualizarBoton();
        sigiIniciarEscucha();
    }

    function sigiDesactivarConversacion() {
        sigiConversationActive = false;
        sigiIsSpeaking = false;
        sigiWaitingForResponse = false;
        sigiRespuestasVozPendientes = 0;
        sigiLimpiarAudio();
        window.speechSynthesis?.cancel();
        sigiDetenerReconocimiento();
        sigiActualizarBoton();
    }

    if (voiceButton) {
        voiceButton.addEventListener('click', () => {
            if (sigiConversationActive) sigiDesactivarConversacion();
            else sigiActivarConversacion();
        });
    }

    const attachButton = document.getElementById('chatAttachButton');
    const fileInput = document.getElementById('chatFileInput');
    const attachmentPreview = document.getElementById('chatAttachmentPreview');
    const errorBox = document.getElementById('chatError');
    const emptyMessage = document.getElementById('chatEmpty');
    const onlineCount = document.getElementById('chatOnlineCount');
    const peopleCount = document.getElementById('chatPeopleCount');
    const onlineLabel = document.getElementById('chatOnlineLabel');
    const peopleLabel = document.getElementById('chatPeopleLabel');
    const infoButton = document.getElementById('chatInfoButton');
    const peopleOverlay = document.getElementById('chatPeopleOverlay');
    const peoplePanel = document.getElementById('chatPeoplePanel');
    const peopleClose = document.getElementById('chatPeopleClose');
    const peopleList = document.getElementById('chatPeopleList');
    const panelOnlineCount = document.getElementById('chatPanelOnlineCount');
    const panelPeopleCount = document.getElementById('chatPanelPeopleCount');

    const usuarioActualId = chatConfig?.dataset.userId || 'null';
    const urlMensajesNuevos = chatConfig?.dataset.mensajesUrl || '/chat/nuevos';
    const urlPresencia = chatConfig?.dataset.presenciaUrl || '/chat/presencia';
    const urlEscribiendo = chatConfig?.dataset.escribiendoUrl || '/chat/escribiendo';
    const urlEstadoEscribiendo = chatConfig?.dataset.estadoEscribiendoUrl || '/chat/escribiendo';
    const giphyApiKey = chatConfig?.dataset.giphyKey || '';
    const intervaloMensajes = 3000;
    const intervaloEscribiendo = 1500;
    const intervaloConsultaEscritura = 700;
    const tiempoSinEscribir = 2400;
    const intervaloPresencia = 10000;

    let ultimoMensajeId = 0;

    if (messages) {
        messages.querySelectorAll('[data-message-id]').forEach(el => {
            const id = parseInt(el.dataset.messageId, 10);
            if (Number.isInteger(id) && id > ultimoMensajeId) ultimoMensajeId = id;
        });
    }

    let typingTimer = null;
    let typingHeartbeat = null;
    let enviandoEstadoEscritura = false;

    function actualizarIndicadorEscritura(usuarios) {
        if (!typingIndicator || !typingName) return;
        const lista = Array.isArray(usuarios) ? usuarios.filter(u => u && String(u.id) !== String(usuarioActualId)) : [];
        if (!lista.length) {
            typingName.textContent = '';
            typingIndicator.classList.remove('is-visible');
            return;
        }
        let texto = lista.length === 1 ? `${lista[0].name} está escribiendo` : `${lista.length} personas están escribiendo`;
        typingName.textContent = texto;
        typingIndicator.classList.add('is-visible');
    }

    async function enviarEstadoEscritura(escribiendo) {
        if (!urlEscribiendo || document.hidden || enviandoEstadoEscritura) return;
        enviandoEstadoEscritura = true;
        try {
            await fetch(urlEscribiendo, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin',
                body: JSON.stringify({ escribiendo: Boolean(escribiendo) }),
                cache: 'no-store'
            });
        } catch (e) {} finally { enviandoEstadoEscritura = false; }
    }

    function iniciarEscritura() {
        if (typingIndicator) typingIndicator.classList.remove('is-visible');
        if (!input || !input.value.trim()) { detenerEscritura(); return; }
        enviarEstadoEscritura(true);
        if (typingHeartbeat) clearInterval(typingHeartbeat);
        typingHeartbeat = setInterval(() => {
            if (input.value.trim() && !document.hidden) enviarEstadoEscritura(true);
            else detenerEscritura();
        }, intervaloEscribiendo);
        if (typingTimer) clearTimeout(typingTimer);
        typingTimer = setTimeout(detenerEscritura, tiempoSinEscribir);
    }

    function detenerEscritura() {
        if (typingTimer) { clearTimeout(typingTimer); typingTimer = null; }
        if (typingHeartbeat) { clearInterval(typingHeartbeat); typingHeartbeat = null; }
        enviarEstadoEscritura(false);
    }

    let consultandoEstadoEscritura = false;
    async function consultarEstadoEscritura() {
        if (consultandoEstadoEscritura || document.hidden || !urlEstadoEscribiendo) return;
        consultandoEstadoEscritura = true;
        try {
            const res = await fetch(urlEstadoEscribiendo, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (res.ok) {
                const data = await res.json();
                if (data.success && Array.isArray(data.usuarios_escribiendo)) {
                    actualizarIndicadorEscritura(data.usuarios_escribiendo);
                }
            }
        } catch (e) {} finally { consultandoEstadoEscritura = false; }
    }

    setInterval(consultarEstadoEscritura, intervaloConsultaEscritura);
    consultarEstadoEscritura();

    function desplazarAlFinal() {
        if (!messages) return;
        messages.scrollTop = messages.scrollHeight;
    }

    desplazarAlFinal();
    procesarMensajesSigiExistentes();

    function actualizarContadorEnLinea(cantidad) {
        if (!onlineCount) return;
        const n = parseInt(cantidad, 10);
        if (Number.isInteger(n)) onlineCount.textContent = n;
    }

    function escaparAtributo(texto) { return escaparHtml(texto); }

    function formatearUltimoVisto(fecha) {
        if (!fecha) return 'Sin registro de actividad';
        const d = new Date(fecha);
        if (Number.isNaN(d.getTime())) return 'Última actividad no disponible';
        const diff = Math.max(0, Date.now() - d.getTime());
        const s = Math.floor(diff / 1000);
        if (s < 60) return 'Hace unos segundos';
        const m = Math.floor(s / 60);
        if (m < 60) return `Hace ${m} ${m === 1 ? 'minuto' : 'minutos'}`;
        const h = Math.floor(m / 60);
        if (h < 24) return `Hace ${h} ${h === 1 ? 'hora' : 'horas'}`;
        const days = Math.floor(h / 24);
        return `Hace ${days} ${days === 1 ? 'día' : 'días'}`;
    }

    function crearAvatarPersona(usuario) {
        if (usuario && usuario.avatar) return `<img src="${escaparAtributo(usuario.avatar)}" alt="${escaparAtributo(usuario.name)}">`;
        return '<i class="bi bi-person-fill"></i>';
    }

    function actualizarPanelPersonas(personas) {
        if (!peopleList || !Array.isArray(personas)) return;
        if (peopleCount) peopleCount.textContent = personas.length;
        if (panelPeopleCount) panelPeopleCount.textContent = personas.length;
        const enLinea = personas.filter(u => u && u.en_linea === true).length;
        if (panelOnlineCount) panelOnlineCount.textContent = enLinea;
        if (onlineCount) onlineCount.textContent = enLinea;

        peopleList.innerHTML = personas.map(usuario => {
            const online = usuario && usuario.en_linea === true;
            const estado = online ? 'En línea' : formatearUltimoVisto(usuario?.ultimo_visto_at);
            return `
                <div class="chat-person">
                    <div class="chat-person-avatar">
                        ${crearAvatarPersona(usuario)}
                        <span class="chat-person-status ${online ? 'online' : ''}"></span>
                    </div>
                    <div class="chat-person-info">
                        <div class="chat-person-name">${escaparHtml(usuario?.name ?? 'Usuario')}</div>
                        <div class="chat-person-state ${online ? 'online' : ''}">${escaparHtml(estado)}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function abrirPanelPersonas() {
        if (!peopleOverlay) return;
        peopleOverlay.classList.remove('d-none');
        peopleOverlay.setAttribute('aria-hidden', 'false');
        document.body.classList.add('chat-people-open');
        cargarPersonasChat();
        if (peopleClose) peopleClose.focus();
    }

    function cerrarPanelPersonas() {
        if (!peopleOverlay) return;
        peopleOverlay.classList.add('d-none');
        peopleOverlay.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('chat-people-open');
    }

    async function cargarPersonasChat() {
        if (!peopleList) return;
        peopleList.innerHTML = '<div class="chat-people-empty">Actualizando...</div>';
        try {
            const res = await fetch(urlPresencia, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            if (data.success) {
                if (data.personas_en_linea !== undefined) actualizarContadorEnLinea(data.personas_en_linea);
                if (Array.isArray(data.usuarios)) actualizarPanelPersonas(data.usuarios);
                if (Array.isArray(data.usuarios_escribiendo)) actualizarIndicadorEscritura(data.usuarios_escribiendo);
            }
        } catch (e) {
            peopleList.innerHTML = '<div class="chat-people-empty">No se pudo cargar la lista.</div>';
        }
    }

    let enviandoPresencia = false;
    async function enviarPresencia() {
        if (document.hidden || enviandoPresencia) return;
        enviandoPresencia = true;
        try {
            const res = await fetch(urlPresencia, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (res.ok) {
                const data = await res.json();
                if (data.success) {
                    if (data.personas_en_linea !== undefined) actualizarContadorEnLinea(data.personas_en_linea);
                    if (peopleOverlay && !peopleOverlay.classList.contains('d-none') && Array.isArray(data.usuarios)) {
                        actualizarPanelPersonas(data.usuarios);
                    }
                    if (Array.isArray(data.usuarios_escribiendo)) actualizarIndicadorEscritura(data.usuarios_escribiendo);
                }
            }
        } catch (e) {} finally { enviandoPresencia = false; }
    }

    enviarPresencia();
    setInterval(enviarPresencia, intervaloPresencia);

    if (infoButton) infoButton.addEventListener('click', abrirPanelPersonas);
    if (peopleClose) peopleClose.addEventListener('click', cerrarPanelPersonas);
    if (peopleOverlay) peopleOverlay.addEventListener('click', (e) => { if (e.target === peopleOverlay) cerrarPanelPersonas(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && peopleOverlay && !peopleOverlay.classList.contains('d-none')) cerrarPanelPersonas(); });

    function ajustarTextarea() {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 140) + 'px';
    }

    if (input) {
        input.addEventListener('input', () => { ajustarTextarea(); iniciarEscritura(); });
    }

    /* ======================================================================
       SELECTOR DE EMOJIS (Grid de emojis)
    ====================================================================== */
    const emojiButton = document.getElementById('chatEmojiButton');
    const emojiPicker = document.getElementById('chatEmojiPicker');
    const emojiGrid = document.getElementById('chatEmojiGrid');
    const emojiClose = document.getElementById('chatEmojiClose');

    const emojis = [
        '😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰',
        '😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏',
        '😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠',
        '😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🫡','🤭','🤫',
        '❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖',
        '👍','👎','👏','🙌','🙏','💪','✌️','🤝','👋','👌','🤞','🤟','🤘','👊','✊','🤲',
        '🔥','⭐','✨','💡','💧','🏠','🌳','🐶','🐱','⚽','🏆','🍕','☕','🍔','🎵','📢'
    ];

    if (emojiGrid) {
        emojiGrid.innerHTML = emojis.map(emoji => `
            <button type="button" class="chat-emoji-item" data-emoji="${emoji}" aria-label="Emoji ${emoji}">
                ${emoji}
            </button>
        `).join('');

        emojiGrid.addEventListener('click', (event) => {
            const boton = event.target.closest('.chat-emoji-item');
            if (!boton) return;
            insertarEmoji(boton.dataset.emoji);
        });
    }

    function insertarEmoji(emoji) {
        if (!input) return;
        const inicio = input.selectionStart ?? input.value.length;
        const fin = input.selectionEnd ?? input.value.length;
        input.value = input.value.substring(0, inicio) + emoji + input.value.substring(fin);
        const nuevaPos = inicio + emoji.length;
        input.focus();
        input.setSelectionRange(nuevaPos, nuevaPos);
        ajustarTextarea();
    }

    /* ======================================================================
       SELECTOR UNIFICADO MEDIA (Emojis / GIFs / ZOE) Y ADJUNTOS
    ====================================================================== */
    const mediaButton = document.getElementById('chatMediaButton');
    const mediaPicker = document.getElementById('chatMediaPicker');
    const mediaEmojiTab = document.getElementById('chatMediaEmojiTab');
    const mediaGifTab = document.getElementById('chatMediaGifTab');
    const mediaSigiTab = document.getElementById('chatMediaSigiTab');
    const mediaClose = document.getElementById('chatMediaClose');
    const sigiPicker = document.getElementById('chatSigiPicker');
    const gifPicker = document.getElementById('chatGifPicker');

    function abrirSelectorMedia(tipo = 'emoji') {
        if (!mediaPicker) return;
        mediaPicker.classList.remove('d-none');

        if (emojiPicker) emojiPicker.style.setProperty('display', tipo === 'emoji' ? 'block' : 'none', 'important');
        if (gifPicker) gifPicker.style.setProperty('display', tipo === 'gif' ? 'block' : 'none', 'important');
        if (sigiPicker) sigiPicker.style.setProperty('display', tipo === 'sigi' ? 'block' : 'none', 'important');

        if (mediaEmojiTab) mediaEmojiTab.classList.toggle('is-active', tipo === 'emoji');
        if (mediaGifTab) mediaGifTab.classList.toggle('is-active', tipo === 'gif');
        if (mediaSigiTab) mediaSigiTab.classList.toggle('is-active', tipo === 'sigi');
    }

    function cerrarSelectorMedia() {
        if (!mediaPicker) return;
        mediaPicker.classList.add('d-none');
    }

    if (mediaButton) {
        mediaButton.addEventListener('click', (e) => {
            e.stopPropagation();
            if (mediaPicker && !mediaPicker.classList.contains('d-none')) {
                cerrarSelectorMedia();
            } else {
                abrirSelectorMedia('emoji');
            }
        });
    }

    if (mediaEmojiTab) mediaEmojiTab.addEventListener('click', (e) => { e.stopPropagation(); abrirSelectorMedia('emoji'); });
    if (mediaGifTab) mediaGifTab.addEventListener('click', (e) => { e.stopPropagation(); abrirSelectorMedia('gif'); });
    if (mediaSigiTab) mediaSigiTab.addEventListener('click', (e) => { e.stopPropagation(); abrirSelectorMedia('sigi'); });
    if (mediaClose) mediaClose.addEventListener('click', () => cerrarSelectorMedia());

    // ADJUNTOS DE ARCHIVOS
    let archivoSeleccionado = null;

    function formatearTamanoArchivo(bytes) {
        const n = Number(bytes || 0);
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return (n / 1024).toFixed(1) + ' KB';
        return (n / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function iconoParaMime(mime) {
        const tipo = String(mime || '').toLowerCase();
        if (tipo.startsWith('image/')) return 'bi-image';
        if (tipo.startsWith('audio/')) return 'bi-music-note-beamed';
        if (tipo.startsWith('video/')) return 'bi-camera-video';
        if (tipo === 'application/pdf') return 'bi-file-earmark-pdf';
        return 'bi-paperclip';
    }

    function mostrarVistaPreviaArchivo(archivo) {
        if (!attachmentPreview) return;
        if (!archivo) {
            attachmentPreview.classList.add('d-none');
            attachmentPreview.innerHTML = '';
            return;
        }

        attachmentPreview.innerHTML = `
            <div class="chat-attachment-preview-icon"><i class="bi ${iconoParaMime(archivo.type)}"></i></div>
            <div class="chat-attachment-preview-info">
                <div class="chat-attachment-preview-name">${escaparHtml(archivo.name)}</div>
                <div class="chat-attachment-preview-size">${escaparHtml(formatearTamanoArchivo(archivo.size))}</div>
            </div>
            <button type="button" class="chat-attachment-preview-remove" id="chatAttachmentRemove" title="Quitar">
                <i class="bi bi-x-lg"></i>
            </button>
        `;
        attachmentPreview.classList.remove('d-none');
        document.getElementById('chatAttachmentRemove')?.addEventListener('click', limpiarArchivoSeleccionado);
    }

    function limpiarArchivoSeleccionado() {
        archivoSeleccionado = null;
        if (fileInput) fileInput.value = '';
        mostrarVistaPreviaArchivo(null);
        input?.focus();
    }

    if (attachButton && fileInput) {
        attachButton.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            archivoSeleccionado = fileInput.files?.[0] ?? null;
            mostrarVistaPreviaArchivo(archivoSeleccionado);
        });
    }

    function escaparHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    function mostrarError(mensaje) {
        if (!errorBox) return;
        errorBox.textContent = mensaje;
        errorBox.classList.remove('d-none');
    }

    function ocultarError() {
        if (!errorBox) return;
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    }

    /* ======================================================================
       PARSER BLINDADO DE ADJUNTOS
    ====================================================================== */
    function obtenerDatosAdjunto(texto) {
        if (!texto) return null;
        let limpio = String(texto).trim();

        try {
            const datos = JSON.parse(limpio);
            if (datos && datos.tipo === 'archivo' && datos.url && datos.nombre) {
                return limpiarTextoAdjunto(datos);
            }
        } catch (e1) {
            try {
                let corregido = limpio.replace(/\\"/g, '"');
                const datos = JSON.parse(corregido);
                if (datos && datos.tipo === 'archivo' && datos.url && datos.nombre) {
                    return limpiarTextoAdjunto(datos);
                }
            } catch (e2) {
                try {
                    if (limpio.startsWith('"') && limpio.endsWith('"')) {
                        limpio = JSON.parse(limpio);
                    }
                    const datos = JSON.parse(limpio);
                    if (datos && datos.tipo === 'archivo' && datos.url && datos.nombre) {
                        return limpiarTextoAdjunto(datos);
                    }
                } catch (e3) {
                    return null;
                }
            }
        }
        return null;
    }

    function limpiarTextoAdjunto(datos) {
        if (datos.texto) {
            datos.texto = String(datos.texto).replace(/^"|"$/g, '').replace(/\\"/g, '"');
        }
        if (datos.mensaje) {
            datos.mensaje = String(datos.mensaje).replace(/^"|"$/g, '').replace(/\\"/g, '"');
        }
        return datos;
    }

    function crearAccionesAdjunto(url, nombre) {
        return `
            <div class="chat-attachment-actions">
                <a href="${escaparHtml(url)}" target="_blank" rel="noopener noreferrer" class="chat-attachment-action" title="Ver archivo">
                    <i class="bi bi-eye"></i><span>Ver</span>
                </a>
                <a href="${escaparHtml(url)}" download="${escaparHtml(nombre)}" class="chat-attachment-action" title="Guardar como">
                    <i class="bi bi-download"></i><span>Guardar como…</span>
                </a>
            </div>
        `;
    }

    function crearVistaAdjunto(datos) {
        const contenedor = document.createElement('div');
        const mime = String(datos.mime || '').toLowerCase();
        const url = String(datos.url || '');
        const nombre = String(datos.nombre || 'Archivo');
        const texto = String(datos.texto || datos.mensaje || '').trim();

        if (mime.startsWith('image/')) {
            contenedor.className = 'chat-attachment-card chat-attachment-media-card';
            contenedor.innerHTML = `
                <a href="${escaparHtml(url)}" target="_blank" rel="noopener noreferrer" class="chat-attachment-link chat-attachment-media-link" title="Ver imagen">
                    <img src="${escaparHtml(url)}" alt="${escaparHtml(nombre)}" class="chat-attachment-image">
                </a>
                ${texto ? `<div class="chat-attachment-text">${escaparHtml(texto)}</div>` : ''}
                ${crearAccionesAdjunto(url, nombre)}
            `;
            return contenedor;
        }

        contenedor.className = 'chat-attachment-card chat-attachment-document-card';
        contenedor.innerHTML = `
            <div class="chat-attachment-file-main">
                <div class="chat-attachment-icon"><i class="bi ${iconoParaMime(mime)}"></i></div>
                <div class="chat-attachment-info">
                    <div class="chat-attachment-name">${escaparHtml(nombre)}</div>
                    <div class="chat-attachment-meta">${escaparHtml(formatearTamanoArchivo(datos.tamano))}</div>
                    ${texto ? `<div class="chat-attachment-text mt-1" style="font-size: 0.85rem; padding: 2px 0 0 0;">${escaparHtml(texto)}</div>` : ''}
                </div>
            </div>
            ${crearAccionesAdjunto(url, nombre)}
        `;
        return contenedor;
    }

    function renderizarAdjuntoEnBurbuja(bubble, datos) {
        if (!bubble || !datos) return;
        bubble.classList.add('chat-attachment-bubble');
        bubble.replaceChildren(crearVistaAdjunto(datos));
    }

    function procesarAdjuntosExistentes() {
        if (!messages) return;
        messages.querySelectorAll('.chat-message:not(.chat-message-sigi) .chat-bubble').forEach(bubble => {
            const datos = obtenerDatosAdjunto(bubble.textContent?.trim() || '');
            if (datos) renderizarAdjuntoEnBurbuja(bubble, datos);
        });
    }

    function esUrlGifPermitida(valor) {
        const texto = String(valor ?? '').trim();
        if (!texto) return false;
        try {
            const url = new URL(texto);
            return url.protocol === 'https:' && (url.hostname === 'giphy.com' || url.hostname.endsWith('.giphy.com'));
        } catch (e) { return false; }
    }

    function insertarGifEnBurbuja(bubble, url) {
        if (!bubble) return;
        bubble.classList.add('chat-gif-message-bubble');
        const img = document.createElement('img');
        img.className = 'chat-gif-message-image';
        img.src = url;
        bubble.replaceChildren(img);
    }

    function procesarGifsExistentes() {
        if (!messages) return;
        messages.querySelectorAll('.chat-message:not(.chat-message-sigi) .chat-bubble').forEach(bubble => {
            const texto = String(bubble.textContent ?? '').trim();
            if (esUrlGifPermitida(texto)) insertarGifEnBurbuja(bubble, texto);
        });
    }

    function analizarFilasMovimientos(texto) {
        const lineas = String(texto ?? '').split(/\r?\n/).map(l => l.trim()).filter(l => l.length > 0);
        const filas = [];
        lineas.forEach(linea => {
            const limpia = linea.replace(/^(?:[•●▪◦\-]\s*|\d+\.\s*)/, '').trim();
            const partes = limpia.split('|').map(p => p.trim());
            if (partes.length !== 5) return;
            filas.push({ fecha: partes[0], tipo: partes[1], concepto: partes[2], categoria: partes[3], monto: partes[4] });
        });
        return filas;
    }

    function renderizarMensajeSigi(bubble, texto) {
        if (!bubble) return;
        const contenido = String(texto ?? '');
        const filas = analizarFilasMovimientos(contenido);
        if (filas.length === 0) {
            bubble.classList.remove('chat-bubble-structured');
            bubble.textContent = contenido;
            return;
        }
        bubble.classList.add('chat-bubble-structured');
        bubble.textContent = contenido;
    }

    function procesarMensajesSigiExistentes() {
        if (!messages) return;
        messages.querySelectorAll('[data-sigi-message]').forEach(bubble => {
            renderizarMensajeSigi(bubble, bubble.textContent);
        });
    }

    function crearAvatar(usuario) {
        if (usuario && usuario.avatar) return `<img src="${escaparHtml(usuario.avatar)}" alt="${escaparHtml(usuario.name)}">`;
        return `<i class="bi bi-person-fill"></i>`;
    }

    function crearMensajeUsuario(data) {
        const usuario = data.usuario;
        const esPropio = usuario && Number(usuario.id) === Number(usuarioActualId);
        const elemento = document.createElement('div');
        elemento.className = 'chat-message' + (esPropio ? ' chat-message-own' : '');
        elemento.dataset.messageId = data.id;

        const nombre = usuario?.name ?? 'Usuario';
        const avatar = crearAvatar(usuario);
        const nombreHtml = esPropio ? '' : `<div class="chat-message-name">${escaparHtml(nombre)}</div>`;
        const editado = data.editado ? `<span class="ms-1">(editado)</span>` : '';
        const mensajeTexto = String(data.mensaje ?? '').trim();
        const datosAdjunto = obtenerDatosAdjunto(mensajeTexto);
        const esAdjunto = !!datosAdjunto;
        const esGif = !esAdjunto && esUrlGifPermitida(mensajeTexto);

        elemento.innerHTML = `
            <div class="chat-avatar">${avatar}</div>
            <div class="chat-message-content">
                ${nombreHtml}
                <div class="chat-bubble"></div>
                <div class="chat-message-time">${escaparHtml(data.created_at ?? '')}${editado}</div>
            </div>
        `;

        const bubble = elemento.querySelector('.chat-bubble');
        if (esGif) insertarGifEnBurbuja(bubble, mensajeTexto);
        else if (esAdjunto) renderizarAdjuntoEnBurbuja(bubble, datosAdjunto);
        else if (bubble) bubble.textContent = mensajeTexto;

        return elemento;
    }

    function crearMensajeSigi(data) {
        const elemento = document.createElement('div');
        elemento.className = 'chat-message chat-message-sigi';
        elemento.dataset.messageId = data.id;
        elemento.innerHTML = `
            <div class="chat-avatar chat-avatar-sigi"><i class="bi bi-robot"></i></div>
            <div class="chat-message-content">
                <div class="chat-message-name">ZOE</div>
                <div class="chat-bubble chat-bubble-sigi-content" data-sigi-message></div>
                <div class="chat-message-time">${escaparHtml(data.created_at ?? '')}</div>
            </div>
        `;
        const bubble = elemento.querySelector('[data-sigi-message]');
        renderizarMensajeSigi(bubble, data.mensaje);
        return elemento;
    }

    function crearMensajeSistema(data) {
        const elemento = document.createElement('div');
        elemento.className = 'chat-system-message';
        elemento.dataset.messageId = data.id;
        elemento.innerHTML = `<span>${escaparHtml(data.mensaje)}</span>`;
        return elemento;
    }

    function agregarMensaje(data) {
        if (!messages) return;
        if (emptyMessage) emptyMessage.remove();

        let elemento;
        if (data.tipo === 'sigi') elemento = crearMensajeSigi(data);
        else if (data.tipo === 'sistema') elemento = crearMensajeSistema(data);
        else elemento = crearMensajeUsuario(data);

        messages.appendChild(elemento);

        const id = parseInt(data.id, 10);
        if (Number.isInteger(id) && id > ultimoMensajeId) ultimoMensajeId = id;
        desplazarAlFinal();
    }

    function mensajeYaExiste(id) {
        if (!messages) return false;
        return !!messages.querySelector(`[data-message-id="${id}"]`);
    }

    async function consultarMensajesNuevos() {
        if (consultandoMensajes || document.hidden) return;
        consultandoMensajes = true;
        try {
            const url = new URL(urlMensajesNuevos, window.location.origin);
            url.searchParams.set('after_id', ultimoMensajeId);
            const respuesta = await fetch(url.toString(), {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                cache: 'no-store'
            });
            if (!respuesta.ok) return;
            const data = await respuesta.json();
            if (!data.success || !Array.isArray(data.mensajes)) return;

            if (data.personas_en_linea !== undefined) actualizarContadorEnLinea(data.personas_en_linea);
            if (Array.isArray(data.usuarios_escribiendo)) actualizarIndicadorEscritura(data.usuarios_escribiendo);

            data.mensajes.forEach(mensaje => {
                const id = parseInt(mensaje.id, 10);
                if (!Number.isInteger(id)) return;
                if (mensajeYaExiste(id)) {
                    if (id > ultimoMensajeId) ultimoMensajeId = id;
                    return;
                }
                agregarMensaje(mensaje);
            });
        } catch (e) {} finally { consultandoMensajes = false; }
    }

    let consultandoMensajes = false;
    setInterval(consultarMensajesNuevos, intervaloMensajes);

    /* ======================================================================
       ENVIAR MENSAJE (Con control anti-duplicidad y soporte ZOE 422)
    ====================================================================== */
    let enviandoChatEnCurso = false;

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (enviandoChatEnCurso || (input && input.disabled)) return;
        enviandoChatEnCurso = true;

        ocultarError();
        detenerEscritura();

        const mensaje = input.value.trim();
        const archivo = archivoSeleccionado;

        if (!mensaje && !archivo) {
            enviandoChatEnCurso = false;
            input.focus();
            return;
        }

        sendButton.disabled = true;
        const iconoOriginal = sendButton.innerHTML;
        sendButton.innerHTML = '<i class="bi bi-hourglass-split"></i>';

        try {
            const respuesta = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin',
                body: (() => {
                    const datos = new FormData();
                    datos.append('mensaje', mensaje);
                    if (archivo) datos.append('archivo', archivo);
                    return datos;
                })()
            });

            const data = await respuesta.json();

            // 🚨 MANEJADOR DE ZOE / BLOQUEO: Si el servidor responde con 422
            if (respuesta.status === 422) {
                mostrarError(data.message || 'Su cuenta fue bloqueada por conducta inapropiada.');

                // 🚫 SI ES EL SEGUNDO STRIKE (BLOQUEO PERMANENTE)
                if (data.es_bloqueo_permanente || data.detalle === 'bloqueo') {
                    if (input) {
                        input.value = '';
                        input.disabled = true;
                        input.placeholder = 'Tu cuenta ha sido suspendida permanentemente de este chat.';
                        input.style.backgroundColor = '#f1f1f1';
                    }
                    if (sendButton) {
                        sendButton.disabled = true;
                    }
                    if (attachButton) {
                        attachButton.style.display = 'none';
                    }
                    if (fileInput) {
                        fileInput.disabled = true;
                    }
                }
                return;
            }

            if (!respuesta.ok) {
                if (data.errors?.mensaje) mostrarError(data.errors.mensaje[0]);
                else if (data.errors?.archivo) mostrarError(data.errors.archivo[0]);
                else mostrarError(data.message ?? 'No se pudo enviar el mensaje.');
                return;
            }

            if (!data.success) {
                mostrarError('No se pudo enviar el mensaje.');
                return;
            }

            agregarMensaje(data.mensaje);

            input.value = '';
            input.style.height = 'auto';
            limpiarArchivoSeleccionado();
            input.focus();
            enviarPresencia();

        } catch (error) {
            console.error(error);
            mostrarError('No se pudo conectar con el servidor.');
        } finally {
            // Si la cuenta NO fue bloqueada, restauramos el botón de envío
            if (!input || !input.disabled) {
                sendButton.disabled = false;
                sendButton.innerHTML = iconoOriginal;
            }
            enviandoChatEnCurso = false;
        }
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    window.addEventListener('pagehide', () => detenerEscritura());

    procesarGifsExistentes();
    procesarAdjuntosExistentes();

});