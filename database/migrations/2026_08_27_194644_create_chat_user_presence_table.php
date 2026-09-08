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
        Schema::create('chat_user_presence', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Usuario
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Última actividad
            |--------------------------------------------------------------------------
            |
            | Este campo se actualizará periódicamente desde el navegador
            | mientras el usuario tenga SIGEFIV abierto.
            |
            */

            $table->timestamp('ultimo_visto_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timestamps
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índice
            |--------------------------------------------------------------------------
            */

            $table->index('ultimo_visto_at');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_user_presence');
    }
};