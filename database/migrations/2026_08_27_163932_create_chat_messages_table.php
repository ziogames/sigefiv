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
        Schema::create('chat_messages', function (Blueprint $table) {

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
            | Usuario que envía el mensaje
            |--------------------------------------------------------------------------
            |
            | Para mensajes normales contiene el ID del usuario.
            | Para mensajes generados por SIGI será NULL.
            |
            */

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Tipo de mensaje
            |--------------------------------------------------------------------------
            |
            | usuario = mensaje escrito por un usuario.
            | sigi    = mensaje generado por SIGI.
            | sistema = mensaje generado automáticamente por SIGEFIV.
            |
            */

            $table->string('tipo')
                ->default('usuario');

            /*
            |--------------------------------------------------------------------------
            | Contenido
            |--------------------------------------------------------------------------
            */

            $table->text('mensaje');

            /*
            |--------------------------------------------------------------------------
            | Respuesta a otro mensaje
            |--------------------------------------------------------------------------
            |
            | Permite posteriormente responder directamente a un mensaje.
            |
            */

            $table->foreignId('mensaje_padre_id')
                ->nullable()
                ->constrained('chat_messages')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->boolean('editado')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Eliminación lógica
            |--------------------------------------------------------------------------
            |
            | No eliminamos físicamente los mensajes.
            | Esto permitirá mantener integridad y auditoría.
            |
            */

            $table->softDeletes();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index([
                'conversation_id',
                'created_at',
            ]);

            $table->index('user_id');
            $table->index('tipo');
            $table->index('mensaje_padre_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};