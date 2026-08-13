<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::table('configuracions', function (Blueprint $table) {

        $table->boolean('bitacora_activa')
              ->default(true);

        $table->boolean('sesion_unica')
              ->default(false);

        $table->boolean('bloqueo_intentos')
              ->default(true);

        $table->boolean('expirar_password')
              ->default(false);

        $table->unsignedInteger('intentos_login')
              ->default(5);

        $table->unsignedInteger('tiempo_sesion')
              ->default(30);

        $table->unsignedInteger('longitud_password')
              ->default(8);

    });
}

    /**
     * Reverse the migrations.
     */
   public function down(): void
{
    Schema::table('configuracions', function (Blueprint $table) {

        $table->dropColumn([
            'bitacora_activa',
            'sesion_unica',
            'bloqueo_intentos',
            'expirar_password',
            'intentos_login',
            'tiempo_sesion',
            'longitud_password'
        ]);

    });
}
};
