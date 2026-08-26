<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memorias_sigi', function (Blueprint $table) {

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('tipo', 50);

            $table->string('clave', 150);

            $table->text('contenido');

            $table->unsignedTinyInteger('importancia')
                ->default(3);

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
        Schema::table('memorias_sigi', function (Blueprint $table) {

            $table->dropForeign([
                'usuario_id'
            ]);

            $table->dropUnique(
                'memorias_sigi_usuario_id_tipo_clave_unique'
            );

            $table->dropIndex(
                'memorias_sigi_usuario_id_tipo_index'
            );

            $table->dropColumn([
                'usuario_id',
                'tipo',
                'clave',
                'contenido',
                'importancia',
            ]);
        });
    }
};