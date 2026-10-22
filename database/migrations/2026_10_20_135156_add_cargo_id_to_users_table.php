<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable por si el vecino no tiene ningún cargo en la directiva
            $table->foreignId('cargo_id')
                  ->nullable()
                  ->constrained('cargos')
                  ->nullOnDelete(); // Si se borra el cargo, el usuario se queda con cargo_id en null
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['cargo_id']);
            $table->dropColumn('cargo_id');
        });
    }
};