<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\chat\ChatDeviceAuthorizationService;
use App\Services\chat\ChatDeviceCommandService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use App\Services\chat\ChatDeviceService;

class ChatSigiService
{
    /**
     * Orden de dispositivo pendiente de recibir duración.
     */
    private array $ordenesDispositivoPendientes = [];

    /**
     * Chat actual durante el procesamiento de una consulta.
     */
    private ?ChatConversation $chatContextoActual = null;

    public function __construct(
        protected ConsultaInteligenteService $consultaInteligente,
        protected ConsultaEjecutorService $consultaEjecutor,
        protected ChatDeviceCommandService $chatDeviceCommandService,
        protected ChatDeviceAuthorizationService $chatDeviceAuthorizationService,
        protected ChatDeviceService $chatDeviceService,
    ) {}

    /**
     * Determina si un mensaje está dirigido a SIGI.
     */
    public function esConsultaParaSigi(string $mensaje): bool
    {
        $texto = mb_strtolower(trim($mensaje), 'UTF-8');

        if ($texto === '') {
            return false;
        }

        if (preg_match('/(?:^|\s)@zoe(?:\s|$)/u', $texto)) {
            return true;
        }

        if (preg_match('/^zoe(?:[\s,:;.!¿?]+|$)/u', $texto)) {
            return true;
        }

        return false;
    }

    /**
     * Elimina la mención de SIGI antes de procesar la consulta.
     */
    public function limpiarConsulta(string $mensaje): string
    {
        $consulta = trim($mensaje);

        $consulta = preg_replace(
            '/(?:^|\s)@zoe(?=\s|$)/iu',
            ' ',
            $consulta
        );

        $consulta = preg_replace(
            '/^zoe(?:[\s,:;.!¿?]+|$)/iu',
            '',
            $consulta
        );

        return trim(
            preg_replace('/\s+/u', ' ', $consulta)
        );
    }

    /**
     * Normaliza texto para detectar palabras/frases sin depender
     * de mayúsculas, tildes o pequeñas variaciones.
     */
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(
            trim($texto),
            'UTF-8'
        );

        $texto = strtr(
            $texto,
            [
                'á' => 'a',
                'é' => 'e',
                'í' => 'i',
                'ó' => 'o',
                'ú' => 'u',
                'ü' => 'u',
            ]
        );

        $texto = preg_replace(
            '/[^\p{L}\p{N}\s]/u',
            ' ',
            $texto
        );

