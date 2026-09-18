<?php

namespace App\Services;

use App\Models\FcmToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\AndroidConfig;
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

   $androidConfig = AndroidConfig::fromArray([
    'priority' => 'high',
    'notification' => [
        'channel_id' => 'sigefiv_notificaciones',
        'color' => '#159447',
        'sound' => 'default',
    ],
]);

    $mensajeFcm = CloudMessage::new()
        ->withToken($token)
        ->withNotification($notificacion)
        ->withData($datos)
        ->withAndroidConfig($androidConfig);

    $this->messaging->send($mensajeFcm);
}
    /**
     * Envía una notificación a todos los dispositivos
     * Android que tengan un token FCM activo.
     *
     * Los tokens inválidos o no registrados se desactivan
     * automáticamente para evitar futuros intentos de envío.
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
            ->whereNotNull('token')
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

            } catch (Throwable $e) {

                /*
                 * Buscamos el registro exacto del token
                 * que produjo el error.
                 */
                $fcmToken = FcmToken::query()
                    ->where('token', $token)
                    ->first();

                /*
                 * Firebase puede devolver errores como:
                 *
                 * NotRegistered
                 * registration-token-not-registered
                 *
                 * En ambos casos el dispositivo ya no debe
                 * recibir notificaciones con este token.
                 */
                $mensajeError = $e->getMessage();

                $tokenInvalido =
                    str_contains(
                        $mensajeError,
                        'NotRegistered'
                    )
                    ||
                    str_contains(
                        $mensajeError,
                        'registration-token-not-registered'
                    );

                if (
                    $tokenInvalido &&
                    $fcmToken
                ) {

                    $fcmToken->update([
                        'activo' => false,
                    ]);

                    continue;
                }

                /*
                 * Cualquier otro error no debe detener
                 * el envío a los demás dispositivos.
                 */
                continue;
            }
        }

        return $enviados;
    }
  /**
 * Envía un evento silencioso a todos los dispositivos
 * Android que tengan un token FCM activo.
 *
 * Este método NO muestra una notificación visual.
 * Se utiliza para sincronizar información en tiempo real.
 */
public function enviarEventoAUsuarios(
    array $data = []
): int {
    $tokens = FcmToken::query()
        ->where('activo', true)
        ->whereNotNull('token')
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

            $datos = [];

            foreach ($data as $clave => $valor) {
                $datos[(string) $clave] = (string) $valor;
            }

            $mensajeFcm = CloudMessage::new()
                ->withToken($token)
                ->withData($datos);

            $this->messaging->send($mensajeFcm);

            $enviados++;

        } catch (Throwable $e) {

            $fcmToken = FcmToken::query()
                ->where('token', $token)
                ->first();

            $mensajeError = $e->getMessage();

            $tokenInvalido =
                str_contains(
                    $mensajeError,
                    'NotRegistered'
                )
                ||
                str_contains(
                    $mensajeError,
                    'registration-token-not-registered'
                );

            if (
                $tokenInvalido &&
                $fcmToken
            ) {
                $fcmToken->update([
                    'activo' => false,
                ]);
            }

            /*
             * Un error en un dispositivo no debe
             * detener el envío a los demás.
             */
            continue;
        }
    }

    return $enviados;
}
}