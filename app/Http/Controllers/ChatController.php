<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\ChatService;
use App\Services\chat\ChatPresenceService;
use App\Services\chat\ChatTypingService;
use App\Services\chat\ChatAttachmentService;
use App\Services\chat\ChatMessageService;
use App\Services\chat\ChatSigiInterventionService;
use App\Services\chat\ChatResponseService;
use App\Services\ChatSigiService;
use App\Services\SigiEventoService;
use App\Services\SigiIntervencionService;
use App\Services\SigiService;
use App\Services\ZoeModeracionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Mostrar el Chat Vecinal.
     */
    public function index(
        ChatService $chatService,
        ChatPresenceService $chatPresenceService
    ): View {
        $usuario = request()->user();

        $chatPresenceService->actualizarPresencia(
            $usuario
        );

        $chat = $chatService->obtenerChatVecinal();

        $mensajes = $chat->mensajes()
            ->with('usuario')
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        $usuarios =
            $chatService->obtenerPersonasChat();

        $resumen =
            $chatService->obtenerResumenPresencia();

        return view('chat.index', [
            'chat' =>
                $chat,

            'mensajes' =>
                $mensajes,

            'usuarios' =>
                $usuarios,

            'personas' =>
                $resumen['total'],

            'personasEnLinea' =>
                $resumen['en_linea'],
        ]);
    }

    /**
     * Registrar la actividad/presencia del usuario.
     */
    public function presencia(
        Request $request,
        ChatService $chatService,
        ChatPresenceService $chatPresenceService,
        ChatTypingService $chatTypingService
    ): JsonResponse {

        $usuario =
            $request->user();

        $chat =
            $chatService->obtenerChatVecinal();

        return response()->json([
            'success' =>
                true,

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
        ZoeModeracionService $zoeService
    ): JsonResponse {
        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

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
        ]);

        $usuario =
            $request->user();

        /*
        |--------------------------------------------------------------------------
        | Obtener Chat Vecinal
        |--------------------------------------------------------------------------
        */

        $chat =
            $chatService->obtenerChatVecinal();

        /*
        |--------------------------------------------------------------------------
        | Actualizar presencia
        |--------------------------------------------------------------------------
        */

        $chatPresenceService->actualizarPresencia(
            $usuario
        );

        /*
        |--------------------------------------------------------------------------
        | Verificar pertenencia
        |--------------------------------------------------------------------------
        */

        $pertenece =
            $chatService->usuarioPerteneceAlChat(
                $usuario
            );

        if (!$pertenece) {
            $chatService->agregarUsuario(
                $usuario
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Contenido del mensaje y Moderación ZOE
        |--------------------------------------------------------------------------
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

        // ZOE evalúa el texto (ya sea un mensaje directo o la leyenda de un archivo adjunto)
        if (!empty($texto)) {
            $resultadoModeracion = $zoeService->procesarMensaje($chat->id, $usuario, $texto);

            if ($resultadoModeracion['bloquear_mensaje']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultadoModeracion['message'] ?? 'Tu mensaje ha sido bloqueado por las normas de convivencia de ZOE.',
                    'detalle' => $resultadoModeracion['accion'],
                    'es_bloqueo_permanente' => $resultadoModeracion['accion'] === 'bloqueo'
                ], 422);
            }
        }

        $contenidoMensaje =
            $texto;

        // Si hay archivo adjunto, el servicio procesa el archivo integrando también el texto (leyenda)
        if ($archivoAdjunto) {
            $contenidoMensaje =
                $chatAttachmentService->procesar(
                    $archivoAdjunto,
                    $texto
                );
        }

        $mensaje =
            $chatMessageService->crearMensaje(
                $chat,
                $usuario,
                $contenidoMensaje
            );

        /*
        |--------------------------------------------------------------------------
        | Detectar @zoe
        |--------------------------------------------------------------------------
        */

        $textoMensaje =
            trim(
                $mensaje->mensaje
            );

        /*
        |--------------------------------------------------------------------------
        | Los archivos no pasan por SIGI
        |--------------------------------------------------------------------------
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
                'success' =>
                    true,

                'mensaje' =>
                    $chatResponseService->mensaje(
                        $mensaje
                    ),

                'sigi_analisis' => [
                    'analizado' =>
                        false,

                    'categoria' =>
                        null,

                    'intencion' =>
                        null,

                    'prioridad' =>
                        null,

                    'contextual' =>
                        false,

                    'debe_intervenir' =>
                        false,
                ],

                'sigi_evento' =>
                    null,

                'sigi_decision' =>
                    null,

                'sigi' =>
                    null,
            ]);
        }

        $mencionaSigi =
            preg_match(
                '/(^|\s)@?zoe\b/i',
                $textoMensaje
            ) === 1;

        /*
        |--------------------------------------------------------------------------
        | Variables de SIGI
        |--------------------------------------------------------------------------
        */

        $analisisSigi = [];

        $resultadoEvento = [
            'evento' => null,
        ];

        $decisionSigi = null;

        $mensajeSigi = null;

        /*
        |--------------------------------------------------------------------------
        | @zoe — CONSULTA EXPLÍCITA
        |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | SIGI — INTERVENCIÓN CONTEXTUAL AUTOMÁTICA
            |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Respuesta JSON
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'success' =>
                true,

            'mensaje' =>
                $chatResponseService->mensaje(
                    $mensaje
                ),

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

            'sigi_decision' =>
                $decisionSigi,

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

                        'usuario' =>
                            null,
                    ]
                    : null,
        ]);
    }

    /**
     * Actualizar el estado temporal de escritura del usuario.
     */
    public function escribiendo(
        Request $request,
        ChatService $chatService,
        ChatTypingService $chatTypingService
    ): JsonResponse {
        $request->validate([
            'escribiendo' => [
                'required',
                'boolean',
            ],
        ]);

        $usuario = $request->user();

        $chat = $chatService->obtenerChatVecinal();

        if (!$chatService->usuarioPerteneceAlChat(
            $usuario
        )) {
            $chatService->agregarUsuario(
                $usuario
            );
        }

        $chatTypingService->actualizarEstado(
            $chat,
            $usuario,
            $request->boolean('escribiendo')
        );

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Obtener mensajes nuevos del Chat Vecinal.
     */
    public function nuevos(
        Request $request,
        ChatService $chatService,
        ChatTypingService $chatTypingService,
        ChatPresenceService $chatPresenceService,
        ChatResponseService $chatResponseService
    ): JsonResponse {
        $request->validate([
            'after_id' => [
                'nullable',
                'min:0',
                'integer',
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

        $mensajes =
            $chat->mensajes()
                ->with('usuario')
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
            'success' =>
                true,

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
    }
}