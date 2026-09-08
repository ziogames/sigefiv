<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea los reportes individuales asociados a los eventos de SIGI.
     */
    public function up(): void
    {
        Schema::create('sigi_evento_reportes', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Evento
            |--------------------------------------------------------------------------
            */

            $table->foreignId('evento_id')
                ->constrained('sigi_eventos')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Mensaje que originó el reporte
            |--------------------------------------------------------------------------
            |
            | Conservamos la relación con el mensaje original.
            |
            */

            $table->foreignId('message_id')
                ->constrained('chat_messages')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Usuario que realizó el reporte
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Tipo específico del reporte
            |--------------------------------------------------------------------------
            |
            | Ejemplos:
            |
            | sin_servicio
            | baja_presion
            | servicio_intermitente
            | restablecido
            | informacion
            |
            */

            $table->string('tipo', 50);


            /*
            |--------------------------------------------------------------------------
            | Texto original
            |--------------------------------------------------------------------------
            |
            | Guardamos lo que escribió realmente el vecino.
            |
            */

            $table->text('mensaje');


            /*
            |--------------------------------------------------------------------------
            | Confianza del análisis
            |--------------------------------------------------------------------------
            |
            | Valor entre 0 y 1.
            |
            | Por ahora será calculado por SIGI.
            |
            */

            $table->decimal('confianza', 5, 4)
                ->default(1.0000);


            /*
            |--------------------------------------------------------------------------
            | Reporte contextual
            |--------------------------------------------------------------------------
            |
            | Indica si SIGI entendió el reporte utilizando
            | mensajes anteriores.
            |
            */

            $table->boolean('contextual')
                ->default(false);


            /*
            |--------------------------------------------------------------------------
            | Fecha
            |--------------------------------------------------------------------------
            */

            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'evento_id',
                'tipo',
            ]);

            $table->index([
                'user_id',
                'created_at',
            ]);

            $table->index('message_id');
        });
    }

    /**
     * Elimina la tabla.
     */
    public function down(): void
    {
        Schema::dropIfExists('sigi_evento_reportes');
    }
};