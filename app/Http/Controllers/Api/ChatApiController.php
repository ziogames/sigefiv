<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChatService;
use App\Services\chat\ChatAttachmentService;
use App\Services\chat\ChatMessageService;
use App\Services\chat\ChatPresenceService;
use App\Services\chat\ChatResponseService;
use App\Services\chat\ChatSigiInterventionService;
use App\Services\chat\ChatTypingService;
use App\Services\ChatSigiService;
use App\Services\ZoeModeracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use App\Models\ChatMessage;
use App\Models\ChatMessageReaction;

class ChatApiController extends Controller
{
    /**
     * Obtener el estado inicial del Chat Vecinal.
     *
     * Incluye:
     * - Información básica del chat.
     * - Mensajes recientes.
     * - Personas conectadas.
     * - Usuarios escribiendo.
     */
    public function index(
        Request $request,
        ChatService $chatService,
        ChatPresenceService $chatPresenceService,
        ChatTypingService $chatTypingService,
        ChatResponseService $chatResponseService
    ): JsonResponse {
        try {
            $usuario = $request->user();

            $chat = $chatService->obtenerChatVecinal();

            $chatPresenceService->actualizarPresencia(
                $usuario
            );

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $mensajes = $chat->mensajes()
                ->with(['usuario', 'replyTo.usuario']) // 💬 Cargamos la relación del mensaje citado
                ->latest()
                ->limit(100)
                ->get()
                ->reverse()
                ->values();

            $resumen =
                $chatService->obtenerResumenPresencia();

            return response()->json([
                'success' => true,

                'chat' => [
                    'id' => $chat->id,
                    'nombre' => $chat->nombre ?? 'Chat Vecinal',
                ],

                'personas' =>
                    $resumen['total'] ?? 0,

                'personas_en_linea' =>
                    $resumen['en_linea'] ?? 0,

                'mensajes' =>
                    $chatResponseService->mensajes(
                        $mensajes
                    ),

                'usuarios_escribiendo' =>
                    $chatTypingService->obtenerUsuariosEscribiendo(
                        $chat,
                        $chatPresenceService->obtenerPersonasChat(
                            $chat
                        )
                    ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo cargar el Chat Vecinal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar un mensaje al Chat Vecinal.
     *
     * Mantiene la misma lógica utilizada por el Chat Web:
     *
     * mensaje
     *   ↓
     * ChatMessageService
     *   ↓
     * ZOE / SIGI
     */
    public function store(
        Request $request,
        ChatService $chatService,
        ChatPresenceService $chatPresenceService,
        ChatSigiService $chatSigiService,
        ChatAttachmentService $chatAttachmentService,
        ChatMessageService $chatMessageService,
        ChatSigiInterventionService $chatSigiInterventionService,
        ChatResponseService $chatResponseService,
        ZoeModeracionService $zoeService
    ): JsonResponse {
        try {
            $request->validate([
                'mensaje' => [
                    'nullable',
                    'string',
                    'max:5000',
                    'required_without:archivo',
                ],

                'archivo' => [
                    'nullable',
                    'file',
                    'max:51200',
                ],

                'reply_to_id' => [
                    'nullable',
                    'integer',
                    'exists:chat_messages,id', // 💬 Validamos que el mensaje al que se responde exista
                ],
            ]);

            $usuario = $request->user();

            $chat =
                $chatService->obtenerChatVecinal();

            /*
             * Actualizar presencia.
             */
            $chatPresenceService->actualizarPresencia(
                $usuario
            );

            /*
             * Verificar pertenencia.
             */
            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            /*
             * Contenido del mensaje.
             */
            $texto =
                trim(
                    (string) $request->input(
                        'mensaje',
                        ''
                    )
                );

            $archivoAdjunto =
                $request->file('archivo');

            $replyToId = $request->input('reply_to_id'); // 💬 Capturamos el ID del mensaje citado

            /*
             * 🚨 ZOE — MODERACIÓN DE MENSAJES (Insultos / Strikes / Bloqueo permanente)
             */
            if ($texto !== '') {
                $resultadoModeracion = $zoeService->procesarMensaje($chat->id, $usuario, $texto);

                if ($resultadoModeracion['bloquear_mensaje'] ?? false) {
                    return response()->json([
                        'success' => false,
                        'message' => $resultadoModeracion['message'] ?? 'Tu mensaje ha sido bloqueado por las normas de convivencia de ZOE.',
                        'detalle' => $resultadoModeracion['accion'] ?? 'advertencia',
                        'es_bloqueo_permanente' => ($resultadoModeracion['accion'] ?? '') === 'bloqueo',
                    ], 422);
                }
            }

            $contenidoMensaje =
                $texto;

            /*
             * Procesar archivo si existe.
             */
            if ($archivoAdjunto) {
                $contenidoMensaje =
                    $chatAttachmentService->procesar(
                        $archivoAdjunto,
                        $texto
                    );
            }

            /*
             * Crear mensaje (Pasando el reply_to_id si tu servicio lo recibe).
             */
            $mensaje =
                $chatMessageService->crearMensaje(
                    $chat,
                    $usuario,
                    $contenidoMensaje,
                    $replyToId // 💬 Enviamos el ID del mensaje respondido
                );

            /*
             * Los archivos no pasan por SIGI.
             */
            $datosAdjunto =
                json_decode(
                    $mensaje->mensaje,
                    true
                );

            $esArchivoAdjunto =
                is_array($datosAdjunto) &&
                ($datosAdjunto['tipo'] ?? null) === 'archivo';

            if ($esArchivoAdjunto) {
                return response()->json([
                    'success' => true,

                    'mensaje' =>
                        $chatResponseService->mensaje(
                            $mensaje
                        ),

                    'sigi_analisis' => [
                        'analizado' => false,
                        'categoria' => null,
                        'intencion' => null,
                        'prioridad' => null,
                        'contextual' => false,
                        'debe_intervenir' => false,
                    ],

                    'sigi_evento' => null,

                    'sigi_decision' => null,

                    'sigi' => null,
                ]);
            }

            /*
             * Detectar @zoe.
             */
            $textoMensaje =
                trim(
                    $mensaje->mensaje
                );

            $mencionaSigi =
                preg_match(
                    '/(^|\s)@?zoe\b/i',
                    $textoMensaje
                ) === 1;

            /*
             * Variables de SIGI.
             */
            $analisisSigi = [];

            $resultadoEvento = [
                'evento' => null,
            ];

            $decisionSigi = null;

            $mensajeSigi = null;

            /*
             * @zoe — consulta explícita.
             */
            if ($mencionaSigi) {
                $mensajeSigi =
                    $chatSigiService->procesar(
                        $chat,
                        $mensaje->mensaje,
                        $usuario
                    );

            } else {

                /*
                 * SIGI — intervención contextual automática.
                 */
                $resultadoSigi =
                    $chatSigiInterventionService->procesar(
                        $chat,
                        $mensaje
                    );

                $analisisSigi =
                    $resultadoSigi['analisis'];

                $resultadoEvento =
                    $resultadoSigi['evento'];

                $decisionSigi =
                    $resultadoSigi['decision'];

                $mensajeSigi =
                    $resultadoSigi['mensaje_sigi'];
            }

            /*
             * Respuesta.
             */
            return response()->json([
                'success' => true,

                /*
                 * Mensaje del vecino.
                 */
                'mensaje' =>
                    $chatResponseService->mensaje(
                        $mensaje
                    ),

                /*
                 * Análisis de SIGI.
                 */
                'sigi_analisis' => [
                    'analizado' =>
                        $analisisSigi[
                            'analizado'
                        ] ?? false,

                    'categoria' =>
                        $analisisSigi[
                            'categoria'
                        ] ?? null,

                    'intencion' =>
                        $analisisSigi[
                            'intencion'
                        ] ?? null,

                    'prioridad' =>
                        $analisisSigi[
                            'prioridad'
                        ] ?? null,

                    'contextual' =>
                        $analisisSigi[
                            'contextual'
                        ] ?? false,

                    'debe_intervenir' =>
                        $analisisSigi[
                            'debe_intervenir'
                        ] ?? false,
                ],

                /*
                 * Evento.
                 */
                'sigi_evento' =>
                    $resultadoEvento['evento']
                        ? [
                            'id' =>
                                $resultadoEvento[
                                    'evento'
                                ]->id,

                            'categoria' =>
                                $resultadoEvento[
                                    'evento'
                                ]->categoria,

                            'estado' =>
                                $resultadoEvento[
                                    'evento'
                                ]->estado,

                            'total_reportes' =>
                                $resultadoEvento[
                                    'evento'
                                ]->total_reportes,

                            'reportes_problema' =>
                                $resultadoEvento[
                                    'evento'
                                ]->reportes_problema,

                            'reportes_resueltos' =>
                                $resultadoEvento[
                                    'evento'
                                ]->reportes_resueltos,

                            'usuarios_afectados' =>
                                $resultadoEvento[
                                    'evento'
                                ]->cantidadUsuariosAfectados(),

                            'usuarios_restablecidos' =>
                                $resultadoEvento[
                                    'evento'
                                ]->cantidadUsuariosRestablecidos(),
                        ]
                        : null,

                /*
                 * Decisión de SIGI.
                 */
                'sigi_decision' =>
                    $decisionSigi,

                /*
                 * Respuesta de SIGI.
                 */
                'sigi' =>
                    $mensajeSigi
                        ? [
                            'id' =>
                                $mensajeSigi->id,

                            'mensaje' =>
                                $mensajeSigi->mensaje,

                            'tipo' =>
                                $mensajeSigi->tipo,

                            'editado' =>
                                $mensajeSigi->editado,

                            'created_at' =>
                                $mensajeSigi->created_at
                                    ?->format('H:i'),

                            'usuario' => null,
                        ]
                        : null,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo enviar el mensaje.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Registrar presencia del usuario.
     */
    public function presencia(
        Request $request,
        ChatService $chatService,
        ChatPresenceService $chatPresenceService,
        ChatTypingService $chatTypingService
    ): JsonResponse {
        try {
            $usuario =
                $request->user();

            $chat =
                $chatService->obtenerChatVecinal();

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $chatPresenceService->actualizarPresencia(
                $usuario
            );

            return response()->json([
                'success' => true,

                ...$chatPresenceService->obtenerDatosPresencia(
                    $chat,
                    $usuario
                ),

                'usuarios_escribiendo' =>
                    $chatTypingService->obtenerUsuariosEscribiendo(
                        $chat,
                        $chatPresenceService->obtenerPersonasChat(
                            $chat
                        )
                    ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo actualizar la presencia.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar estado de escritura.
     */
    public function escribiendo(
        Request $request,
        ChatService $chatService,
        ChatTypingService $chatTypingService
    ): JsonResponse {
        try {
            $request->validate([
                'escribiendo' => [
                    'required',
                    'boolean',
                ],
            ]);

            $usuario =
                $request->user();

            $chat =
                $chatService->obtenerChatVecinal();

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $chatTypingService->actualizarEstado(
                $chat,
                $usuario,
                $request->boolean('escribiendo')
            );

            return response()->json([
                'success' => true,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo actualizar el estado de escritura.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener mensajes nuevos.
     *
     * Android enviará el ID del último mensaje recibido:
     *
     * /api/chat/nuevos?after_id=123
     */
    public function nuevos(
        Request $request,
        ChatService $chatService,
        ChatTypingService $chatTypingService,
        ChatPresenceService $chatPresenceService,
        ChatResponseService $chatResponseService
    ): JsonResponse {
        try {
            $request->validate([
                'after_id' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],
            ]);

            $usuario =
                $request->user();

            $chatPresenceService->actualizarPresencia(
                $usuario
            );

            $afterId =
                (int) $request->input(
                    'after_id',
                    0
                );

            $chat =
                $chatService->obtenerChatVecinal();

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $mensajes =
                $chat->mensajes()
                    ->with(['usuario', 'replyTo.usuario']) // 💬 Cargamos la relación en mensajes nuevos también
                    ->where(
                        'id',
                        '>',
                        $afterId
                    )
                    ->orderBy('id')
                    ->limit(100)
                    ->get();

            $personasEnLinea =
                $chatPresenceService->contarUsuariosEnLinea(
                    $chat
                );

            return response()->json([
                'success' => true,

                'personas_en_linea' =>
                    $personasEnLinea,

                'mensajes' =>
                    $chatResponseService->mensajes(
                        $mensajes
                    ),

                'usuarios_escribiendo' =>
                    $chatTypingService->obtenerUsuariosEscribiendo(
                        $chat,
                        $chatPresenceService->obtenerPersonasChat(
                            $chat
                        )
                    ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudieron obtener los mensajes nuevos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
 * Agregar o quitar una reacción de un mensaje.
 *
 * Si el usuario ya tiene esa misma reacción:
 *      → se elimina.
 *
 * Si no la tiene:
 *      → se agrega.
 */
public function reaccion(
    Request $request,
    ChatService $chatService
): JsonResponse {
    try {
        $request->validate([
            'emoji' => [
                'required',
                'string',
                'max:20',
                'in:👍,❤️,😂,😮,😢,🙏',
            ],
        ]);

        $usuario = $request->user();

        $chat = $chatService->obtenerChatVecinal();

        /*
         * Verificar que el usuario pertenece al Chat Vecinal.
         */
        if (!$chatService->usuarioPerteneceAlChat($usuario)) {
            $chatService->agregarUsuario($usuario);
        }

        /*
         * Buscar el mensaje.
         */
        $mensaje = ChatMessage::query()
            ->where('id', $request->route('chatMessage'))
            ->where('conversation_id', $chat->id)
            ->first();

        if (!$mensaje) {
            return response()->json([
                'success' => false,
                'message' => 'El mensaje no existe en el Chat Vecinal.',
            ], 404);
        }

        $emoji = $request->input('emoji');

        /*
         * Buscar si el usuario ya tiene esta reacción.
         */
    $reaccionActual = ChatMessageReaction::query()
    ->where('chat_message_id', $mensaje->id)
    ->where('user_id', $usuario->id)
    ->first();

if ($reaccionActual) {
    if ($reaccionActual->emoji === $emoji) {
        // El usuario vuelve a pulsar la misma reacción.
        // Se elimina.
        $reaccionActual->delete();
        $accion = 'eliminada';
    } else {
        // El usuario eligió otra reacción.
        // Se reemplaza la anterior.
        $reaccionActual->update([
            'emoji' => $emoji,
        ]);
        $accion = 'reemplazada';
    }
} else {
    // El usuario todavía no tenía ninguna reacción.
    ChatMessageReaction::create([
        'chat_message_id' => $mensaje->id,
        'user_id' => $usuario->id,
        'emoji' => $emoji,
    ]);

    $accion = 'agregada';
}

        /*
         * Obtener todas las reacciones agrupadas por emoji.
         */
        $reacciones = ChatMessageReaction::query()
            ->where('chat_message_id', $mensaje->id)
            ->selectRaw('emoji, COUNT(*) as cantidad')
            ->groupBy('emoji')
            ->orderBy('emoji')
            ->get()
            ->map(function ($reaccion) use ($mensaje, $usuario) {

                $yo = ChatMessageReaction::query()
                    ->where('chat_message_id', $mensaje->id)
                    ->where('user_id', $usuario->id)
                    ->where('emoji', $reaccion->emoji)
                    ->exists();

                return [
                    'emoji' => $reaccion->emoji,
                    'cantidad' => (int) $reaccion->cantidad,
                    'yo' => $yo,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'accion' => $accion,
            'mensaje_id' => $mensaje->id,
            'reacciones' => $reacciones,
        ]);

    } catch (Throwable $e) {

        return response()->json([
            'success' => false,
            'message' => 'No se pudo procesar la reacción.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

/**
 * Obtener las reacciones de un mensaje.
 */
public function reacciones(
    Request $request,
    ChatService $chatService
): JsonResponse {
    try {

        $usuario = $request->user();

        $chat = $chatService->obtenerChatVecinal();

        /*
         * Verificar que el usuario pertenece al Chat Vecinal.
         */
        if (!$chatService->usuarioPerteneceAlChat($usuario)) {
            $chatService->agregarUsuario($usuario);
        }

        /*
         * Buscar el mensaje dentro del Chat Vecinal.
         */
        $mensaje = ChatMessage::query()
            ->where('id', $request->route('chatMessage'))
            ->where('conversation_id', $chat->id)
            ->first();

        if (!$mensaje) {
            return response()->json([
                'success' => false,
                'message' => 'El mensaje no existe en el Chat Vecinal.',
            ], 404);
        }

        /*
         * Obtener todas las reacciones.
         */
        $reacciones = ChatMessageReaction::query()
            ->where('chat_message_id', $mensaje->id)
            ->selectRaw('emoji, COUNT(*) as cantidad')
            ->groupBy('emoji')
            ->orderBy('emoji')
            ->get()
            ->map(function ($reaccion) use ($mensaje, $usuario) {

                $yo = ChatMessageReaction::query()
                    ->where('chat_message_id', $mensaje->id)
                    ->where('user_id', $usuario->id)
                    ->where('emoji', $reaccion->emoji)
                    ->exists();

                return [
                    'emoji' => $reaccion->emoji,
                    'cantidad' => (int) $reaccion->cantidad,
                    'yo' => $yo,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'mensaje_id' => $mensaje->id,
            'reacciones' => $reacciones,
        ]);

    } catch (Throwable $e) {

        return response()->json([
            'success' => false,
            'message' => 'No se pudieron obtener las reacciones.',
            'error' => $e->getMessage(),
        ], 500);
    }
}
}