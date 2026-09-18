<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar preferencias de notificaciones a los tokens FCM.
     */
    public function up(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {

            $table->boolean('ingresos')
                ->default(true)
                ->after('activo');

            $table->boolean('egresos')
                ->default(true)
                ->after('ingresos');

            $table->boolean('zoe')
                ->default(true)
                ->after('egresos');

            $table->boolean('avisos')
                ->default(true)
                ->after('zoe');
        });
    }

    /**
     * Revertir las preferencias agregadas.
     */
    public function down(): void
    {
        Schema::table('fcm_tokens', function (Blueprint $table) {

            $table->dropColumn([
                'ingresos',
                'egresos',
                'zoe',
                'avisos',
            ]);
        });
    }
};