<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('zoe_feedback');

        Schema::create('zoe_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->string('mensaje_hash', 64);
            $table->text('mensaje_texto');
            $table->string('voto', 20);
            $table->timestamps();

            $table->unique(['usuario_id', 'mensaje_hash'], 'zoe_feedback_usuario_mensaje_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoe_feedback');
    }
};