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
       Schema::create('categorias', function (Blueprint $table) {

    $table->id();

    $table->string('nombre');

    $table->enum('tipo',[
        'Ingreso',
        'Egreso'
    ]);

    $table->string('icono')
          ->default('cil-folder');

    $table->string('color')
          ->default('primary');

    $table->boolean('activo')
          ->default(true);

    $table->unsignedInteger('orden')
          ->default(0);

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
