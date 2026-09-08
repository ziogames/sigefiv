<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\SigiEvento;
use App\Models\SigiEventoReporte;
use Illuminate\Support\Facades\DB;

class SigiEventoService
{
    /**
     * Procesa un mensaje y lo incorpora al evento correspondiente.
     */
    public function procesarMensaje(
        ChatMessage $mensaje,
        array $analisis
    ): array {
        if (
            empty($analisis['analizado']) ||
            empty($analisis['categoria'])
        ) {
            return [
                'evento' => null,
                'reporte' => null,
                'creado' => false,
                'actualizado' => false,
                'debe_intervenir' => false,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Pregunta general relacionada con un evento
        |--------------------------------------------------------------------------
        |
        | Una pregunta clasificada como "general" NO debe heredar
        | automáticamente cualquier evento activo de la conversación.
        |
        | Esto es importante porque el chat vecinal es una conversación
        | compartida: puede existir una emergencia activa y, al mismo
        | tiempo, un vecino puede estar hablando de otro asunto.
        |
        | Solo permitimos contexto cuando SigiService ya determinó que
        | la pregunta es contextual respecto de un evento anterior.
        |
        | Ejemplo:
        |
        |   "¿cómo se usa?"
        |
        |   -> pregunta general sin contexto -> SIGI guarda silencio.
        |
        | Mientras que una pregunta contextual como:
        |
        |   "¿ya volvió para todos?"
        |
        |   -> SigiService debe marcarla como contextual -> buscamos
        |      el evento relacionado.
        |
        */

        if (
            $analisis['categoria'] === 'general' &&
            ($analisis['intencion'] ?? null) === 'pregunta' &&
            !empty($analisis['contextual']) &&
            $mensaje->conversation_id
        ) {
            $eventoGeneral = SigiEvento::query()
                ->where(
                    'conversation_id',
                    $mensaje->conversation_id
                )
                ->whereIn(
                    'estado',
                    [
                        'abierto',
                        'parcial',
                    ]
                )
                ->orderByDesc('id')
                ->first();

            if ($eventoGeneral) {
                return [
                    'evento' => $eventoGeneral,
                    'reporte' => null,
                    'creado' => false,
                    'actualizado' => false,
                    'debe_intervenir' => true,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Pregunta general sin contexto
        |--------------------------------------------------------------------------
        |
        | No debe crear ni reutilizar eventos.
        |
        */

        if (
            $analisis['categoria'] === 'general'
        ) {
            return [
                'evento' => null,
                'reporte' => null,
                'creado' => false,
                'actualizado' => false,
                'debe_intervenir' => false,
            ];
        }

        if (
            $analisis['categoria'] === 'general'
        ) {
            return [
                'evento' => null,
                'reporte' => null,
                'creado' => false,
                'actualizado' => false,
                'debe_intervenir' => false,
            ];
        }

        $categoria = $analisis['categoria'];
        $intencion = $analisis['intencion'] ?? 'comentario';
        $prioridad = $analisis['prioridad'] ?? 'baja';
        $contextual = $analisis['contextual'] ?? false;

        if (!$mensaje->conversation_id) {
            return [
                'evento' => null,
                'reporte' => null,
                'creado' => false,
                'actualizado' => false,
                'debe_intervenir' => false,
            ];
        }

        $evento = $this->buscarEvento(
            $mensaje->conversation_id,
            $categoria
        );

        return DB::transaction(function () use (
            $mensaje,
            $analisis,
            $evento,
            $categoria,
            $intencion,
            $prioridad,
            $contextual
        ) {
            $creado = false;
            $actualizado = false;

            /*
            |--------------------------------------------------------------------------
            | Crear evento
            |--------------------------------------------------------------------------
            */

            if (!$evento) {
                $evento = SigiEvento::create([
                    'conversation_id' => $mensaje->conversation_id,
                    'categoria' => $categoria,
                    'estado' => $this->determinarEstadoInicial(
                        $intencion
                    ),
                    'resumen' => null,
                    'total_reportes' => 0,
                    'reportes_problema' => 0,
                    'reportes_resueltos' => 0,
                    'prioridad' => $prioridad,
                    'ultimo_reporte_at' => null,
                    'ultima_intervencion_at' => null,
                    'resuelto_at' => null,
                    'cerrado_at' => null,
                ]);

                $creado = true;
            } else {
                $actualizado = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Tipo específico
            |--------------------------------------------------------------------------
            */

            $tipoReporte = $this->determinarTipoReporte(
                $mensaje,
                $analisis,
                $categoria,
                $intencion
            );

            /*
            |--------------------------------------------------------------------------
            | Evitar duplicar mensaje
            |--------------------------------------------------------------------------
            */

            $reporteExistente = SigiEventoReporte::where(
                'evento_id',
                $evento->id
            )
                ->where(
                    'message_id',
                    $mensaje->id
                )
                ->first();

            if ($reporteExistente) {
                return [
                    'evento' => $evento->fresh(),
                    'reporte' => $reporteExistente,
                    'creado' => $creado,
                    'actualizado' => $actualizado,
                    'debe_intervenir' =>
                        $this->debeIntervenir(
                            $evento,
                            $analisis
                        ),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Crear reporte
            |--------------------------------------------------------------------------
            */

            $reporte = SigiEventoReporte::create([
                'evento_id' => $evento->id,
                'message_id' => $mensaje->id,
                'user_id' => $mensaje->user_id,
                'tipo' => $tipoReporte,
                'mensaje' => $mensaje->mensaje,
                'confianza' => $this->determinarConfianza(
                    $analisis
                ),
                'contextual' => $contextual,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Contadores históricos
            |--------------------------------------------------------------------------
            */

            $evento->total_reportes++;

            if ($intencion === 'reporte_problema') {
                $evento->reportes_problema++;
            }

            if ($intencion === 'reporte_resuelto') {
                $evento->reportes_resueltos++;
            }

            /*
            |--------------------------------------------------------------------------
            | Prioridad
            |--------------------------------------------------------------------------
            */

            $evento->prioridad =
                $this->obtenerMayorPrioridad(
                    $evento->prioridad,
                    $prioridad
                );

            /*
            |--------------------------------------------------------------------------
            | Último reporte
            |--------------------------------------------------------------------------
            */

            $evento->ultimo_reporte_at =
                $mensaje->created_at ?? now();

            /*
            |--------------------------------------------------------------------------
            | Estado actual
            |--------------------------------------------------------------------------
            */

            $this->actualizarEstadoActual(
                $evento
            );

            $evento->save();

            return [
                'evento' => $evento->fresh(),
                'reporte' => $reporte->fresh(),
                'creado' => $creado,
                'actualizado' => $actualizado,
                'debe_intervenir' =>
                    $this->debeIntervenir(
                        $evento,
                        $analisis
                    ),
            ];
        });
    }

    /**
     * Busca el evento correspondiente.
     */
    protected function buscarEvento(
        int $conversationId,
        string $categoria
    ): ?SigiEvento {
        /*
        |--------------------------------------------------------------------------
        | Evento abierto
        |--------------------------------------------------------------------------
        */

        $evento = SigiEvento::query()
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'categoria',
                $categoria
            )
            ->where(
                'estado',
                'abierto'
            )
            ->orderByDesc('id')
            ->first();

        if ($evento) {
            return $evento;
        }

        /*
        |--------------------------------------------------------------------------
        | Evento parcial
        |--------------------------------------------------------------------------
        */

        $evento = SigiEvento::query()
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'categoria',
                $categoria
            )
            ->where(
                'estado',
                'parcial'
            )
            ->orderByDesc('id')
            ->first();

        if ($evento) {
            return $evento;
        }

        /*
        |--------------------------------------------------------------------------
        | Evento resuelto recientemente
        |--------------------------------------------------------------------------
        */

        return SigiEvento::query()
            ->where(
                'conversation_id',
                $conversationId
            )
            ->where(
                'categoria',
                $categoria
            )
            ->where(
                'estado',
                'resuelto'
            )
            ->where(
                'resuelto_at',
                '>=',
                now()->subMinutes(30)
            )
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Determina el estado actual utilizando
     * el último reporte conocido de cada vecino.
     */
    protected function actualizarEstadoActual(
        SigiEvento $evento
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Vecinos actualmente afectados
        |--------------------------------------------------------------------------
        */

        $afectados =
            $evento->cantidadUsuariosAfectados();

        /*
        |--------------------------------------------------------------------------
        | Vecinos actualmente restablecidos
        |--------------------------------------------------------------------------
        */

        $restablecidos =
            $evento->cantidadUsuariosRestablecidos();

        /*
        |--------------------------------------------------------------------------
        | Nadie está afectado
        |--------------------------------------------------------------------------
        */

        if ($afectados === 0) {
            if ($restablecidos > 0) {
                $evento->estado = 'resuelto';
                $evento->resuelto_at = now();
                $evento->cerrado_at = null;

                return;
            }

            /*
            | No hay información suficiente para marcarlo
            | como resuelto.
            */

            $evento->estado = 'abierto';
            $evento->resuelto_at = null;
            $evento->cerrado_at = null;

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Hay afectados y también restablecidos
        |--------------------------------------------------------------------------
        */

        if ($restablecidos > 0) {
            $evento->estado = 'parcial';
        } else {
            $evento->estado = 'abierto';
        }

        $evento->resuelto_at = null;
        $evento->cerrado_at = null;
    }

    /**
     * Determina el tipo específico del reporte.
     */
    protected function determinarTipoReporte(
        ChatMessage $mensaje,
        array $analisis,
        string $categoria,
        string $intencion
    ): string {
        $texto = mb_strtolower(
            $mensaje->mensaje,
            'UTF-8'
        );

        /*
        |--------------------------------------------------------------------------
        | Restablecido
        |--------------------------------------------------------------------------
        */

        if ($intencion === 'reporte_resuelto') {
            return 'restablecido';
        }

        /*
        |--------------------------------------------------------------------------
        | Alerta
        |--------------------------------------------------------------------------
        */

        if (
            $intencion === 'alerta' ||
            ($analisis['prioridad'] ?? null) === 'alta'
        ) {
            return 'alerta';
        }

        /*
        |--------------------------------------------------------------------------
        | Baja presión
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguno($texto, [
                'baja presion',
                'baja presión',
                'poca presion',
                'poca presión',
                'presion baja',
                'presión baja',
                'sale poco',
                'sale muy poco',
                'sale poquito',
            ])
        ) {
            return 'baja_presion';
        }

        /*
        |--------------------------------------------------------------------------
        | Servicio intermitente
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguno($texto, [
                'va y viene',
                'viene y se va',
                'intermitente',
                'a veces hay',
                'a veces no hay',
                'se corta',
                'se corta por momentos',
            ])
        ) {
            return 'servicio_intermitente';
        }

        /*
        |--------------------------------------------------------------------------
        | Reporte contextual
        |--------------------------------------------------------------------------
        */

        if (
            !empty($analisis['contextual']) &&
            $intencion === 'reporte_problema'
        ) {
            return 'sin_servicio';
        }

        /*
        |--------------------------------------------------------------------------
        | Sin servicio
        |--------------------------------------------------------------------------
        */

        if (
            $intencion === 'reporte_problema' &&
            $this->contieneAlguno($texto, [
                'no tengo',
                'no hay',
                'sin agua',
                'sin luz',
                'se fue',
                'se corto',
                'se cortó',
                'cortaron',
                'no sale',
                'no funciona',
            ])
        ) {
            return 'sin_servicio';
        }

        /*
        |--------------------------------------------------------------------------
        | Información
        |--------------------------------------------------------------------------
        */

        return 'informacion';
    }

    /**
     * Determina la confianza.
     */
    protected function determinarConfianza(
        array $analisis
    ): float {
        $categoria =
            $analisis['categoria'] ?? 'general';

        $intencion =
            $analisis['intencion'] ?? 'comentario';

        if (!empty($analisis['contextual'])) {
            return 0.9000;
        }

        if (
            $categoria === 'emergencia' ||
            ($analisis['prioridad'] ?? null) === 'alta'
        ) {
            return 0.9800;
        }

        if (
            in_array(
                $categoria,
                [
                    'agua',
                    'luz',
                    'seguridad',
                ],
                true
            ) &&
            in_array(
                $intencion,
                [
                    'reporte_problema',
                    'reporte_resuelto',
                    'pregunta',
                ],
                true
            )
        ) {
            return 0.9500;
        }

        return 0.8500;
    }

    /**
     * Estado inicial.
     */
    protected function determinarEstadoInicial(
        string $intencion
    ): string {
        if (
            $intencion === 'reporte_resuelto'
        ) {
            return 'resuelto';
        }

        return 'abierto';
    }

    /**
     * Determina si SIGI debe intervenir.
     */
    protected function debeIntervenir(
        SigiEvento $evento,
        array $analisis
    ): bool {
        $intencion =
            $analisis['intencion'] ?? 'comentario';

        $prioridad =
            $analisis['prioridad'] ?? 'baja';

        if (
            $prioridad === 'alta' ||
            $evento->categoria === 'emergencia'
        ) {
            return true;
        }

        if (
            $intencion === 'pregunta'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Registra una intervención.
     */
    public function registrarIntervencion(
        SigiEvento $evento
    ): void {
        $evento->ultima_intervencion_at = now();

        $evento->save();
    }

    /**
     * Cierra eventos antiguos.
     */
    public function cerrarEventosAntiguos(
        int $minutos = 60
    ): int {
        return SigiEvento::query()
            ->where(
                'estado',
                'abierto'
            )
            ->where(
                'ultimo_reporte_at',
                '<',
                now()->subMinutes($minutos)
            )
            ->update([
                'estado' => 'cerrado',
                'cerrado_at' => now(),
            ]);
    }

    /**
     * Obtiene la prioridad mayor.
     */
    protected function obtenerMayorPrioridad(
        string $actual,
        string $nueva
    ): string {
        $niveles = [
            'baja' => 1,
            'media' => 2,
            'alta' => 3,
        ];

        $nivelActual =
            $niveles[$actual] ?? 1;

        $nivelNuevo =
            $niveles[$nueva] ?? 1;

        return $nivelNuevo > $nivelActual
            ? $nueva
            : $actual;
    }

    /**
     * Comprueba términos.
     */
    protected function contieneAlguno(
        string $texto,
        array $terminos
    ): bool {
        foreach ($terminos as $termino) {
            $termino = mb_strtolower(
                $termino,
                'UTF-8'
            );

            if (
                $termino !== '' &&
                str_contains(
                    $texto,
                    $termino
                )
            ) {
                return true;
            }
        }

        return false;
    }
}