        return trim(
            preg_replace('/\s+/u', ' ', $texto)
        );
    }

    /**
     * Comprueba si existe alguna de las palabras/frases indicadas.
     */
    private function contieneAlguna(
        string $texto,
        array $terminos
    ): bool {
        foreach ($terminos as $termino) {
            $termino = $this->normalizarTexto($termino);

            if ($termino === '') {
                continue;
            }

            if (
                preg_match(
                    '/(?:^|\s)'.
                    preg_quote($termino, '/').
                    '(?:\s|$)/u',
                    $texto
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta emergencias y servicios vecinales.
     *
     * IMPORTANTE:
     * - Se ejecuta automáticamente aunque no exista @zoe.
     * - Las respuestas son locales y no dependen de IA.
     * - Se priorizan situaciones de seguridad y salud.
     */
    private function respuestaAutomaticaEmergencia(
        string $mensaje
    ): ?string {
        $texto = $this->normalizarTexto($mensaje);

        if ($texto === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | SEGURIDAD / ROBO / ASALTO
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'robo',
                    'roban',
                    'robando',
                    'robaron',
                    'asalto',
                    'asaltan',
                    'asaltando',
                    'asaltaron',
                    'ladron',
                    'ladrones',
                    'delincuente',
                    'delincuentes',
                    'delincuencia',
                    'me estan robando',
                    'estan robando',
                    'están robando',
                ]
            )
        ) {
            return
                "👮 POLICÍA\n\n"
                ."📞 Emergencias: 105\n"
                ."📞 Comisaría de Villa El Salvador: (01) 287-3804\n\n"
                .'🚨 Si el robo está ocurriendo en este momento, comunícate inmediatamente con la Policía.';
        }

        /*
        |--------------------------------------------------------------------------
        | AGRESIÓN / PELEA / DISTURBIOS
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'pelea',
                    'peleando',
                    'pelean',
                    'agresion',
                    'agresion fisica',
                    'agredieron',
                    'agrediendo',
                    'golpean',
                    'golpeando',
                    'golpearon',
                    'violencia',
                    'disturbio',
                    'disturbios',
                    'escandalo',
                ]
            )
        ) {
            return
                "🚨 SEGURIDAD CIUDADANA\n\n"
                ."👮 Policía: 105\n"
                ."🛡️ Serenazgo VES: (01) 510-0200\n"
                ."📞 Comisaría de Villa El Salvador: (01) 287-3804\n\n"
                .'Si existe peligro inmediato, comunícate con la Policía.';
        }

        /*
        |--------------------------------------------------------------------------
        | INCENDIO / BOMBEROS
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'bombero',
                    'bomberos',
                    'incendio',
                    'incendios',
                    'se quema',
                    'se esta quemando',
                    'esta ardiendo',
                    'humo',
                    'fuego',
                ]
            )
        ) {
            return
                "🚒 BOMBEROS\n\n"
                ."📞 Emergencias: 116\n"
                ."📞 Bomberos de Villa El Salvador: (01) 287-7423\n\n"
                .'🚨 Si existe un incendio o peligro inmediato, comunícate inmediatamente con los Bomberos.';
        }

        /*
        |--------------------------------------------------------------------------
        | EMERGENCIA MÉDICA
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'ambulancia',
                    'ambulancia municipal',
                    'accidente',
                    'accidentado',
                    'herido',
                    'herida',
                    'heridos',
                    'heridas',
                    'sangrando',
                    'sangre',
                    'cabeza rota',
                    'cabeza herida',
                    'golpe en la cabeza',
                    'desmayo',
                    'desmayado',
                    'desmayada',
                    'inconsciente',
                    'no responde',
                    'emergencia medica',
                ]
            )
        ) {
            return
                "🚑 EMERGENCIA MÉDICA\n\n"
                ."📞 Ambulancia Municipal: 959 235 344\n"
                ."👮 Policía: 105\n\n"
                .'🚨 Si la persona está en peligro inmediato, solicita ayuda de emergencia sin demora.';
        }

        /*
        |--------------------------------------------------------------------------
        | SERENAZGO
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'serenazgo',
                    'sereno',
                    'serenos',
                    'seguridad ciudadana',
                    'tomando cerveza',
                    'tomando alcohol',
                    'tomando en el parque',
                    'bebiendo en el parque',
                    'borrachos en el parque',
                    'borracho en el parque',
                    'disturbios en el parque',
                    'escandalo en el parque',
                ]
            )
        ) {
            return
                "🛡️ SERENAZGO DE VILLA EL SALVADOR\n\n"
                ."📞 Emergencias: (01) 510-0200\n\n"
                .'Puedes comunicarte con Serenazgo para reportar situaciones que afecten la seguridad y tranquilidad vecinal.';
        }

        /*
        |--------------------------------------------------------------------------
        | CENTRAL DE CÁMARAS
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'central de camaras',
                    'central camaras',
                    'central de vigilancia',
                    'central de monitoreo',
                    'camaras de seguridad',
                    'camaras de vigilancia',
                ]
            )
        ) {
            return
                "📹 CENTRAL DE CÁMARAS\n\n"
                ."📞 Llamadas de emergencia: (01) 510-0200\n"
                ."🕐 Atención: 24 horas\n"
                ."💬 WhatsApp: 959 257 663\n\n"
                .'El WhatsApp es únicamente para enviar fotos, videos o reportes.';
        }

        /*
        |--------------------------------------------------------------------------
        | LUZ DEL SUR
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'luz del sur',
                    'fonoluz',
                    'corte de luz',
                    'corte de electricidad',
                    'se fue la luz',
                    'sin luz',
                    'no tengo luz',
                    'no hay luz',
                    'estamos sin luz',
                    'apagón',
                    'apagon',
                    'problema electrico',
                    'averia electrica',
                ]
            )
        ) {
            return
                "💡 LUZ DEL SUR – FONOLUZ\n\n"
                ."📞 01 617 5000\n\n"
                .'Puedes comunicarte con Fonoluz para reportar problemas del servicio eléctrico.';
        }

        /*
        |--------------------------------------------------------------------------
        | SEDAPAL
        |--------------------------------------------------------------------------
        */

        if (
            $this->contieneAlguna(
                $texto,
                [
                    'sedapal',
                    'aquafono',
                    'corte de agua',
                    'cortes de agua',
                    'se fue el agua',
                    'sin agua',
                    'no tengo agua',
                    'no hay agua',
                    'estamos sin agua',
                    'fuga de agua',
                    'fuga de sedapal',
                    'problema de agua',
                ]
            )
        ) {
            return
                "💧 SEDAPAL – AQUAFONO\n\n"
                ."📞 01 317 8000\n\n"
                .'Puedes comunicarte con Aquafono para reportar problemas relacionados con el servicio de agua y alcantarillado.';
        }

        return null;
    }

    /**
     * Procesa una consulta dirigida a SIGI.
     */
    public function responder(
        string $mensaje,
        User $usuario
    ): array {
        $consulta = $this->limpiarConsulta($mensaje);

        if ($consulta === '') {
            return [
                'success' => true,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' => '🤖 Hola. Soy ZOE. ¿En qué puedo ayudarte?',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | RESPUESTA AUTOMÁTICA
        |--------------------------------------------------------------------------
        */

        $respuestaEmergencia =
            $this->respuestaAutomaticaEmergencia(
                $consulta
            );

        if ($respuestaEmergencia !== null) {
            return [
                'success' => true,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' => '🤖 '.$respuestaEmergencia,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | ÓRDENES DE DISPOSITIVOS
        |--------------------------------------------------------------------------
        |
        | La detección se realiza antes de la consulta financiera para evitar
        | que una orden de las luces sea interpretada como una consulta normal.
        |
        | IMPORTANTE:
        | - Aquí todavía NO se comunica con el ESP32.
        | - Aquí todavía NO se enciende ni apaga ningún dispositivo.
        | - La autorización se valida usando el rol real de SIGEFIV.
        |
        */

        /*
        |--------------------------------------------------------------------------
        | RESPUESTA A UNA ORDEN DE DISPOSITIVO PENDIENTE
        |--------------------------------------------------------------------------
        |
        | Si ZOE preguntó cuánto tiempo mantener encendidas las luces,
        | una respuesta como "zoe 10 minutos" debe completar esa orden
        | y no pasar al sistema de consultas financieras.
        |
        */

        $duracionPendiente =
            $this->obtenerDuracionDeOrdenPendiente(
                $this->chatContextoActual,
                $consulta
            );

        if ($duracionPendiente !== null) {

          $ordenPendiente = Cache::get(
    'chat_dispositivo_pendiente_' .
    $this->chatContextoActual->id
);

Cache::forget(
    'chat_dispositivo_pendiente_' .
    $this->chatContextoActual->id
);

            $ordenPendiente['duracion_minutos'] =
                $duracionPendiente;

            return [
                'success' => true,
                'tipo' => 'dispositivo',
                'resultado' => $ordenPendiente,
                'mensaje' => '🤖 Entendido. Las luces de la loza deportiva quedarán encendidas durante '
                    .$duracionPendiente
                    .' minutos. La ejecución física se conectará en el siguiente paso.',
            ];
        }

        $ordenDispositivo =
            $this->chatDeviceCommandService
                ->detectarOrden($consulta);

        if ($ordenDispositivo['es_orden'] ?? false) {

            if (
                ! $this->chatDeviceAuthorizationService
                    ->puedeControlarDispositivos($usuario)
            ) {
                return [
                    'success' => true,
                    'tipo' => 'texto',
                    'resultado' => null,
                    'mensaje' => '🤖 No estás autorizado para controlar los dispositivos del Chat Vecinal.',
                ];
            }

            if (
                ($ordenDispositivo['accion'] ?? null) === 'encender'
            ) {

                /*
                |--------------------------------------------------------------------------
                | Si el usuario ya indicó una duración,
                | no volver a preguntarla.
                |--------------------------------------------------------------------------
                */

               if (
    isset($ordenDispositivo['duracion_minutos']) &&
    (int) $ordenDispositivo['duracion_minutos'] > 0
) {
    $duracionMinutos =
        (int) $ordenDispositivo['duracion_minutos'];

    $dispositivo =
        $this->chatDeviceService
            ->obtenerPorNombre(
                'Luces de la loza deportiva'
            );

    if (!$dispositivo) {
        return [
            'success' => false,
            'tipo' => 'texto',
            'resultado' => null,
            'mensaje' =>
                '🤖 No encontré las luces de la loza deportiva en los dispositivos configurados.',
        ];
    }

    $dispositivo =
        $this->chatDeviceService
            ->encenderPorMinutos(
                $dispositivo,
                $duracionMinutos,
                $usuario,
                $mensaje
            );

    return [
        'success' => true,
        'tipo' => 'dispositivo',
        'resultado' => [
            'dispositivo' => $dispositivo,
            'orden' => $ordenDispositivo,
        ],
        'mensaje' =>
            '🤖 Entendido. Las luces de la loza deportiva quedarán encendidas durante '
            .$duracionMinutos
            .' minutos.',
    ];
}

                /*
                |--------------------------------------------------------------------------
                | Si NO indicó duración, preguntar.
                |--------------------------------------------------------------------------
                */
                if (
                    isset($this->chatContextoActual) &&
                    $this->chatContextoActual instanceof ChatConversation
                ) {
                    Cache::put(
                        'chat_dispositivo_pendiente_'.
                        $this->chatContextoActual->id,
                        $ordenDispositivo,
                        now()->addMinutes(10)
                    );
                }

                return [
                    'success' => true,
                    'tipo' => 'dispositivo',
                    'resultado' => $ordenDispositivo,
                    'mensaje' => '🤖 Claro. ¿Por cuánto tiempo deseas mantener encendidas las luces de la loza deportiva?',
                ];
            }

          if (
    ($ordenDispositivo['accion'] ?? null) === 'apagar'
) {
    $dispositivo =
        $this->chatDeviceService
            ->obtenerPorNombre(
                'Luces de la loza deportiva'
            );

    if (!$dispositivo) {
        return [
            'success' => false,
            'tipo' => 'texto',
            'resultado' => null,
            'mensaje' =>
                '🤖 No encontré las luces de la loza deportiva en los dispositivos configurados.',
        ];
    }

    $dispositivo =
        $this->chatDeviceService
            ->apagar(
                $dispositivo,
                $usuario,
                $mensaje
            );

    return [
        'success' => true,
        'tipo' => 'dispositivo',
        'resultado' => [
            'dispositivo' => $dispositivo,
            'orden' => $ordenDispositivo,
        ],
        'mensaje' =>
            '🤖 Entendido. Las luces de la loza deportiva han sido apagadas.',
    ];
}
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTA INTELIGENTE NORMAL
        |--------------------------------------------------------------------------
        */

        $interpretacion =
            $this->consultaInteligente
                ->interpretar($consulta);

        /*
        |--------------------------------------------------------------------------
        | CONTEXTO FINANCIERO DEL CHAT VECINAL
        |--------------------------------------------------------------------------
        */

        if (
            isset($this->chatContextoActual) &&
            $this->chatContextoActual instanceof ChatConversation
        ) {
            $interpretacion =
                $this->aplicarContextoFinanciero(
                    $this->chatContextoActual,
                    $mensaje,
                    $interpretacion
                );
        }

        $respuesta =
            $this->consultaEjecutor
                ->ejecutar(
                    $interpretacion,
                    $usuario->id
                );

        if (! is_array($respuesta)) {
            return [
                'success' => false,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' => '🤖 No pude procesar tu consulta en este momento.',
            ];
        }

        if (
            ! isset($respuesta['mensaje']) ||
            trim((string) $respuesta['mensaje']) === ''
        ) {
            $respuesta['mensaje'] =
                '🤖 No encontré una respuesta para esa consulta.';
        }

        $mensajeRespuesta =
            trim(
                (string) $respuesta['mensaje']
            );

        if (
            ! str_starts_with(
                $mensajeRespuesta,
                '🤖'
            )
        ) {
            $respuesta['mensaje'] =
                '🤖 '.$mensajeRespuesta;
        }

        return $respuesta;
    }

    /**
     * Obtiene la duración de la respuesta a una orden pendiente.
     */
  private function obtenerDuracionDeOrdenPendiente(
    ?ChatConversation $chat,
    string $texto
): ?int {
    if (!$chat instanceof ChatConversation) {
        return null;
    }

    $ordenPendiente = Cache::get(
        'chat_dispositivo_pendiente_' . $chat->id
    );

    if (!is_array($ordenPendiente)) {
        return null;
    }

        $texto = $this->normalizarTexto($texto);

        if (
            preg_match(
                '/^(?:por|durante)?\s*(\d+(?:[.,]\d+)?)\s*horas?\s*(?:y\s*)?(\d+)\s*minutos?$/u',
                $texto,
                $coincidencias
            )
        ) {
            $horas = (float) str_replace(
                ',',
                '.',
                $coincidencias[1]
            );

            return (int) round(
                ($horas * 60) + (int) $coincidencias[2]
            );
        }

        if (
            preg_match(
                '/^(?:por|durante)?\s*(\d+(?:[.,]\d+)?)\s*horas?$/u',
                $texto,
                $coincidencias
            )
        ) {
            $horas = (float) str_replace(
                ',',
                '.',
                $coincidencias[1]
            );

            return (int) round($horas * 60);
        }

        if (
            preg_match(
                '/^(?:por|durante)?\s*(\d+)\s*minutos?$/u',
                $texto,
                $coincidencias
            )
        ) {
            return (int) $coincidencias[1];
        }

        return null;
    }

    /**
     * Completa una consulta financiera con el contexto de la última
     * consulta financiera del mismo Chat Vecinal.
     *
     * No modifica el texto de la consulta. En su lugar, reutiliza la
     * interpretación estructurada de ConsultaInteligenteService para
     * heredar fecha, categoría y tipo de movimiento cuando corresponda.
     */
    private function aplicarContextoFinanciero(
        ChatConversation $chat,
        string $consulta,
        array $interpretacion
    ): array {
        $texto = mb_strtolower(
            trim($consulta),
            'UTF-8'
        );

        /*
        |--------------------------------------------------------------------------
        | Solo completar consultas financieras.
        |--------------------------------------------------------------------------
        */

        $esFinanciera =
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresos') ||
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'egresos') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastos') ||
            str_contains($texto, 'saldo') ||
            str_contains($texto, 'caja') ||
            str_contains($texto, 'movimiento') ||
            str_contains($texto, 'movimientos');

        if (! $esFinanciera) {
            return $interpretacion;
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTA GENERAL DE MOVIMIENTOS
        |--------------------------------------------------------------------------
        |
        | Si el vecino pregunta explícitamente por "movimientos", no debemos
        | heredar Ingreso/Egreso de una consulta financiera anterior.
        |
        | Ejemplo:
        |   ¿Cuántos ingresos hubo en junio?       -> Ingreso
        |   ¿Cuántos egresos hubo en junio?        -> Egreso
        |   ¿Cuántos movimientos tuvimos en junio? -> TODOS
        |
        | ConsultaInteligenteService ya determinó correctamente que la última
        | consulta no tiene tipo_movimiento. Por eso conservamos esa decisión
        | y no permitimos que el contexto del chat la cambie.
        |
        | La categoría, concepto y fecha detectados en la consulta actual
        | permanecen intactos.
        |--------------------------------------------------------------------------
        */

        $esMovimientoGeneral =
            preg_match(
                '/(?:^|\\s)movimientos?(?:\\s|$)/u',
                $texto
            ) === 1
            &&
            ! preg_match(
                '/(?:^|\\s)(?:ingresos?|egresos?|gastos?)(?:\\s|$)/u',
                $texto
            );

        if ($esMovimientoGeneral) {
            $interpretacion['tipo_movimiento'] = null;

            /*
             * "tipo" es un campo auxiliar utilizado por algunas rutas
             * antiguas. También debe quedar limpio para evitar que el
             * ejecutor aplique Ingreso/Egreso por una interpretación previa.
             */
            if (array_key_exists('tipo', $interpretacion)) {
                $interpretacion['tipo'] = null;
            }

            return $interpretacion;
        }

        /*
        |--------------------------------------------------------------------------
        | Si la consulta actual ya trae información temporal completa,
        | no heredamos la fecha anterior.
        |--------------------------------------------------------------------------
        */

        $fechaActual =
            $interpretacion['fecha']
            ?? [];

        $tieneFechaActual =
            ($fechaActual['anio'] ?? null) !== null ||
            ($fechaActual['mes'] ?? null) !== null ||
            ! empty($fechaActual['meses'] ?? []) ||
            ($fechaActual['mes_desde'] ?? null) !== null ||
            ($fechaActual['mes_hasta'] ?? null) !== null;

        /*
        |--------------------------------------------------------------------------
        | Buscar hacia atrás la última consulta financiera del usuario.
        |
        | Ignoramos respuestas de SIGI para que el contexto provenga
        | únicamente de lo que preguntó el vecino.
        |--------------------------------------------------------------------------
        */

        $mensajes =
            $chat->mensajes()
                ->where('tipo', 'usuario')
                ->orderByDesc('id')
                ->limit(30)
                ->get([
                    'id',
                    'mensaje',
                ]);

        foreach ($mensajes as $mensajeAnterior) {
            $textoAnterior =
                trim((string) $mensajeAnterior->mensaje);

            if ($textoAnterior === '') {
                continue;
            }

            if (
                mb_strtolower(
                    $textoAnterior,
                    'UTF-8'
                ) === mb_strtolower(
                    $consulta,
                    'UTF-8'
                )
            ) {
                continue;
            }

            $textoAnteriorNormalizado =
                mb_strtolower(
                    $textoAnterior,
                    'UTF-8'
                );

            $esFinancieraAnterior =
                str_contains($textoAnteriorNormalizado, 'ingreso') ||
                str_contains($textoAnteriorNormalizado, 'ingresos') ||
                str_contains($textoAnteriorNormalizado, 'egreso') ||
                str_contains($textoAnteriorNormalizado, 'egresos') ||
                str_contains($textoAnteriorNormalizado, 'gasto') ||
                str_contains($textoAnteriorNormalizado, 'gastos') ||
                str_contains($textoAnteriorNormalizado, 'saldo') ||
                str_contains($textoAnteriorNormalizado, 'caja') ||
                str_contains($textoAnteriorNormalizado, 'movimiento') ||
                str_contains($textoAnteriorNormalizado, 'movimientos');

            if (! $esFinancieraAnterior) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Interpretamos nuevamente la consulta anterior con el mismo
            | servicio que usa el asistente SIGI.
            |--------------------------------------------------------------------------
            */

            $interpretacionAnterior =
                $this->consultaInteligente
                    ->interpretar(
                        $this->limpiarConsulta(
                            $textoAnterior
                        )
                    );

            $fechaAnterior =
                $interpretacionAnterior['fecha']
                ?? [];

            /*
            |--------------------------------------------------------------------------
            | Heredar fecha.
            |--------------------------------------------------------------------------
            */

            if (
                ! $tieneFechaActual &&
                ! empty($fechaAnterior)
            ) {
                $interpretacion['fecha'] =
                    $fechaAnterior;
            }

            /*
            |--------------------------------------------------------------------------
            | Heredar categoría solo si la actual no la tiene.
            |--------------------------------------------------------------------------
            */

            if (
                empty($interpretacion['categoria'] ?? null) &&
                ! empty($interpretacionAnterior['categoria'] ?? null)
            ) {
                $interpretacion['categoria'] =
                    $interpretacionAnterior['categoria'];
            }

            /*
            |--------------------------------------------------------------------------
            | Determinar explícitamente el tipo actual.
            |
            | "y cuáles son los egresos" debe ser Egreso aunque la consulta
            | anterior haya sido sobre ingresos.
            |--------------------------------------------------------------------------
            */

            $esEgreso =
                str_contains($texto, 'egreso') ||
                str_contains($texto, 'egresos') ||
                str_contains($texto, 'gasto') ||
                str_contains($texto, 'gastos');

            $esIngreso =
                str_contains($texto, 'ingreso') ||
                str_contains($texto, 'ingresos');

            if ($esEgreso) {
                $interpretacion['tipo_movimiento'] =
                    'Egreso';

                $interpretacion['tipo'] =
                    'Egreso';
            } elseif ($esIngreso) {
                $interpretacion['tipo_movimiento'] =
                    'Ingreso';

                $interpretacion['tipo'] =
                    'Ingreso';
            }

            /*
            |--------------------------------------------------------------------------
            | La consulta anterior ya nos sirvió; no necesitamos buscar
            | más atrás.
            |--------------------------------------------------------------------------
            */

            break;
        }

        return $interpretacion;
    }

    /**
     * Convierte el resultado de una consulta de movimientos en texto
     * legible para el Chat Vecinal.
     *
     * El asistente SIGI puede recibir y renderizar directamente
     * "resultado". El Chat Vecinal, en cambio, guarda el mensaje
     * como texto en ChatMessage. Por eso aquí incorporamos el detalle
     * de los movimientos al mensaje que verá el grupo.
     */
    
private function agregarDetalleMovimientos(
    string $mensaje,
    array $respuesta
): string {
    $tipo =
        $respuesta['tipo'] ?? null;

    $resultado =
        $respuesta['resultado'] ?? null;

    if (
        $tipo !== 'lista' ||
        ! is_iterable($resultado)
    ) {
        return $mensaje;
    }

    $movimientos =
        is_object($resultado) &&
        method_exists($resultado, 'values')
            ? $resultado->values()
            : $resultado;

    $filas = [];

    $cantidadMovimientos = 0;
    $cantidadIngresos = 0;
    $cantidadEgresos = 0;

    $totalIngresos = 0.0;
    $totalEgresos = 0.0;

    foreach ($movimientos as $movimiento) {

        if (is_array($movimiento)) {

            $fecha = $movimiento['fecha'] ?? null;

            $movimientoTipo =
                $movimiento['tipo'] ?? null;

            $concepto =
                $movimiento['concepto'] ?? null;

            $monto =
                $movimiento['monto'] ?? null;

            $categoria =
                $movimiento['categoria']['nombre']
                ?? $movimiento['categoria']['name']
                ?? null;

        } else {

            $fecha =
                $movimiento->fecha ?? null;

            $movimientoTipo =
                $movimiento->tipo ?? null;

            $concepto =
                $movimiento->concepto ?? null;

            $monto =
                $movimiento->monto ?? null;

            $categoria =
                $movimiento->categoria?->nombre
                ?? $movimiento->categoria?->name
                ?? null;
        }

        if ($fecha instanceof \DateTimeInterface) {

            $fechaTexto =
                $fecha->format('d/m/Y');

        } elseif ($fecha) {

            $fechaTexto =
                (string) $fecha;

        } else {

            $fechaTexto = '—';
        }

        $montoNumerico =
            is_numeric($monto)
                ? (float) $monto
                : null;

        $montoTexto =
            $montoNumerico !== null
                ? 'S/ '.
                    number_format(
                        abs($montoNumerico),
                        2,
                        '.',
                        ','
                    )
                : '—';

        /*
        |--------------------------------------------------------------------------
        | CLASIFICACIÓN Y RESUMEN FINANCIERO
        |--------------------------------------------------------------------------
        */

        $tipoNormalizado =
            mb_strtolower(
                trim((string) $movimientoTipo),
                'UTF-8'
            );

        $esEgreso =
            str_contains($tipoNormalizado, 'egreso') ||
            str_contains($tipoNormalizado, 'salida') ||
            ($montoNumerico !== null && $montoNumerico < 0);

        if ($montoNumerico !== null) {

            $cantidadMovimientos++;

            $importe =
                abs($montoNumerico);

            if ($esEgreso) {

                $cantidadEgresos++;

                $totalEgresos += $importe;

            } else {

                $cantidadIngresos++;

                $totalIngresos += $importe;
            }
        }

        $filas[] =
            '• '.
            $fechaTexto.
            ' | '.
            ($movimientoTipo ?: '—').
            ' | '.
            ($concepto ?: 'Sin concepto').
            ' | '.
            ($categoria ?: 'Sin categoría').
            ' | '.
            $montoTexto;
    }

    if (empty($filas)) {
        return $mensaje;
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL DE LA CONSULTA
    |--------------------------------------------------------------------------
    */

    $balance =
        $totalIngresos - $totalEgresos;

    $total =
        $movimientos instanceof Collection
            ? (float) $movimientos->sum(
                fn ($movimiento) => is_array($movimiento)
                    ? ($movimiento['monto'] ?? 0)
                    : ($movimiento->monto ?? 0)
            )
            : collect($movimientos)->sum(
                fn ($movimiento) => is_array($movimiento)
                    ? ($movimiento['monto'] ?? 0)
                    : ($movimiento->monto ?? 0)
            );

    $totalTexto =
        'S/ '.
        number_format(
            $total,
            2,
            '.',
            ','
        );

    $filas[] =
        '• '.
        ' | '.
        ' | '.
        ' | '.
        ' | **Total** | **'.
        $totalTexto.
        '**';

    /*
    |--------------------------------------------------------------------------
    | RESUMEN EXPLICATIVO DE ZOE
    |--------------------------------------------------------------------------
    */

    $resumen =
        "📊 **Resumen financiero**\n\n".
        "Se encontraron **{$cantidadMovimientos} movimientos**.\n\n".
        "💰 Ingresos: **S/ ".
        number_format(
            $totalIngresos,
            2,
            '.',
            ','
        ).
        "** ({$cantidadIngresos} movimientos).\n\n".
        "💸 Egresos: **S/ ".
        number_format(
            $totalEgresos,
            2,
            '.',
            ','
        ).
        "** ({$cantidadEgresos} movimientos).\n\n".
        "📈 Balance: **S/ ".
        number_format(
            $balance,
            2,
            '.',
            ','
        ).
        "**.";

    return
        $mensaje.
        "\n\n".
        implode("\n", $filas).
        "\n\n".
        $resumen;
}
    /**
     * Crea un mensaje de SIGI dentro de la conversación.
     */
    public function crearMensajeSigi(
        ChatConversation $chat,
        array $respuesta
    ): ChatMessage {
        $mensaje =
            trim(
                (string) (
                    $respuesta['mensaje']
                    ?? '🤖 No tengo una respuesta disponible.'
                )
            );

        $mensaje =
            $this->agregarDetalleMovimientos(
                $mensaje,
                $respuesta
            );

        return ChatMessage::create([
            'conversation_id' => $chat->id,

            'user_id' => null,

            'tipo' => 'sigi',

            'mensaje' => $mensaje,

            'editado' => false,
        ]);
    }

    /**
     * Procesa el mensaje del Chat Vecinal.
     *
     * Primero busca alertas automáticas.
     * Si no encuentra ninguna, exige @zoe para procesar
     * una consulta normal.
     */
    public function procesar(
        ChatConversation $chat,
        string $mensaje,
        User $usuario
    ): ?ChatMessage {
        /*
        |--------------------------------------------------------------------------
        | ALERTAS AUTOMÁTICAS
        |--------------------------------------------------------------------------
        */

        $respuestaEmergencia =
            $this->respuestaAutomaticaEmergencia(
                $mensaje
            );

        if ($respuestaEmergencia !== null) {
            return $this->crearMensajeSigi(
                $chat,
                [
                    'success' => true,
                    'tipo' => 'texto',
                    'resultado' => null,
                    'mensaje' => '🤖 '.$respuestaEmergencia,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SIGI NORMAL
        |--------------------------------------------------------------------------
        */

        if (
            ! $this->esConsultaParaSigi(
                $mensaje
            )
        ) {
            return null;
        }

        $this->chatContextoActual = $chat;

        $respuesta =
            $this->responder(
                $mensaje,
                $usuario
            );

        $this->chatContextoActual = null;

        $mensajeSigi =
            $this->crearMensajeSigi(
                $chat,
                $respuesta
            );

        $mensajeSigi->load('usuario');

        return $mensajeSigi;
    }
}
