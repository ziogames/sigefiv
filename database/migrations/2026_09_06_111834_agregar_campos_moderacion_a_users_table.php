<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('advertencias_count')->default(0)->after('estado');
            $table->text('ultimo_mensaje_advertencia')->nullable()->after('advertencias_count');
            $table->timestamp('fecha_advertencia')->nullable()->after('ultimo_mensaje_advertencia');
            $table->text('mensaje_bloqueo')->nullable()->after('fecha_advertencia');
            $table->timestamp('fecha_bloqueo')->nullable()->after('mensaje_bloqueo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'advertencias_count',
                'ultimo_mensaje_advertencia',
                'fecha_advertencia',
                'mensaje_bloqueo',
                'fecha_bloqueo',
            ]);
        });
    }
};