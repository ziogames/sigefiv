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
        Schema::create('chat_conversations', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Información de la conversación
            |--------------------------------------------------------------------------
            */

            $table->string('nombre');

            $table->string('tipo')
                ->default('publico');

            $table->text('descripcion')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Asamblea relacionada
            |--------------------------------------------------------------------------
            |
            | Una conversación podrá estar asociada posteriormente
            | a una asamblea determinada.
            |
            */

            $table->foreignId('asamblea_id')
                ->nullable()
                ->constrained('asambleas')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('tipo');
            $table->index('activo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversations');
    }
};