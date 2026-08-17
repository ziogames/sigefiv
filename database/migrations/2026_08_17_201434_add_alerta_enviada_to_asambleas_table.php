<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar control de envío de alerta.
     */
    public function up(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {

            $table->boolean('alerta_enviada')
                ->default(false)
                ->after('estado');

            $table->timestamp('alerta_enviada_at')
                ->nullable()
                ->after('alerta_enviada');

        });
    }

    /**
     * Revertir cambios.
     */
    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {

            $table->dropColumn([
                'alerta_enviada',
                'alerta_enviada_at',
            ]);

        });
    }
};