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
        Schema::create('periodos', function (Blueprint $table) {

            $table->id();

            $table->year('anio');

            $table->unsignedTinyInteger('mes');

            $table->string('nombre', 30);

            $table->decimal(
                'saldo_inicial',
                12,
                2
            )->default(0);

            $table->decimal(
                'total_ingresos',
                12,
                2
            )->default(0);

            $table->decimal(
                'total_egresos',
                12,
                2
            )->default(0);

            $table->decimal(
                'saldo_final',
                12,
                2
            )->default(0);

            $table->enum(
                'estado',
                [
                    'Abierto',
                    'Cerrado'
                ]
            )->default('Abierto');

            $table->timestamps();

            $table->unique([
                'anio',
                'mes'
            ]);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};