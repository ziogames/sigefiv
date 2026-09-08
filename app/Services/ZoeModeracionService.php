<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChatMessage;
use Carbon\Carbon;

class ZoeModeracionService
{
   protected array $palabrasProhibidas = [
        // Insultos y groserías directas
        'mierda',
        'estupidos',
        'estupideces',
        'idioteces',
        'carajo',
        'imbécil',
        'imbéciles',
        'idiota',
        'idiotas',
        'tonto',
        'tontos',
        'tarado',
        'tarados',
        'pendejo',
        'pendejos',
        'viejas chillonas',
        'cosas de hombres',
        'no me jorobes',
        'jorobes la paciencia',
        'no me calientes',
        'chillonas',
        'malditas',
        "provoques",
        // Frases intimidantes o amenazas de agresión/visita
        'mandar directamente a tu casa',
        'olvidar de ser vecino',
        'mejor evítame',
        'evítame sra',
        'te voy a buscar',
        'te voy a caer',
        'ir a tu casa',
        'afuera nos vemos',
        'vernos las caras',
        'no sabes con quién te metes',
        'atente a las consecuencias',
        'se van a arrepentir'
    ];
    public function procesarMensaje(int $conversationId, User $user, string $mensajeTexto): array
    {
        if ($this->contienePalabraProhibida($mensajeTexto)) {

            // Obtenemos el número actual de advertencias asegurando que sea entero
            $advertenciasActuales = intval($user->advertencias_count ?? 0);

            // CASO A: PRIMER STRIKE (Advertencia)
            if ($advertenciasActuales === 0) {
                $user->increment('advertencias_count');
                $user->estado = 'advertido';
                $user->ultimo_mensaje_advertencia = $mensajeTexto;
                $user->fecha_advertencia = Carbon::now();
                $user->save();

                $textoZoe = "⚠️ **Advertencia para @{$user->name}:** He detectado un lenguaje inapropiado en tu mensaje. Este chat es un espacio de convivencia vecinal basado en el respeto mutuo. Por favor, evita este tipo de expresiones; una nueva infracción resultará en la suspensión definitiva de tu cuenta.";

                ChatMessage::create([
                    'conversation_id' => $conversationId,
                    'user_id' => null,
                    'tipo' => 'sigi',
                    'mensaje' => $textoZoe,
                ]);

                return [
                    'bloquear_mensaje' => true,
                    'accion' => 'advertencia',
                    'message' => 'Tu mensaje ha sido bloqueado por infringir las normas de convivencia. Has recibido una advertencia.',
                    'motivo' => 'Primer strike registrado.'
                ];
            }

            // CASO B: SEGUNDO STRIKE O MÁS (Baneo definitivo)
            else {
                $user->increment('advertencias_count');
                $user->estado = 'bloqueado';
                $user->mensaje_bloqueo = $mensajeTexto;
                $user->fecha_bloqueo = Carbon::now();
                $user->save();

                $textoZoe = "🚫 **Suspensión para @{$user->name}:** Has infringido las normas de convivencia por segunda vez. Tu cuenta ha sido bloqueada permanentemente de este chat vecinal.";

                ChatMessage::create([
                    'conversation_id' => $conversationId,
                    'user_id' => null,
                    'tipo' => 'sigi',
                    'mensaje' => $textoZoe,
                ]);

                return [
                    'bloquear_mensaje' => true,
                    'accion' => 'bloqueo',
                    'message' => 'Su cuenta fue bloqueada por conducta inapropiada.',
                    'motivo' => 'Segundo strike alcanzado. Usuario bloqueado.'
                ];
            }
        }

        return [
            'bloquear_mensaje' => false,
            'accion' => 'permitido'
        ];
    }

    /**
     * Restablece el historial de infracciones de un vecino (Lista blanca).
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
}