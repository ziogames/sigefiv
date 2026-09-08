<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('chat_devices', function (Blueprint $table) {
        $table->id();

        $table->string('nombre');
        $table->string('tipo');
        $table->string('ubicacion')->nullable();

        $table->string('estado')->default('apagado');

        $table->dateTime('apagado_programado')->nullable();

        $table->foreignId('usuario_ultima_orden_id')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->string('ultima_orden')->nullable();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('chat_devices');
}
    
};
