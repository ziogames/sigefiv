<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emprendimiento_favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('emprendimiento_id')->constrained('emprendimientos')->onDelete('cascade');
            $table->timestamps();

            // Evita que un usuario marque dos veces el mismo emprendimiento como favorito
            $table->unique(['user_id', 'emprendimiento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emprendimiento_favoritos');
    }
};