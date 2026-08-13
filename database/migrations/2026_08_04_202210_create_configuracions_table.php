<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracions', function (Blueprint $table) {

            $table->id();

            $table->string('nombre_sistema');

            $table->string('organizacion');

            $table->string('direccion')->nullable();

            $table->string('telefono')->nullable();

            $table->string('correo')->nullable();

            $table->string('logo')->nullable();

            $table->string('moneda')->default('Sol Peruano');

            $table->string('simbolo_moneda')->default('S/');

            $table->integer('decimales')->default(2);

            $table->string('zona_horaria')
                  ->default('America/Lima');

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracions');
    }
};