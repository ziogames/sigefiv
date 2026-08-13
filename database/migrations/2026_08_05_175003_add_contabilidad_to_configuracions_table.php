<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->year('anio_inicio')
                ->nullable()
                ->after('color_principal');

            $table->unsignedTinyInteger('mes_inicio')
                ->nullable()
                ->after('anio_inicio');

            $table->decimal('saldo_apertura',12,2)
                ->default(0)
                ->after('mes_inicio');

            $table->boolean('contabilidad_iniciada')
                ->default(false)
                ->after('saldo_apertura');

        });
    }

    public function down(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->dropColumn([

                'anio_inicio',

                'mes_inicio',

                'saldo_apertura',

                'contabilidad_iniciada',

            ]);

        });
    }
};