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
        Schema::table('asambleas', function (Blueprint $table) {

            $table->string('convoca')->nullable()->after('titulo');

            $table->string('sector')->nullable()->after('convoca');

            $table->string('grupo')->nullable()->after('sector');

            $table->string('manzana')->nullable()->after('grupo');

            $table->string('lote')->nullable()->after('manzana');

            $table->time('primera_citacion')->nullable()->after('fecha');

            $table->time('segunda_citacion')->nullable()->after('primera_citacion');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {

            $table->dropColumn([
                'convoca',
                'sector',
                'grupo',
                'manzana',
                'lote',
                'primera_citacion',
                'segunda_citacion',
            ]);

        });
    }
};