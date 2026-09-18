<?php

namespace App\Console\Commands;

use App\Models\FcmToken;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Throwable;

class ProbarFcmTodos extends Command
{
    protected $signature = 'fcm:probar-todos';

    protected $description =
        'Envía una notificación FCM de prueba a todos los dispositivos activos';

    public function handle(FcmService $fcmService): int
    {
        $this->info('Buscando dispositivos FCM activos...');

        $tokens = FcmToken::query()
            ->where('activo', true)
            ->whereNotNull('token')
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        $total = $tokens->count();

        if ($total === 0) {
            $this->warn(
                'No se encontraron dispositivos FCM activos.'
            );

            return self::SUCCESS;
        }

        $this->info(
            "Dispositivos encontrados: {$total}"
        );

        $enviados = 0;
        $fallidos = 0;
        $desactivados = 0;

        foreach ($tokens as $indice => $token) {

            $numero = $indice + 1;

            try {

                $fcmService->enviarAUnToken(
                    token: $token,
                    titulo: 'Prueba SIGEFIV',
                    mensaje: 'Esta es una prueba de notificación para todos los usuarios.',
                    data: [
                        'tipo' => 'prueba',
                        'origen' => 'laravel',
                    ]
                );

                $enviados++;

                $this->line(
                    "  [{$numero}/{$total}] ✓ Enviado"
                );

            } catch (Throwable $e) {

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

                if ($tokenInvalido) {

                    $fcmToken = FcmToken::query()
                        ->where('token', $token)
                        ->first();

                    if ($fcmToken) {

                        $fcmToken->update([
                            'activo' => false,
                        ]);

                        $desactivados++;

                        $this->warn(
                            "  [{$numero}/{$total}] ⚠ Token inválido → desactivado"
                        );

                        continue;
                    }
                }

                $fallidos++;

                $this->error(
                    "  [{$numero}/{$total}] ✗ Error: {$mensajeError}"
                );
            }
        }

        $this->newLine();

        $this->info('========================================');
        $this->info('     PRUEBA FCM MASIVA FINALIZADA');
        $this->info('========================================');

        $this->info(
            "Total dispositivos: {$total}"
        );

        $this->info(
            "Enviados: {$enviados}"
        );

        $this->info(
            "Tokens desactivados: {$desactivados}"
        );

        $this->warn(
            "Otros fallidos: {$fallidos}"
        );

        return $fallidos > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}