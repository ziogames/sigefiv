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

            $chatPresenceService->actualizarPresencia($usuario);

            if (!$chatService->usuarioPerteneceAlChat($usuario)) {
                $chatService->agregarUsuario($usuario);
            }

            // withTrashed() conserva mensajes borrados para "Este mensaje fue eliminado"
            // where('bloqueado_moderacion', false) oculta las evidencias forenses a los vecinos
            $mensajes = $chat->mensajes()
                ->withTrashed()
                ->where('bloqueado_moderacion', false)
                ->with(['usuario', 'replyTo.usuario', 'mensajePadre.usuario', 'reacciones'])
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->reverse()
                ->values();

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
                ->where('bloqueado_moderacion', false)
                ->orderBy('id')
                ->first();

            if ($primerNoLeido) {
                $primerNoLeidoId = $primerNoLeido->id;

                $mensajesNoLeidos = $chat->mensajes()
                    ->where('id', '>', $desdeMessageId)
                    ->where('user_id', '!=', $usuario->id)
                    ->where('bloqueado_moderacion', false)
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

            // 🛡️ MODERACIÓN ZOE (3 Strikes + Detección IA)
            if ($texto !== '') {
                $resultadoModeracion = $zoeService->procesarMensaje($chat->id, $usuario, $texto);

                if ($resultadoModeracion['bloquear_mensaje'] ?? false) {
                    // Guarda la evidencia oculta en la base de datos
                    ChatMessage::create([
                        'conversation_id'      => $chat->id,
                        'user_id'              => $usuario->id,
                        'mensaje'              => $texto,
                        'bloqueado_moderacion' => true,
                        'motivo_moderacion'    => $resultadoModeracion['detalle_motivo'] ?? 'Lenguaje ofensivo o infracción a normas',
                        'reply_to_id'          => $replyToId,
                        'created_at'           => now(),
                    ]);

                    $esBloqueo = ($resultadoModeracion['accion'] ?? '') === 'bloqueo';

                    return response()->json([
                        'success'               => false,
                        'message'               => $resultadoModeracion['message'] ?? 'Tu mensaje ha sido bloqueado por las normas de convivencia de ZOE.',
                        'detalle'               => $resultadoModeracion['accion'] ?? 'advertencia',
                        'strikes'               => $resultadoModeracion['strikes'] ?? $usuario->advertencias_count,
                        'es_bloqueo_permanente' => $esBloqueo,
                    ], $esBloqueo ? 403 : 422);
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
                    $esEstadisticaGrafica = false;

                    // 1. Verificar si n8n devolvió estadísticas estructuradas (Canvas)
                    if (is_array($respuestaIa)) {
                        $extra = $respuestaIa['extra'] ?? null;
                        if (
                            is_array($extra) &&
                            isset($extra['tipo']) &&
                            in_array($extra['tipo'], [
                                'ingresos_vs_egresos',
                                'evolucion_saldo',
                                'egresos_por_categoria',
                                'resumen_financiero'
                            ])
                        ) {
                            if (empty($extra['titulo']) && !empty($textoBase)) {
                                $extra['titulo'] = $textoBase;
                            }
                            $textoFinal = json_encode($extra, JSON_UNESCAPED_UNICODE);
                            $esEstadisticaGrafica = true;
                        }
                    }

                    // 2. Si no es estadística gráfica, verificar si es lista de emprendimientos
                 // 2. Si no es estadística gráfica, verificar si es lista de emprendimientos
                    if (!$esEstadisticaGrafica && is_array($respuestaIa)) {
                        $extra = $respuestaIa['extra'] ?? null;
                        $listaEmprendimientos = null;

                        if (is_array($extra)) {
                            $listaEmprendimientos = $extra['emprendimientos']
                                ?? (isset($extra[0]) ? $extra : null);
                        }

                        if (is_array($listaEmprendimientos) && !empty($listaEmprendimientos)) {
                            foreach ($listaEmprendimientos as $emp) {
                                $nombre = $emp['nombre'] ?? $emp['name'] ?? 'Emprendimiento Vecinal';
                                $categoria = $emp['categoria'] ?? $emp['modalidad'] ?? 'General';
                                $descripcion = $emp['descripcion'] ?? $emp['description'] ?? '';
                                $contacto = $emp['telefono'] ?? $emp['phone'] ?? '';

                                $imagen = $emp['imagen']
                                    ?? $emp['imagen_url']
                                    ?? ($emp['fotos'][0]['url'] ?? null)
                                    ?? ($emp['photos'][0]['url'] ?? null)
                                    ?? '';

                                // Si la imagen es una ruta relativa local, convertirla a URL completa accesible por Android
                                if (!empty($imagen) && !str_starts_with($imagen, 'http')) {
                                    $imagen = url('storage/' . ltrim($imagen, '/'));
                                }

                                $zona = $emp['zona_atencion'] ?? $emp['attention_zone'] ?? '';
                                $horario = $emp['horario'] ?? $emp['schedule'] ?? '';

                                // 🎯 LA CLAVE: Agregar el punto • al inicio para que Android active ChatUIComponent.Emprendimiento
                                $textoFinal .= "\n• {$nombre} | {$categoria} | {$descripcion} | {$contacto} | {$imagen} | {$zona} | {$horario}";
                            }
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
     * PUT /api/chat/mensajes/{id}
     * Editar un mensaje propio (límite de 15 minutos).
     */
    public function update(
        Request $request,
        int $id,
        ChatService $chatService,
        ChatResponseService $chatResponseService
    ): JsonResponse {
        try {
            $request->validate([
                'mensaje' => 'required|string|max:5000',
            ]);

            $usuario = $request->user();
            $chat = $chatService->obtenerChatVecinal();

            $mensaje = ChatMessage::where('id', $id)
                ->where('conversation_id', $chat->id)
                ->firstOrFail();

            if ((int) $mensaje->user_id !== (int) $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar este mensaje.',
                ], 403);
            }

            if ($mensaje->created_at->diffInMinutes(now()) > 15) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tiempo límite para editar este mensaje (15 minutos) ha expirado.',
                ], 422);
            }

            $mensaje->update([
                'mensaje' => trim($request->input('mensaje')),
                'editado' => true,
            ]);

            return response()->json([
                'success' => true,
                'mensaje' => $chatResponseService->mensaje(
                    $mensaje->fresh(['usuario', 'replyTo.usuario', 'mensajePadre.usuario', 'reacciones'])
                ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo editar el mensaje.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/chat/mensajes/{id}
     * Eliminar mensaje (autor: hasta 48h / moderación de directiva: sin límite).
     */
    public function destroy(
        Request $request,
        int $id,
        ChatService $chatService,
        ChatResponseService $chatResponseService
    ): JsonResponse {
        try {
            $usuario = $request->user();
            $chat = $chatService->obtenerChatVecinal();

            $mensaje = ChatMessage::where('id', $id)
                ->where('conversation_id', $chat->id)
                ->firstOrFail();

            $esAutor = (int) $mensaje->user_id === (int) $usuario->id;
            $esDirectiva = !is_null($usuario->cargo_id);

            if (!$esAutor && !$esDirectiva) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este mensaje.',
                ], 403);
            }

            if ($esAutor && !$esDirectiva && $mensaje->created_at->diffInHours(now()) > 48) {
                return response()->json([
                    'success' => false,
                    'message' => 'El plazo para eliminar tu mensaje ha vencido.',
                ], 422);
            }

            $mensaje->deleted_by = $usuario->id;
            $mensaje->save();
            $mensaje->delete(); // Soft delete activa deleted_at

            return response()->json([
                'success' => true,
                'mensaje' => $chatResponseService->mensaje(
                    $mensaje->fresh(['usuario', 'replyTo.usuario', 'mensajePadre.usuario', 'reacciones'])
                ),
            ]);

        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el mensaje.',
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

            // withTrashed() para mensajes eliminados, sin exponer las evidencias de moderación
            $mensajes = $chat->mensajes()
                ->withTrashed()
                ->where('bloqueado_moderacion', false)
                ->with(['usuario', 'replyTo.usuario', 'mensajePadre.usuario', 'reacciones'])
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
                ->where('bloqueado_moderacion', false)
                ->orderBy('id')
                ->first();

            if ($primerNoLeido) {
                $primerNoLeidoId = $primerNoLeido->id;

                $mensajesNoLeidos = $chat->mensajes()
                    ->where('id', '>', $desdeMessageId)
                    ->where('user_id', '!=', $usuario->id)
                    ->where('bloqueado_moderacion', false)
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