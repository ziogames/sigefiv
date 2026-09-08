<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asamblea;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AsambleaController extends Controller
{
    /**
     * Listar asambleas.
     *
     * GET /api/asambleas
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $asambleas = Asamblea::with(['creador', 'agendas'])
                ->orderByDesc('fecha')
                ->orderByDesc('hora')
                ->get();

            return response()->json([
                'success' => true,
                'asambleas' => $asambleas->map(function (Asamblea $asamblea) {
                    return $this->formatearAsamblea($asamblea);
                })->values(),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las asambleas.',
                'error' => $e->getMessage(),
                'asambleas' => [],
            ], 500);
        }
    }

    /**
     * Mostrar una asamblea.
     *
     * GET /api/asambleas/{asamblea}
     */
    public function show(
        Asamblea $asamblea
    ): JsonResponse {
        try {

            $asamblea->load([
                'creador',
                'agendas',
            ]);

            return response()->json([
                'success' => true,
                'asamblea' => $this->formatearAsamblea(
                    $asamblea
                ),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener la asamblea.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear una asamblea.
     *
     * POST /api/asambleas
     */
    public function store(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'tipo' => [
                'required',
                'string',
                'max:30',
            ],

            'titulo' => [
                'required',
                'string',
                'max:255',
            ],

            'convoca' => [
                'required',
                'string',
                'max:255',
            ],

            'sector' => [
                'nullable',
                'string',
                'max:100',
            ],

            'grupo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manzana' => [
                'nullable',
                'string',
                'max:50',
            ],

            'lote' => [
                'nullable',
                'string',
                'max:50',
            ],

            'fecha' => [
                'required',
                'date',
            ],

            'hora' => [
                'nullable',
                'date_format:H:i',
            ],

            'primera_citacion' => [
                'required',
                'date_format:H:i',
            ],

            'segunda_citacion' => [
                'nullable',
                'date_format:H:i',
                'after:primera_citacion',
            ],

            'lugar' => [
                'required',
                'string',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'importancia' => [
                'required',
                'in:normal,importante,urgente',
            ],

            'agenda' => [
                'nullable',
                'array',
            ],

            'agenda.*' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'plantilla_citacion' => [
                'required',
                'integer',
                'between:1,7',
            ],
        ]);

        try {

            $asamblea = DB::transaction(
                function () use ($datos) {

                    $agenda =
                        $datos['agenda'] ?? [];

                    unset(
                        $datos['agenda']
                    );

                    $datos['estado'] =
                        'borrador';

                    $datos['created_by'] =
                        auth()->id();

                    $datos['alerta_enviada'] =
                        false;

                    $datos['alerta_enviada_at'] =
                        null;

                    $asamblea =
                        Asamblea::create(
                            $datos
                        );

                    $numero = 1;

                    foreach ($agenda as $punto) {

                        if (
                            $punto === null ||
                            trim($punto) === ''
                        ) {
                            continue;
                        }

                        $asamblea
                            ->agendas()
                            ->create([
                                'numero' =>
                                    $numero,

                                'descripcion' =>
                                    trim($punto),
                            ]);

                        $numero++;
                    }

                    return $asamblea;
                }
            );

            $asamblea->load([
                'creador',
                'agendas',
            ]);

            return response()->json([
                'success' => true,
                'message' =>
                    'Asamblea creada correctamente.',

                'asamblea' =>
                    $this->formatearAsamblea(
                        $asamblea
                    ),
            ], 201);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    'No se pudo crear la asamblea.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Actualizar una asamblea.
     *
     * PUT /api/asambleas/{asamblea}
     */
    public function update(
        Request $request,
        Asamblea $asamblea
    ): JsonResponse {
        $datos = $request->validate([
            'tipo' => [
                'required',
                'string',
                'max:30',
            ],

            'titulo' => [
                'required',
                'string',
                'max:255',
            ],

            'convoca' => [
                'required',
                'string',
                'max:255',
            ],

            'sector' => [
                'nullable',
                'string',
                'max:100',
            ],

            'grupo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manzana' => [
                'nullable',
                'string',
                'max:50',
            ],

            'lote' => [
                'nullable',
                'string',
                'max:50',
            ],

            'fecha' => [
                'required',
                'date',
            ],

            'hora' => [
                'nullable',
                'date_format:H:i',
            ],

            'primera_citacion' => [
                'required',
                'date_format:H:i',
            ],

            'segunda_citacion' => [
                'nullable',
                'date_format:H:i',
                'after:primera_citacion',
            ],

            'lugar' => [
                'required',
                'string',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'importancia' => [
                'required',
                'in:normal,importante,urgente',
            ],

            'agenda' => [
                'nullable',
                'array',
            ],

            'agenda.*' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'plantilla_citacion' => [
                'required',
                'integer',
                'between:1,7',
            ],
        ]);

        try {

            /*
             * Solo las asambleas en borrador
             * pueden modificarse.
             */
            if (
                $asamblea->estado !==
                'borrador'
            ) {

                return response()->json([
                    'success' => false,

                    'message' =>
                        'Las asambleas publicadas o canceladas no pueden modificarse.',
                ], 422);
            }

            /*
             * Una convocatoria que ya haya sido
             * enviada tampoco puede modificarse.
             */
            if (
                $asamblea->alerta_enviada
            ) {

                return response()->json([
                    'success' => false,

                    'message' =>
                        'Esta asamblea ya tiene una convocatoria enviada y no puede modificarse.',
                ], 422);
            }

            $agenda =
                $datos['agenda'] ?? [];

            unset(
                $datos['agenda']
            );

            DB::transaction(
                function () use (
                    $asamblea,
                    $datos,
                    $agenda
                ) {

                    $asamblea->update(
                        $datos
                    );

                    $asamblea
                        ->agendas()
                        ->delete();

                    $numero = 1;

                    foreach (
                        $agenda as $punto
                    ) {

                        if (
                            $punto === null ||
                            trim($punto) === ''
                        ) {
                            continue;
                        }

                        $asamblea
                            ->agendas()
                            ->create([
                                'numero' =>
                                    $numero,

                                'descripcion' =>
                                    trim($punto),
                            ]);

                        $numero++;
                    }
                }
            );

            $asamblea->load([
                'creador',
                'agendas',
            ]);

            return response()->json([
                'success' => true,

                'message' =>
                    'Asamblea actualizada correctamente.',

                'asamblea' =>
                    $this->formatearAsamblea(
                        $asamblea
                    ),
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,

                'message' =>
                    'No se pudo actualizar la asamblea.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publicar una asamblea.
     *
     * POST /api/asambleas/{asamblea}/publicar
     *
     * La publicación cambia el estado de borrador
     * a publicada y envía una notificación FCM
     * a los dispositivos Android registrados.
     */
    public function publicar(
        Asamblea $asamblea,
        FcmService $fcmService
    ): JsonResponse {
        try {

            /*
             * Solo se puede publicar una asamblea
             * que todavía esté en borrador.
             */
            if (
                $asamblea->estado !==
                'borrador'
            ) {

                return response()->json([
                    'success' => false,

                    'message' =>
                        'Solo se pueden publicar asambleas que estén en borrador.',
                ], 422);
            }

            /*
             * Publicamos la asamblea.
             *
             * Esta parte se mantiene exactamente
             * como el flujo que ya probamos.
             */
            $asamblea->update([
                'estado' =>
                    'publicada',
            ]);

            /*
             * Cargamos las relaciones para devolver
             * la asamblea completa a Android.
             */
            $asamblea->load([
                'creador',
                'agendas',
            ]);

            /*
             * ==============================================================
             * NOTIFICACIÓN NATIVA ANDROID — FCM
             * ==============================================================
             *
             * Esta notificación es independiente del sistema
             * Push web existente.
             */
            $notificacionesEnviadas = 0;
            $errorNotificacion = null;

            try {

                $notificacionesEnviadas =
                    $fcmService->enviarAUsuarios(
                        titulo:
                            'Nueva asamblea',

                        mensaje:
                            'Se ha publicado: ' .
                            ($asamblea->titulo
                                ?: 'Nueva asamblea'),

                        data: [
                            'tipo' =>
                                'asamblea',

                            'asamblea_id' =>
                                $asamblea->id,
                        ]
                    );

            } catch (Throwable $e) {

                /*
                 * Si FCM falla, NO deshacemos la publicación.
                 *
                 * La asamblea ya fue publicada correctamente.
                 * Registramos el error para poder revisarlo.
                 */
                $errorNotificacion =
                    $e->getMessage();

                Log::error(
                    'Error enviando notificación FCM al publicar asamblea.',
                    [
                        'asamblea_id' =>
                            $asamblea->id,

                        'error' =>
                            $e->getMessage(),
                    ]
                );
            }

            $mensaje =
                'Asamblea publicada correctamente.';

            if (
                $notificacionesEnviadas > 0
            ) {

                $mensaje .=
                    ' Notificación enviada a ' .
                    $notificacionesEnviadas .
                    ' dispositivo(s) Android.';

            } elseif (
                $errorNotificacion !== null
            ) {

                $mensaje .=
                    ' La notificación Android no pudo enviarse.';
            }

            return response()->json([
                'success' => true,

                'message' =>
                    $mensaje,

                'asamblea' =>
                    $this->formatearAsamblea(
                        $asamblea
                    ),

                'notificaciones_enviadas' =>
                    $notificacionesEnviadas,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,

                'message' =>
                    'No se pudo publicar la asamblea.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar una asamblea.
     *
     * DELETE /api/asambleas/{asamblea}
     */
    public function destroy(
        Asamblea $asamblea
    ): JsonResponse {
        try {

            if (
                $asamblea->alerta_enviada
            ) {

                return response()->json([
                    'success' => false,

                    'message' =>
                        'Esta asamblea ya tiene una convocatoria enviada y no puede eliminarse.',
                ], 422);
            }

            DB::transaction(
                function () use (
                    $asamblea
                ) {

                    $asamblea
                        ->agendas()
                        ->delete();

                    $asamblea->delete();
                }
            );

            return response()->json([
                'success' => true,

                'message' =>
                    'Asamblea eliminada correctamente.',
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,

                'message' =>
                    'No se pudo eliminar la asamblea.',

                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formatear una asamblea para Android.
     */
    private function formatearAsamblea(
        Asamblea $asamblea
    ): array {
        return [
            'id' =>
                $asamblea->id,

            'tipo' =>
                $asamblea->tipo,

            'titulo' =>
                $asamblea->titulo,

            'convoca' =>
                $asamblea->convoca,

            'sector' =>
                $asamblea->sector,

            'grupo' =>
                $asamblea->grupo,

            'manzana' =>
                $asamblea->manzana,

            'lote' =>
                $asamblea->lote,

            'fecha' =>
                $asamblea->fecha
                    ?->format('Y-m-d'),

            'hora' =>
                $asamblea->hora
                    ?->format('H:i'),

            'primera_citacion' =>
                $asamblea->primera_citacion
                    ?->format('H:i'),

            'segunda_citacion' =>
                $asamblea->segunda_citacion
                    ?->format('H:i'),

            'lugar' =>
                $asamblea->lugar,

            'descripcion' =>
                $asamblea->descripcion,

            'importancia' =>
                $asamblea->importancia,

            'plantilla_citacion' =>
                $asamblea->plantilla_citacion,

            'estado' =>
                $asamblea->estado,

            'created_by' =>
                $asamblea->created_by,

            'alerta_enviada' =>
                (bool) $asamblea->alerta_enviada,

            'alerta_enviada_at' =>
                $asamblea->alerta_enviada_at
                    ?->toISOString(),

            'creador' =>
                $asamblea->creador
                    ? [
                        'id' =>
                            $asamblea->creador->id,

                        'name' =>
                            $asamblea->creador->name,

                        'email' =>
                            $asamblea->creador->email,
                    ]
                    : null,

            'agendas' =>
                $asamblea->agendas
                    ->map(function ($agenda) {

                        return [
                            'id' =>
                                $agenda->id,

                            'numero' =>
                                $agenda->numero,

                            'descripcion' =>
                                $agenda->descripcion,
                        ];

                    })
                    ->values()
                    ->all(),

            'created_at' =>
                $asamblea->created_at
                    ?->toISOString(),

            'updated_at' =>
                $asamblea->updated_at
                    ?->toISOString(),
        ];
    }
}