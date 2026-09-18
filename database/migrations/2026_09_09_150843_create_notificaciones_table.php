<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('titulo');
            $table->text('mensaje');

            /*
             * Tipo de notificación:
             * asamblea, comunicado, movimiento,
             * aviso, recordatorio, zoe, etc.
             */
            $table->string('tipo', 50)->default('aviso');

            /*
             * Información adicional para Android.
             *
             * Ejemplo:
             * {
             *     "asamblea_id": 15
             * }
             */
            $table->json('data')->nullable();

            $table->boolean('leida')->default(false);
            $table->timestamp('fecha_lectura')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'leida']);
            $table->index(['user_id', 'created_at']);
            $table->index('tipo');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};