<?php

namespace App\Console\Commands;

use App\Models\ChatDevice;
use Illuminate\Console\Command;

class ChatDevicesApagarVencidos extends Command
{
    /**
     * Nombre y firma del comando.
     */
    protected $signature = 'chat:devices-apagar-vencidos';

    /**
     * Descripción del comando.
     */
    protected $description =
        'Apaga los dispositivos cuyo tiempo programado ya venció';

    /**
     * Ejecuta el comando.
     */
    public function handle(): int
    {
        $dispositivos =
            ChatDevice::where('estado', 'encendido')
                ->whereNotNull('apagado_programado')
                ->where(
                    'apagado_programado',
                    '<=',
                    now()
                )
                ->get();

        if ($dispositivos->isEmpty()) {
            $this->info(
                'No hay dispositivos pendientes de apagado.'
            );

            return self::SUCCESS;
        }

        foreach ($dispositivos as $dispositivo) {
            $dispositivo->update([
                'estado' => 'apagado',
                'apagado_programado' => null,
            ]);

            $this->info(
                'Dispositivo apagado: '
                .$dispositivo->nombre
            );
        }

        $this->info(
            'Dispositivos apagados: '
            .$dispositivos->count()
        );

        return self::SUCCESS;
    }
}