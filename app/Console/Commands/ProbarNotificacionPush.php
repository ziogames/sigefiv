<?php

namespace App\Console\Commands;

use App\Models\PushSubscription;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class ProbarNotificacionPush extends Command
{
    protected $signature = 'push:probar';

    protected $description = 'Envía una notificación Push de prueba';

    public function handle(
        PushNotificationService $pushNotificationService
    ): int {
        $suscripcion = PushSubscription::latest()->first();

        if (!$suscripcion) {
            $this->error('No existe ninguna suscripción Push.');
            return self::FAILURE;
        }

        $this->info('Enviando notificación de prueba...');

        $resultado = $pushNotificationService->enviar(
            $suscripcion,
            'SIGEFIV',
            'Esta es una notificación de prueba.',
            '/dashboard'
        );

        if ($resultado) {
            $this->info('Notificación enviada correctamente.');
            return self::SUCCESS;
        }

        $this->error('No se pudo enviar la notificación.');
        return self::FAILURE;
    }
}