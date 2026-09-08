<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de eventos inteligentes de SIGI.
     */
    public function up(): void
    {
        Schema::create('sigi_eventos', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Conversación
            |--------------------------------------------------------------------------
            |
            | Evento perteneciente a una conversación concreta.
            |
            */

            $table->foreignId('conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Tipo de evento
            |--------------------------------------------------------------------------
            */

            $table->string('categoria', 50);


            /*
            |--------------------------------------------------------------------------
            | Estado del evento
            |--------------------------------------------------------------------------
            |
            | abierto      = SIGI sigue recopilando información.
            | resuelto     = el problema fue solucionado.
            | cerrado      = SIGI dejó de seguirlo.
            |
            */

            $table->string('estado', 30)
                ->default('abierto');


            /*
            |--------------------------------------------------------------------------
            | Resumen
            |--------------------------------------------------------------------------
            |
            | Resumen generado/actualizado por SIGI.
            |
            */

            $table->text('resumen')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Contadores
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('total_reportes')
                ->default(0);

            $table->unsignedInteger('reportes_problema')
                ->default(0);

            $table->unsignedInteger('reportes_resueltos')
                ->default(0);


            /*
            |--------------------------------------------------------------------------
            | Prioridad
            |--------------------------------------------------------------------------
            */

            $table->string('prioridad', 20)
                ->default('baja');


            /*
            |--------------------------------------------------------------------------
            | Última actividad
            |--------------------------------------------------------------------------
            */

            $table->timestamp('ultimo_reporte_at')
                ->nullable();

            $table->timestamp('ultima_intervencion_at')
                ->nullable();

            $table->timestamp('resuelto_at')
                ->nullable();

            $table->timestamp('cerrado_at')
                ->nullable();


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'conversation_id',
                'categoria',
                'estado',
            ]);

            $table->index([
                'categoria',
                'estado',
            ]);

            $table->index('prioridad');

            $table->index('ultimo_reporte_at');
        });
    }

    /**
     * Elimina la tabla de eventos de SIGI.
     */
    public function down(): void
    {
        Schema::dropIfExists('sigi_eventos');
    }
};