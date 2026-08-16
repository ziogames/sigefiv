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
        ?string $url = null
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

        $payload = json_encode([
            'title' => $titulo,
            'body' => $mensaje,
            'url' => $url ?? '/',
            'icon' => '/assets/pwa/icon-192.png',
            'badge' => '/assets/pwa/icon-192.png',
        ], JSON_UNESCAPED_UNICODE);

        $report = $webPush->sendOneNotification(
            $subscription,
            $payload
        );

        if ($report->isSuccess()) {
            return true;
        }

        return false;
    }
}