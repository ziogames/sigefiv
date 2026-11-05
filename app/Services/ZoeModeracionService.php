<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Throwable;

class ZoeModeracionService
{
    /**
     * Filtro local de tolerancia cero (solo groserías explícitas o de rescate).
     * El resto de frases contextuales ("viejas chillonas", sarcasmo, amenazas sutiles)
     * las evalúa el LLM en n8n.
     */
    protected array $palabrasProhibidas = [
        'mierda',
        'carajo',
        'imbécil',
        'imbéciles',
        'idiota',
        'idiotas',
        'pendejo',
        'pendejos',
        'conchetumadre',
        'hijo de puta',
        'cholo de mierda',
        'negro de mierda',
        'maricon'
    ];

    /**
     * Evalúa el mensaje del usuario aplicando la regla de 3 advertencias (strikes).
     */
    public function procesarMensaje(int $conversationId, User $user, string $mensajeTexto): array
    {
        // 1. Si el usuario ya está marcado como bloqueado, no permitir ningún envío
        if ($user->estado === 'bloqueado' || $user->fecha_bloqueo !== null) {
            return [
                'bloquear_mensaje' => true,
                'accion'           => 'bloqueo',
                'message'          => '🚫 Tu cuenta está suspendida de este chat vecinal por infracciones previas.',
                'detalle_motivo'   => $user->mensaje_bloqueo ?? 'Cuenta bloqueada permanentemente.',
                'strikes'          => 3,
            ];
        }

        $esOfensivo = false;
        $motivoInfraccion = null;

        // 2. Filtro rápido local (ejecución en < 1ms para groserías indiscutibles)
        if ($this->contienePalabraProhibida($mensajeTexto)) {
            $esOfensivo = true;
            $motivoInfraccion = 'Uso de lenguaje soez o insultos explícitos.';
        } else {
            // 3. Consulta semántica a n8n (evalúa contexto, edadismo, desprecio o amenazas)
            $resultadoIa = $this->consultarN8nModeracion($user, $mensajeTexto);
            if ($resultadoIa['es_ofensivo'] ?? false) {
                $esOfensivo = true;
                $motivoInfraccion = $resultadoIa['motivo'] ?? 'Falta de respeto a las normas de convivencia vecinal.';
            }
        }

        // Si superó ambos filtros, el mensaje es aprobado
        if (!$esOfensivo) {
            return [
                'bloquear_mensaje' => false,
                'accion'           => 'permitido',
            ];
        }

        // 4. Registro progresivo de los 3 strikes
        $advertenciasActuales = intval($user->advertencias_count ?? 0) + 1;
        $user->advertencias_count = $advertenciasActuales;
        $user->ultimo_mensaje_advertencia = $motivoInfraccion;
        $user->fecha_advertencia = Carbon::now();

        // 🚨 FALTA 3: BLOQUEO DEFINITIVO Y REVOCACIÓN DE ACCESO
        if ($advertenciasActuales >= 3) {
            $user->estado = 'bloqueado';
            $user->fecha_bloqueo = Carbon::now();
            $user->mensaje_bloqueo = "Cuenta suspendida tras acumular 3 advertencias. Infracción final: {$motivoInfraccion}";
            $user->save();

            // Revoca tokens de sesión de Sanctum para forzar la expulsión en el móvil
            $user->tokens()->delete();

            return [
                'bloquear_mensaje' => true,
                'accion'           => 'bloqueo',
                'strikes'          => 3,
                'detalle_motivo'   => $user->mensaje_bloqueo,
                'message'          => '🚨 ZOE: Has acumulado 3 advertencias por faltas de respeto. Tu cuenta ha sido bloqueada y se cerrará tu sesión.',
            ];
        }

        // ⚠️ FALTAS 1 Y 2: ADVERTENCIAS PREVIAS
        $user->estado = 'advertido';
        $user->save();

        $restantes = 3 - $advertenciasActuales;

        return [
            'bloquear_mensaje' => true,
            'accion'           => 'advertencia',
            'strikes'          => $advertenciasActuales,
            'detalle_motivo'   => $motivoInfraccion,
            'message'          => "⚠️ ZOE: Tu mensaje no fue enviado porque infringe las normas de convivencia ({$motivoInfraccion}). Advertencia {$advertenciasActuales}/3. Te restan {$restantes} aviso(s) antes del bloqueo definitivo.",
        ];
    }

    /**
     * Conexión con el webhook de n8n para análisis de IA.
     */
    protected function consultarN8nModeracion(User $user, string $mensajeTexto): array
    {
        try {
            $urlN8n = config('services.n8n.url_moderacion', env('N8N_MODERACION_URL'));

            if (!empty($urlN8n)) {
                $response = Http::timeout(3.5)->post($urlN8n, [
                    'usuario_id' => $user->id,
                    'nombre'     => $user->name,
                    'mensaje'    => $mensajeTexto,
                ]);

                if ($response->successful()) {
                    return $response->json();
                }
            }
        } catch (Throwable $e) {
            // Continúa con la ejecución si n8n no responde dentro del timeout
        }

        return ['es_ofensivo' => false, 'motivo' => null];
    }

    /**
     * Detección por subcadenas para el filtro rápido.
     */
    protected function contienePalabraProhibida(string $texto): bool
    {
        $textoLower = mb_strtolower($texto);

        foreach ($this->palabrasProhibidas as $palabra) {
            if (str_contains($textoLower, mb_strtolower($palabra))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Método administrativo para levantar sanciones si la directiva lo autoriza.
     */
    public function perdonarVecino(User $user): void
    {
        $user->advertencias_count = 0;
        $user->estado = 'activo';
        $user->ultimo_mensaje_advertencia = null;
        $user->fecha_advertencia = null;
        $user->mensaje_bloqueo = null;
        $user->fecha_bloqueo = null;
        $user->save();
    }
}