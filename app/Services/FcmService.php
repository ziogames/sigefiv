<?php

namespace App\Services;

use App\Models\FcmToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FcmService
{
    public function __construct(
        private readonly Messaging $messaging
    ) {
    }

    /**
     * Envía una notificación a un token FCM específico.
     */
    public function enviarAUnToken(
        string $token,
        string $titulo,
        string $mensaje,
        array $data = []
    ): void {
        $datos = [];

        foreach ($data as $clave => $valor) {
            $datos[(string) $clave] = (string) $valor;
        }

        $notificacion = Notification::create(
            $titulo,
            $mensaje
        );

        $mensajeFcm = CloudMessage::new()
            ->withToken($token)
            ->withNotification($notificacion);

        if (!empty($datos)) {
            $mensajeFcm = $mensajeFcm->withData($datos);
        }

        $this->messaging->send($mensajeFcm);
    }

    /**
     * Envía una notificación a todos los dispositivos
     * Android que tengan un token FCM activo.
     *
     * Devuelve la cantidad de dispositivos a los que
     * Firebase confirmó el envío.
     */
    public function enviarAUsuarios(
        string $titulo,
        string $mensaje,
        array $data = []
    ): int {
        $tokens = FcmToken::query()
            ->where('activo', true)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return 0;
        }

        $enviados = 0;

        foreach ($tokens as $token) {
            try {

                $this->enviarAUnToken(
                    token: $token,
                    titulo: $titulo,
                    mensaje: $mensaje,
                    data: $data
                );

                $enviados++;

            } catch (Throwable) {
                /*
                 * Un token que falle no debe impedir
                 * que los demás dispositivos reciban
                 * la notificación.
                 *
                 * Más adelante podremos marcar
                 * automáticamente los tokens inválidos
                 * como inactivos.
                 */
                continue;
            }
        }

        return $enviados;
    }
}