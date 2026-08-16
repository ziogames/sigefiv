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
        Schema::create('climas', function (Blueprint $table) {

            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Ubicación
            |--------------------------------------------------------------------------
            */

            $table->string('ubicacion', 150);

            $table->string('pais', 100)->nullable();

            $table->decimal('latitud', 10, 7);

            $table->decimal('longitud', 10, 7);


            /*
            |--------------------------------------------------------------------------
            | Datos meteorológicos
            |--------------------------------------------------------------------------
            */

            $table->decimal('temperatura', 5, 2)->nullable();

            $table->decimal('sensacion', 5, 2)->nullable();

            $table->unsignedSmallInteger('humedad')->nullable();

            $table->decimal('viento', 6, 2)->nullable();

            $table->unsignedSmallInteger('codigo')->nullable();

            $table->string('descripcion', 100)->nullable();


            /*
            |--------------------------------------------------------------------------
            | Proveedor
            |--------------------------------------------------------------------------
            |
            | Nos permitirá posteriormente agregar otros proveedores.
            |
            */

            $table->string('proveedor', 50)
                ->default('open-meteo');


            /*
            |--------------------------------------------------------------------------
            | Control del caché
            |--------------------------------------------------------------------------
            */

            $table->timestamp('consultado_en')->nullable();

            $table->timestamp('expira_en')->nullable();


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['ubicacion', 'proveedor']
            );

            $table->index(
                'expira_en'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('climas');
    }
};