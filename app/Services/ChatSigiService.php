<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\chat\ChatDeviceAuthorizationService;
use App\Services\chat\ChatDeviceCommandService;
use App\Services\chat\ChatDeviceService;
use App\Services\ZoeDirectivaService;
use App\Services\ZoeEmergenciaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ChatSigiService
{
    private array $ordenesDispositivoPendientes = [];
    private ?ChatConversation $chatContextoActual = null;

    public function __construct(
        protected ConsultaInteligenteService $consultaInteligente,
        protected ConsultaEjecutorService $consultaEjecutor,
        protected ZoeN8nService $zoeN8nService,
        protected ChatDeviceCommandService $chatDeviceCommandService,
        protected ChatDeviceAuthorizationService $chatDeviceAuthorizationService,
        protected ChatDeviceService $chatDeviceService,
        protected ZoeDirectivaService $zoeDirectivaService,
        protected ZoeEmergenciaService $zoeEmergenciaService
    ) {}

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

    public function limpiarConsulta(string $mensaje): string
    {
        $consulta = trim($mensaje);

        $consulta = preg_replace('/(?:^|\s)@zoe(?=\s|$)/iu', ' ', $consulta);
        $consulta = preg_replace('/^zoe(?:[\s,:;.!¿?]+|$)/iu', '', $consulta);

        return trim(preg_replace('/\s+/u', ' ', $consulta));
    }

    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower(trim($texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);
        $texto = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $texto);

        return trim(preg_replace('/\s+/u', ' ', $texto));
    }

    /**
     * Detección dinámica de emergencias y consultas institucionales
     * consultando directamente las tablas de PostgreSQL.
     */
    private function respuestaInstitucionalEmergencia(string $mensaje): ?string
    {
        $texto = $this->normalizarTexto($mensaje);

        if ($texto === '') {
            return null;
        }

        // ==========================================
        // 1. CONSULTA DE DIRECTIVA Y CARGOS DESDE LA BD
        // ==========================================
        if (
            (str_contains($texto, 'directiva') || 
             str_contains($texto, 'secretario') || 
             str_contains($texto, 'secretaria') || 
             str_contains($texto, 'cargos') ||
             str_contains($texto, 'presidente')) &&
            !str_contains($texto, 'estatuto')
        ) {
            return $this->zoeDirectivaService->obtenerInformacionDirectiva();
        }

        // ==========================================
        // 2. CONSULTAS DE EMERGENCIA Y SERVICIOS DESDE LA BD
        // ==========================================
        $filtroBusqueda = null;

        if (str_contains($texto, 'robo') || str_contains($texto, 'asalto') || str_contains($texto, 'policia') || str_contains($texto, 'ladron')) {
            $filtroBusqueda = 'Policía';
        } elseif (str_contains($texto, 'bombero') || str_contains($texto, 'incendio') || str_contains($texto, 'fuego')) {
            $filtroBusqueda = 'Bomberos';
        } elseif (str_contains($texto, 'ambulancia') || str_contains($texto, 'accidente') || str_contains($texto, 'herido') || str_contains($texto, 'medica')) {
            $filtroBusqueda = 'Ambulancia';
        } elseif (str_contains($texto, 'serenazgo') || str_contains($texto, 'sereno') || str_contains($texto, 'seguridad ciudadana')) {
            $filtroBusqueda = 'Serenazgo';
        } elseif (str_contains($texto, 'luz') || str_contains($texto, 'fonoluz') || str_contains($texto, 'electricidad')) {
            $filtroBusqueda = 'Luz del Sur';
        } elseif (str_contains($texto, 'agua') || str_contains($texto, 'sedapal') || str_contains($texto, 'aquafono')) {
            $filtroBusqueda = 'Sedapal';
        } elseif (str_contains($texto, 'emergencia') || str_contains($texto, 'numeros') || str_contains($texto, 'telefono')) {
            // Si piden números en general sin especificar categoría
            return $this->zoeEmergenciaService->obtenerNumerosEmergencia();
        }

        if ($filtroBusqueda !== null) {
            return $this->zoeEmergenciaService->obtenerNumerosEmergencia($filtroBusqueda);
        }

        return null;
    }

    public function responder(string $mensaje, User $usuario): array
    {
        $consulta = $this->limpiarConsulta($mensaje);

        if ($consulta === '') {
            return [
                'success' => true,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' => '🤖 Hola. Soy ZOE. ¿En qué puedo ayudarte?',
            ];
        }

        // ==========================================
        // RESPUESTAS INSTITUCIONALES Y DE EMERGENCIA (POSTGRESQL)
        // ==========================================
        $respuestaInstitucional = $this->respuestaInstitucionalEmergencia($consulta);

        if ($respuestaInstitucional !== null) {
            return [
                'success' => true,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' => '🤖 ' . $respuestaInstitucional,
            ];
        }

        // ==========================================
        // ÓRDENES DE DISPOSITIVOS
        // ==========================================
        $duracionPendiente = $this->obtenerDuracionDeOrdenPendiente($this->chatContextoActual, $consulta);

        if ($duracionPendiente !== null) {
            $ordenPendiente = Cache::get('chat_dispositivo_pendiente_' . $this->chatContextoActual->id);
            Cache::forget('chat_dispositivo_pendiente_' . $this->chatContextoActual->id);

            $ordenPendiente['duracion_minutos'] = $duracionPendiente;

            return [
                'success' => true,
                'tipo' => 'dispositivo',
                'resultado' => $ordenPendiente,
                'mensaje' => '🤖 Entendido. Las luces de la loza deportiva quedarán encendidas durante ' . $duracionPendiente . ' minutos.',
            ];
        }

        $ordenDispositivo = $this->chatDeviceCommandService->detectarOrden($consulta);

        if ($ordenDispositivo['es_orden'] ?? false) {
            if (!$this->chatDeviceAuthorizationService->puedeControlarDispositivos($usuario)) {
                return [
                    'success' => true,
                    'tipo' => 'texto',
                    'resultado' => null,
                    'mensaje' => '🤖 No estás autorizado para controlar los dispositivos del Chat Vecinal.',
                ];
            }

            if (($ordenDispositivo['accion'] ?? null) === 'encender') {
                if (isset($ordenDispositivo['duracion_minutos']) && (int) $ordenDispositivo['duracion_minutos'] > 0) {
                    $duracionMinutos = (int) $ordenDispositivo['duracion_minutos'];
                    $dispositivo = $this->chatDeviceService->obtenerPorNombre('Luces de la loza deportiva');

                    if (!$dispositivo) {
                        return [
                            'success' => false,
                            'tipo' => 'texto',
                            'resultado' => null,
                            'mensaje' => '🤖 No encontré las luces de la loza deportiva en los dispositivos configurados.',
                        ];
                    }

                    $dispositivo = $this->chatDeviceService->encenderPorMinutos($dispositivo, $duracionMinutos, $usuario, $mensaje);

                    return [
                        'success' => true,
                        'tipo' => 'dispositivo',
                        'resultado' => ['dispositivo' => $dispositivo, 'orden' => $ordenDispositivo],
                        'mensaje' => '🤖 Entendido. Las luces de la loza deportiva quedarán encendidas durante ' . $duracionMinutos . ' minutos.',
                    ];
                }

                if (isset($this->chatContextoActual) && $this->chatContextoActual instanceof ChatConversation) {
                    Cache::put('chat_dispositivo_pendiente_' . $this->chatContextoActual->id, $ordenDispositivo, now()->addMinutes(10));
                }

                return [
                    'success' => true,
                    'tipo' => 'dispositivo',
                    'resultado' => $ordenDispositivo,
                    'mensaje' => '🤖 Claro. ¿Por cuánto tiempo deseas mantener encendidas las luces de la loza deportiva?',
                ];
            }

            if (($ordenDispositivo['accion'] ?? null) === 'apagar') {
                $dispositivo = $this->chatDeviceService->obtenerPorNombre('Luces de la loza deportiva');

                if (!$dispositivo) {
                    return [
                        'success' => false,
                        'tipo' => 'texto',
                        'resultado' => null,
                        'mensaje' => '🤖 No encontré las luces de la loza deportiva en los dispositivos configurados.',
                    ];
                }

                $dispositivo = $this->chatDeviceService->apagar($dispositivo, $usuario, $mensaje);

                return [
                    'success' => true,
                    'tipo' => 'dispositivo',
                    'resultado' => ['dispositivo' => $dispositivo, 'orden' => $ordenDispositivo],
                    'mensaje' => '🤖 Entendido. Las luces de la loza deportiva han sido apagadas.',
                ];
            }
        }

        // ==========================================
        // CONSULTAS FINANCIERAS
        // ==========================================
        $interpretacion = $this->consultaInteligente->interpretar($consulta);

        if ($this->chatContextoActual instanceof ChatConversation) {
            $interpretacion = $this->aplicarContextoFinanciero($this->chatContextoActual, $consulta, $interpretacion);
        }

        $textoFinanciero = mb_strtolower($consulta, 'UTF-8');
        $esConsultaFinanciera = str_contains($textoFinanciero, 'resumen') ||
            str_contains($textoFinanciero, 'ingreso') || str_contains($textoFinanciero, 'ingresos') ||
            str_contains($textoFinanciero, 'egreso') || str_contains($textoFinanciero, 'egresos') ||
            str_contains($textoFinanciero, 'gasto') || str_contains($textoFinanciero, 'gastos') ||
            str_contains($textoFinanciero, 'saldo') || str_contains($textoFinanciero, 'caja') ||
            str_contains($textoFinanciero, 'movimiento') || str_contains($textoFinanciero, 'movimientos');

        if ($esConsultaFinanciera) {
            $respuestaLaravel = $this->consultaEjecutor->ejecutar($interpretacion, $usuario->id);
            if (($respuestaLaravel['success'] ?? false) === true) {
                return $respuestaLaravel;
            }
        }

        // ==========================================
        // CONSULTA INTELIGENTE (n8n + Ollama)
        // ==========================================
        $respuestaN8n = $this->zoeN8nService->consultar($consulta, $usuario->id, $usuario->name);

        if ($respuestaN8n === null || trim($respuestaN8n) === '') {
            return [
                'success' => false,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' => '🤖 No pude comunicarme con ZOE en este momento.',
            ];
        }

        $mensajeRespuesta = trim($respuestaN8n);

        if (!str_starts_with($mensajeRespuesta, '🤖')) {
            $mensajeRespuesta = '🤖 ' . $mensajeRespuesta;
        }

        return [
            'success' => true,
            'tipo' => 'texto',
            'resultado' => null,
            'mensaje' => $mensajeRespuesta,
        ];
    }

    private function obtenerDuracionDeOrdenPendiente(?ChatConversation $chat, string $texto): ?int
    {
        if (!$chat instanceof ChatConversation) {
            return null;
        }

        $ordenPendiente = Cache::get('chat_dispositivo_pendiente_' . $chat->id);
        if (!is_array($ordenPendiente)) {
            return null;
        }

        $texto = $this->normalizarTexto($texto);

        if (preg_match('/^(?:por|durante)?\s*(\d+(?:[.,]\d+)?)\s*horas?\s*(?:y\s*)?(\d+)\s*minutos?$/u', $texto, $coincidencias)) {
            $horas = (float) str_replace(',', '.', $coincidencias[1]);
            return (int) round(($horas * 60) + (int) $coincidencias[2]);
        }

        if (preg_match('/^(?:por|durante)?\s*(\d+(?:[.,]\d+)?)\s*horas?$/u', $texto, $coincidencias)) {
            $horas = (float) str_replace(',', '.', $coincidencias[1]);
            return (int) round($horas * 60);
        }

        if (preg_match('/^(?:por|durante)?\s*(\d+)\s*minutos?$/u', $texto, $coincidencias)) {
            return (int) $coincidencias[1];
        }

        return null;
    }

    private function aplicarContextoFinanciero(ChatConversation $chat, string $consulta, array $interpretacion): array
    {
        return $interpretacion;
    }

    public function crearMensajeSigi(ChatConversation $chat, array $respuesta): ChatMessage
    {
        $mensaje = trim((string) ($respuesta['mensaje'] ?? '🤖 No tengo una respuesta disponible.'));

        return ChatMessage::create([
            'conversation_id' => $chat->id,
            'user_id' => null,
            'tipo' => 'sigi',
            'mensaje' => $mensaje,
            'editado' => false,
        ]);
    }

    public function procesar(ChatConversation $chat, string $mensaje, User $usuario): ?ChatMessage
    {
        if (!$this->esConsultaParaSigi($mensaje)) {
            return null;
        }

        $this->chatContextoActual = $chat;

        $respuesta = $this->responder($mensaje, $usuario);

        $this->chatContextoActual = null;

        $mensajeSigi = $this->crearMensajeSigi($chat, $respuesta);
        $mensajeSigi->load('usuario');

        return $mensajeSigi;
    }
}