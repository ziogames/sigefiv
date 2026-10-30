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
use App\Services\ZoeN8nService;
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
                ->with(['usuario', 'replyTo.usuario'])
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();

            $usuarioChat = $chat->usuarios()
                ->where('users.id', $usuario->id)
                ->first();

            $ultimoLeidoMessageId =
                $usuarioChat?->pivot?->ultimo_leido_message_id;

            $desdeMessageId = $ultimoLeidoMessageId ?? 0;

            $primerNoLeidoId = null;
            $mensajesNoLeidos = 0;

            $primerNoLeido = $chat->mensajes()
                ->where('id', '>', $desdeMessageId)
                ->where('user_id', '!=', $usuario->id)
                ->orderBy('id')
                ->first();

            if ($primerNoLeido) {
                $primerNoLeidoId = $primerNoLeido->id;

                $mensajesNoLeidos = $chat->mensajes()
                    ->where('id', '>', $desdeMessageId)
                    ->where('user_id', '!=', $usuario->id)
                    ->count();
            }

            $resumen = $chatService->obtenerResumenPresencia();

            return response()->json([
                'success' => true,
                'chat' => [
                    'id' => $chat->id,
                    'nombre' => $chat->nombre ?? 'Chat Vecinal',
                ],
                'personas' => $resumen['total'] ?? 0,
                'personas_en_linea' => $resumen['en_linea'] ?? 0,
                'mensajes' => $chatResponseService->mensajes($mensajes),
                'ultimo_leido_message_id' => $ultimoLeidoMessageId,
                'primer_no_leido_id' => $primerNoLeidoId,
                'mensajes_no_leidos' => $mensajesNoLeidos,
                'usuarios_escribiendo' => $chatTypingService->obtenerUsuariosEscribiendo(
                    $chat,
                    $chatPresenceService->obtenerPersonasChat($chat)
                ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo cargar el Chat Vecinal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Enviar un mensaje al Chat Vecinal.
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
        ZoeModeracionService $zoeService,
        ZoeN8nService $zoeN8nService
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
                    'exists:chat_messages,id',
                ],
            ]);

            $usuario = $request->user();
            $chat = $chatService->obtenerChatVecinal();

            $chatPresenceService->actualizarPresencia($usuario);

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $texto = trim((string) $request->input('mensaje', ''));
            $archivoAdjunto = $request->file('archivo');
            $replyToId = $request->input('reply_to_id');

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

            $contenidoMensaje = $texto;

            if ($archivoAdjunto) {
                $contenidoMensaje = $chatAttachmentService->procesar($archivoAdjunto, $texto);
            }

            $mensaje = $chatMessageService->crearMensaje(
                $chat,
                $usuario,
                $contenidoMensaje,
                $replyToId
            );

            $datosAdjunto = json_decode($mensaje->mensaje, true);
            $esArchivoAdjunto = is_array($datosAdjunto) && ($datosAdjunto['tipo'] ?? null) === 'archivo';

            if ($esArchivoAdjunto) {
                return response()->json([
                    'success' => true,
                    'mensaje' => $chatResponseService->mensaje($mensaje),
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

            $textoMensaje = trim($mensaje->mensaje);

            $mencionaSigi = preg_match(
                '/(^|\s)@?zoe\b/i',
                $textoMensaje
            ) === 1;

            $analisisSigi = [];
            $resultadoEvento = ['evento' => null];
            $decisionSigi = null;
            $mensajeSigi = null;

            /*
             * 🤖 @zoe — CONSULTAS VÍA N8N
             */
            if ($mencionaSigi) {
                $consultaIa = preg_replace('/(^|\s)@?zoe(\s+ia)?\b/i', ' ', $textoMensaje);
                $consultaIa = trim(preg_replace('/\s+/u', ' ', (string) $consultaIa));

                if (empty($consultaIa)) {
                    $consultaIa = "Hola";
                }

                $respuestaIa = $zoeN8nService->consultar(
                    $consultaIa,
                    (int) $usuario->id,
                    $usuario->name
                );

                if ($respuestaIa !== null) {
                    $textoBase = is_array($respuestaIa)
                        ? ($respuestaIa['texto'] ?? '')
                        : (string) $respuestaIa;

                    $textoFinal = '🤖 ' . trim($textoBase);

                    // Extraer array de emprendimientos si n8n lo devolvió estructurado
                    $listaEmprendimientos = null;
                    if (is_array($respuestaIa)) {
                        $extra = $respuestaIa['extra'] ?? null;
                        if (is_array($extra)) {
                            $listaEmprendimientos = $extra['emprendimientos']
                                ?? (isset($extra[0]) ? $extra : null);
                        }
                    }

                    // Anexar líneas con viñetas para que Android genere las tarjetas interactivas
  if (is_array($listaEmprendimientos) && !empty($listaEmprendimientos)) {
    foreach ($listaEmprendimientos as $emp) {
        $nombre = $emp['nombre'] ?? $emp['name'] ?? 'Emprendimiento Vecinal';
        $categoria = $emp['categoria'] ?? $emp['modalidad'] ?? 'General';
        $descripcion = $emp['descripcion'] ?? $emp['description'] ?? '';
        $contacto = $emp['telefono'] ?? $emp['phone'] ?? '';

        // 📸 Extracción profunda de la URL de la foto (soporta imagen directa o array de fotos)
        $imagen = $emp['imagen'] 
            ?? $emp['imagen_url'] 
            ?? ($emp['fotos'][0]['url'] ?? null) 
            ?? ($emp['photos'][0]['url'] ?? null) 
            ?? '';

        $zona = $emp['zona_atencion'] ?? $emp['attention_zone'] ?? '';
        $horario = $emp['horario'] ?? $emp['schedule'] ?? '';

        $textoFinal .= "\n• {$nombre} | {$categoria} | {$descripcion} | {$contacto} | {$imagen} | {$zona} | {$horario}";
    }
}
                    $mensajeSigi = $chatSigiService->crearMensajeSigi(
                        $chat,
                        [
                            'success' => true,
                            'tipo' => 'texto',
                            'resultado' => null,
                            'mensaje' => $textoFinal,
                        ]
                    );
                } else {
                    $mensajeSigi = $chatSigiService->crearMensajeSigi(
                        $chat,
                        [
                            'success' => false,
                            'tipo' => 'texto',
                            'resultado' => null,
                            'mensaje' => '🤖 No pude comunicarme con el servicio de inteligencia artificial.',
                        ]
                    );
                }
            } else {
                $resultadoSigi = $chatSigiInterventionService->procesar($chat, $mensaje);

                $analisisSigi = $resultadoSigi['analisis'];
                $resultadoEvento = $resultadoSigi['evento'];
                $decisionSigi = $resultadoSigi['decision'];
                $mensajeSigi = $resultadoSigi['mensaje_sigi'];
            }

            return response()->json([
                'success' => true,
                'mensaje' => $chatResponseService->mensaje($mensaje),
                'sigi_analisis' => [
                    'analizado' => $analisisSigi['analizado'] ?? false,
                    'categoria' => $analisisSigi['categoria'] ?? null,
                    'intencion' => $analisisSigi['intencion'] ?? null,
                    'prioridad' => $analisisSigi['prioridad'] ?? null,
                    'contextual' => $analisisSigi['contextual'] ?? false,
                    'debe_intervenir' => $analisisSigi['debe_intervenir'] ?? false,
                ],
                'sigi_evento' => $resultadoEvento['evento']
                    ? [
                        'id' => $resultadoEvento['evento']->id,
                        'categoria' => $resultadoEvento['evento']->categoria,
                        'estado' => $resultadoEvento['evento']->estado,
                        'total_reportes' => $resultadoEvento['evento']->total_reportes,
                        'reportes_problema' => $resultadoEvento['evento']->reportes_problema,
                        'reportes_resueltos' => $resultadoEvento['evento']->reportes_resueltos,
                        'usuarios_afectados' => $resultadoEvento['evento']->cantidadUsuariosAfectados(),
                        'usuarios_restablecidos' => $resultadoEvento['evento']->cantidadUsuariosRestablecidos(),
                    ]
                    : null,
                'sigi_decision' => $decisionSigi,
                'sigi' => $mensajeSigi
                    ? [
                        'id' => $mensajeSigi->id,
                        'mensaje' => $mensajeSigi->mensaje,
                        'tipo' => $mensajeSigi->tipo,
                        'editado' => $mensajeSigi->editado,
                        'created_at' => $mensajeSigi->created_at?->format('H:i'),
                        'usuario' => null,
                    ]
                    : null,
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo enviar el mensaje.',
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
            $usuario = $request->user();
            $chat = $chatService->obtenerChatVecinal();

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $chatPresenceService->actualizarPresencia($usuario);

            return response()->json([
                'success' => true,
                ...$chatPresenceService->obtenerDatosPresencia($chat, $usuario),
                'usuarios_escribiendo' => $chatTypingService->obtenerUsuariosEscribiendo(
                    $chat,
                    $chatPresenceService->obtenerPersonasChat($chat)
                ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar la presencia.',
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

            $usuario = $request->user();
            $chat = $chatService->obtenerChatVecinal();

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
                'message' => 'No se pudo actualizar el estado de escritura.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener mensajes nuevos.
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

            $usuario = $request->user();
            $chatPresenceService->actualizarPresencia($usuario);

            $afterId = (int) $request->input('after_id', 0);
            $chat = $chatService->obtenerChatVecinal();

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $mensajes = $chat->mensajes()
                ->with(['usuario', 'replyTo.usuario'])
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit(100)
                ->get();

            $personasEnLinea = $chatPresenceService->contarUsuariosEnLinea($chat);

            $usuarioChat = $chat->usuarios()
                ->where('users.id', $usuario->id)
                ->first();

            $ultimoLeidoMessageId = $usuarioChat?->pivot?->ultimo_leido_message_id;
            $desdeMessageId = $ultimoLeidoMessageId ?? 0;

            $primerNoLeidoId = null;
            $mensajesNoLeidos = 0;

            $primerNoLeido = $chat->mensajes()
                ->where('id', '>', $desdeMessageId)
                ->where('user_id', '!=', $usuario->id)
                ->orderBy('id')
                ->first();

            if ($primerNoLeido) {
                $primerNoLeidoId = $primerNoLeido->id;

                $mensajesNoLeidos = $chat->mensajes()
                    ->where('id', '>', $desdeMessageId)
                    ->where('user_id', '!=', $usuario->id)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'personas_en_linea' => $personasEnLinea,
                'mensajes' => $chatResponseService->mensajes($mensajes),
                'ultimo_leido_message_id' => $ultimoLeidoMessageId,
                'primer_no_leido_id' => $primerNoLeidoId,
                'mensajes_no_leidos' => $mensajesNoLeidos,
                'usuarios_escribiendo' => $chatTypingService->obtenerUsuariosEscribiendo(
                    $chat,
                    $chatPresenceService->obtenerPersonasChat($chat)
                ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los mensajes nuevos.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar hasta qué mensaje ha leído el usuario.
     */
    public function marcarLeido(
        Request $request,
        ChatService $chatService
    ): JsonResponse {
        try {
            $request->validate([
                'mensaje_id' => [
                    'required',
                    'integer',
                    'min:1',
                ],
            ]);

            $usuario = $request->user();
            $chat = $chatService->obtenerChatVecinal();

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            $mensaje = ChatMessage::query()
                ->where('id', $request->integer('mensaje_id'))
                ->where('conversation_id', $chat->id)
                ->first();

            if (!$mensaje) {
                return response()->json([
                    'success' => false,
                    'message' => 'El mensaje no existe en el Chat Vecinal.',
                ], 404);
            }

            $usuarioChat = $chat->usuarios()
                ->where('users.id', $usuario->id)
                ->first();

            $ultimoLeidoActual = $usuarioChat?->pivot?->ultimo_leido_message_id;

            if ($ultimoLeidoActual !== null && $mensaje->id <= (int) $ultimoLeidoActual) {
                return response()->json([
                    'success' => true,
                    'mensaje_id' => (int) $ultimoLeidoActual,
                    'message' => 'La posición de lectura ya estaba más adelante.',
                ]);
            }

            $chat->usuarios()->updateExistingPivot(
                $usuario->id,
                [
                    'ultimo_leido_message_id' => $mensaje->id,
                    'ultimo_leido_at' => $mensaje->created_at,
                ]
            );

            return response()->json([
                'success' => true,
                'mensaje_id' => $mensaje->id,
                'message' => 'Mensaje marcado como leído.',
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el estado de lectura.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Agregar o quitar una reacción de un mensaje.
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
                    'max:50',
                    'in:👍,❤️,😂,😮,😢,🙏',
                ],
            ]);

            $usuario = $request->user();
            $chat = $chatService->obtenerChatVecinal();

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

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

            $reaccionActual = ChatMessageReaction::query()
                ->where('chat_message_id', $mensaje->id)
                ->where('user_id', $usuario->id)
                ->first();

            if ($reaccionActual) {
                if ($reaccionActual->emoji === $emoji) {
                    $reaccionActual->delete();
                    $accion = 'eliminada';
                } else {
                    $reaccionActual->update([
                        'emoji' => $emoji,
                    ]);
                    $accion = 'reemplazada';
                }
            } else {
                ChatMessageReaction::create([
                    'chat_message_id' => $mensaje->id,
                    'user_id' => $usuario->id,
                    'emoji' => $emoji,
                ]);
                $accion = 'agregada';
            }

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

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

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