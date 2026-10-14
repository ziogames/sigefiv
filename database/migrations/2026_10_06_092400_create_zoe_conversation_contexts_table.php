<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zoe_conversation_contexts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('usuario_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->jsonb('contexto')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zoe_conversation_contexts');
    }
};