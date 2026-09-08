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
        Schema::create('chat_conversation_user', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Conversación
            |--------------------------------------------------------------------------
            */

            $table->foreignId('conversation_id')
                ->constrained('chat_conversations')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Usuario
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Rol dentro de la conversación
            |--------------------------------------------------------------------------
            |
            | miembro      = usuario normal de la conversación.
            | administrador = puede administrar la conversación.
            |
            */

            $table->string('rol')
                ->default('miembro');

            /*
            |--------------------------------------------------------------------------
            | Lectura de mensajes
            |--------------------------------------------------------------------------
            |
            | Se utilizará posteriormente para calcular mensajes no leídos.
            |
            */

            $table->timestamp('ultimo_leido_at')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Un usuario no puede pertenecer dos veces
            | a la misma conversación.
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'conversation_id',
                'user_id',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index('user_id');
            $table->index('rol');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_conversation_user');
    }
};