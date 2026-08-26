<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('periodos', 'fecha_cierre')) {

            Schema::table('periodos', function (Blueprint $table) {

                $table->timestamp('fecha_cierre')
                    ->nullable()
                    ->after('estado');
            });
        }
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        if (Schema::hasColumn('periodos', 'fecha_cierre')) {

            Schema::table('periodos', function (Blueprint $table) {

                $table->dropColumn('fecha_cierre');
            });
        }
    }
};