
<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Los campos ya se crean en la migración inicial.
        // Se conserva esta migración por compatibilidad
        // con las bases de datos donde ya figura como ejecutada.
    }

    public function down(): void
    {
        // No eliminar campos: pertenecen a la migración inicial.
    }
};
