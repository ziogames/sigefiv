<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->string('nombre_sistema')
                  ->default('SIGEFIV');

            $table->string('organizacion')
                  ->nullable();

            $table->string('direccion')
                  ->nullable();

            $table->string('telefono')
                  ->nullable();

            $table->string('correo')
                  ->nullable();

            $table->string('logo')
                  ->nullable();

            $table->string('moneda')
                  ->default('Sol Peruano');

            $table->string('simbolo_moneda')
                  ->default('S/');

            $table->integer('decimales')
                  ->default(2);

            $table->string('zona_horaria')
                  ->default('America/Lima');

        });
    }

    public function down(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->dropColumn([

                'nombre_sistema',
                'organizacion',
                'direccion',
                'telefono',
                'correo',
                'logo',
                'moneda',
                'simbolo_moneda',
                'decimales',
                'zona_horaria',

            ]);

        });
    }
};