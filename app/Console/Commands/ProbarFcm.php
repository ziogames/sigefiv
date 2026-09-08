<?php

namespace App\Console\Commands;

use App\Models\FcmToken;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Throwable;

class ProbarFcm extends Command
{
    protected $signature = 'fcm:probar';

    protected $description = 'Envía una notificación FCM de prueba al último dispositivo registrado';

    public function handle(
        FcmService $fcmService
    ): int {
        $this->info('Buscando un token FCM activo...');

        $fcmToken = FcmToken::query()
            ->where('activo', true)
            ->latest('ultimo_acceso')
            ->first();

        if (!$fcmToken) {
            $this->error(
                'No se encontró ningún token FCM activo.'
            );

            return self::FAILURE;
        }

        $this->info(
            'Token encontrado para el usuario ID: ' .
            $fcmToken->user_id
        );

        try {

            $fcmService->enviarAUnToken(
                token: $fcmToken->token,
                titulo: 'Prueba SIGEFIV',
                mensaje: 'Laravel acaba de enviar esta notificación a tu Android.',
                data: [
                    'tipo' => 'prueba',
                    'origen' => 'laravel',
                ]
            );

            $this->info(
                '========================================'
            );

            $this->info(
                'NOTIFICACIÓN FCM ENVIADA CORRECTAMENTE'
            );

            $this->info(
                '========================================'
            );

            return self::SUCCESS;

        } catch (Throwable $e) {

            $this->error(
                'No se pudo enviar la notificación FCM.'
            );

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }
}