<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crear tabla de tokens FCM.
     */
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('token')->unique();

            $table->string('plataforma', 20)
                ->default('android');

            $table->boolean('activo')
                ->default(true);

            $table->timestamp('ultimo_acceso')
                ->nullable();

            $table->timestamps();

            $table->index([
                'user_id',
                'activo',
            ]);
        });
    }

    /**
     * Eliminar tabla de tokens FCM.
     */
    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};