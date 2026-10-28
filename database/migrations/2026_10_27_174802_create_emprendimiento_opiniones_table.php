<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprendimiento_opiniones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emprendimiento_id')->constrained('emprendimientos')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Vecino que califica
            $table->integer('calificacion'); // Del 1 al 5
            $table->text('comentario')->nullable();
            $table->json('aspectos_destacados')->nullable(); // Ej: ["Calidad", "Precio", "Puntualidad"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprendimiento_opiniones');
    }
};