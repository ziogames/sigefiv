<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memorias_sigi', function (Blueprint $table) {

            $table->id();

            /*
             * Usuario propietario de la memoria.
             *
             * NULL = memoria global de SIGI.
             */
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Tipo de memoria.
             *
             * Ejemplos:
             * preferencia
             * dato
             * contexto
             * sistema
             */
            $table->string('tipo', 50);

            /*
             * Nombre identificador de la memoria.
             */
            $table->string('clave', 150);

            /*
             * Contenido que SIGI debe recordar.
             */
            $table->text('contenido');

            /*
             * 1 = poca importancia
             * 5 = máxima importancia
             */
            $table->unsignedTinyInteger('importancia')
                ->default(3);

            $table->timestamps();

            /*
             * Evita memorias duplicadas para
             * el mismo usuario y clave.
             */
            $table->unique([
                'usuario_id',
                'tipo',
                'clave',
            ]);

            $table->index([
                'usuario_id',
                'tipo',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memorias_sigi');
    }
};