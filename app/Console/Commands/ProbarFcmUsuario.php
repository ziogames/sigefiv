<?php

namespace App\Console\Commands;

use App\Services\NotificacionService;
use Illuminate\Console\Command;

class ProbarFcmUsuario extends Command
{
    protected $signature = 'fcm:probar-usuario {usuario_id}';

    protected $description = 'Envía una notificación FCM a todos los dispositivos de un usuario';

    public function handle(NotificacionService $notificacionService): int
    {
        $usuarioId = (int) $this->argument('usuario_id');

        $this->info("Enviando notificación al usuario ID: {$usuarioId}...");
        $this->newLine();

        $enviados = $notificacionService->enviarAUsuario(
            usuarioId: $usuarioId,
            titulo: 'Prueba SIGEFIV',
            mensaje: 'Esta notificación fue enviada directamente a tus dispositivos.',
            tipo: 'prueba',
            data: [
                'origen' => 'laravel',
            ]
        );

        $this->newLine();
        $this->line('========================================');
        $this->line('     PRUEBA FCM POR USUARIO');
        $this->line('========================================');
        $this->line("Usuario ID: {$usuarioId}");
        $this->line("Dispositivos enviados: {$enviados}");
        $this->line('========================================');

        return self::SUCCESS;
    }
}