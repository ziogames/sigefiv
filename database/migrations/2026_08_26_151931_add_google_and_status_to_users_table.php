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
        Schema::table('users', function (Blueprint $table) {

            $table->string('google_id')
                ->nullable()
                ->unique()
                ->after('email');

            $table->string('estado', 20)
                ->default('activo')
                ->after('google_id');

            $table->boolean('recibir_notificaciones')
                ->default(true)
                ->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropUnique(['google_id']);

            $table->dropColumn([
                'google_id',
                'estado',
                'recibir_notificaciones',
            ]);
        });
    }
};