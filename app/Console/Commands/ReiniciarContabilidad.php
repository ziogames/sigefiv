<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Movimiento;
use App\Models\Periodo;
use App\Models\Configuracion;

class ReiniciarContabilidad extends Command
{
    /**
     * Nombre del comando.
     */
    protected $signature = 'sigefiv:reiniciar-contabilidad';

    /**
     * Descripción.
     */
    protected $description = 'Elimina movimientos y periodos e inicializa nuevamente la contabilidad.';

    /**
     * Ejecutar comando.
     */
    public function handle(): int
    {
        if (!$this->confirm(
            '¿Está seguro de reiniciar la contabilidad?'
        )) {

            return self::SUCCESS;

        }

        DB::beginTransaction();

        try {

            Movimiento::truncate();

            Periodo::truncate();

            $config = Configuracion::first();

            if ($config) {

                $config->update([

                    'anio_inicio' => null,

                    'mes_inicio' => null,

                    'saldo_apertura' => 0,

                    'contabilidad_iniciada' => false,

                ]);

            }

            DB::commit();

            $this->newLine();

            $this->info('=====================================');

            $this->info(' CONTABILIDAD REINICIADA');

            $this->info('=====================================');

            $this->line('✓ Movimientos eliminados.');

            $this->line('✓ Períodos eliminados.');

            $this->line('✓ Configuración contable reiniciada.');

            $this->newLine();

            return self::SUCCESS;

        } catch (\Throwable $e) {

            DB::rollBack();

            $this->error($e->getMessage());

            return self::FAILURE;

        }
    }
}