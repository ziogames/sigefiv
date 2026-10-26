<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprendimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // 👈 Cambiado a nullable para que no sea obligatorio
            $table->string('name');
            $table->string('slug');
            // 👈 Añadido el campo de palabras clave para las búsquedas de Zoe
            $table->string('keywords')->nullable();
            $table->text('description')->nullable();
            $table->string('phone');
            $table->boolean('show_whatsapp')->default(true);
            $table->string('modality')->default('ambos'); // local_emprendedor, domicilio_cliente, ambos, virtual
            $table->string('attention_zone')->nullable();
            $table->string('schedule')->nullable();
            $table->string('status')->default('draft'); // draft, published, paused, review
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprendimientos');
    }
};