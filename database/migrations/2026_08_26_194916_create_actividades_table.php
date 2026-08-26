<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Usuario
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Información de actividad
            |--------------------------------------------------------------------------
            */

            $table->string('modulo', 100);

            $table->string('accion', 100);

            $table->string('ruta', 255)->nullable();


            /*
            |--------------------------------------------------------------------------
            | Información técnica
            |--------------------------------------------------------------------------
            */

            $table->string('ip', 45)->nullable();

            $table->text('user_agent')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Fechas
            |--------------------------------------------------------------------------
            */

            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            |
            | La tabla de actividades puede crecer rápidamente.
            | Estos índices facilitarán las consultas del panel
            | de actividad.
            |
            */

            $table->index('modulo');

            $table->index('created_at');

        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};