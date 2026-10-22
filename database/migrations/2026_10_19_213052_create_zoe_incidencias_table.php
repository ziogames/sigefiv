<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoe_incidencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Vecino que reporta (si aplica)
            $table->text('mensaje')->nullable();             // Texto o consulta enviada a ZOE
            $table->string('imagen_path')->nullable();       // Ruta del archivo en public/chat_zoe/...
            $table->string('estado')->default('pendiente');  // pendiente, en proceso, resuelto
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoe_incidencias');
    }
};