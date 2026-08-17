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
        Schema::create('asamblea_agendas', function (Blueprint $table) {

            $table->id();

            $table->foreignId('asamblea_id')
                ->constrained('asambleas')
                ->cascadeOnDelete();

            $table->unsignedInteger('numero');

            $table->text('descripcion');

            $table->timestamps();

            $table->index([
                'asamblea_id',
                'numero',
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asamblea_agendas');
    }
};