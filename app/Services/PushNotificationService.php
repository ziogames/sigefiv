<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    /**
     * Envía una notificación a una suscripción.
     */
    public function enviar(
        PushSubscription $suscripcion,
        string $titulo,
        string $mensaje,
        ?string $url = null,
        array $datosExtra = []
    ): bool {
        $auth = [
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);

        $subscription = Subscription::create([
            'endpoint' => $suscripcion->endpoint,

            'keys' => [
                'p256dh' => $suscripcion->public_key,
                'auth' => $suscripcion->auth_token,
            ],

            'contentEncoding' => $suscripcion->content_encoding,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Payload base
        |--------------------------------------------------------------------------
        */

        $payload = [
            'title' => $titulo,

            'body' => $mensaje,

            'url' => $url ?? '/',

            /*
            |--------------------------------------------------------------------------
            | Logo de la notificación
            |--------------------------------------------------------------------------
            */

            'icon' => '/assets/asambleas/logo-grupo.png',

            'badge' => '/assets/pwa/icon-192.png',

            /*
            |--------------------------------------------------------------------------
            | Identificación
            |--------------------------------------------------------------------------
            */

            'tipo' => 'general',

            'tag' => 'sigefiv-notificacion',
        ];


        /*
        |--------------------------------------------------------------------------
        | Datos adicionales
        |--------------------------------------------------------------------------
        |
        | Permite que otros módulos, especialmente Asambleas,
        | agreguen información al Push sin romper las
        | notificaciones existentes.
        |
        */

        if (!empty($datosExtra)) {

            $payload = array_merge(
                $payload,
                $datosExtra
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Convertir a JSON
        |--------------------------------------------------------------------------
        */

        $jsonPayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


        /*
        |--------------------------------------------------------------------------
        | Enviar Push
        |--------------------------------------------------------------------------
        */

        $report = $webPush->sendOneNotification(
            $subscription,
            $jsonPayload
        );


        /*
        |--------------------------------------------------------------------------
        | Resultado
        |--------------------------------------------------------------------------
        */

        if ($report->isSuccess()) {

            return true;

        }


        return false;
    }
}