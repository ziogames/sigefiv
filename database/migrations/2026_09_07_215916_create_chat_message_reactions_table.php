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
        Schema::create('chat_message_reactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('chat_message_id')
                ->constrained('chat_messages')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('emoji', 20);

            $table->timestamps();

            /*
             * Un usuario solo puede tener una reacción
             * del mismo emoji sobre un mismo mensaje.
             */
            $table->unique(
                ['chat_message_id', 'user_id', 'emoji'],
                'chat_message_reactions_unique'
            );

            /*
             * Índices para acelerar las consultas
             * de reacciones por mensaje.
             */
            $table->index(
                ['chat_message_id'],
                'chat_message_reactions_message_index'
            );

            $table->index(
                ['user_id'],
                'chat_message_reactions_user_index'
            );
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_message_reactions');
    }
};