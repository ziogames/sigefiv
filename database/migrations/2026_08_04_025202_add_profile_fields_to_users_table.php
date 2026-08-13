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

            $table->string('telefono')->nullable()->after('email');

            $table->string('dni',20)->nullable()->after('telefono');

            $table->string('direccion')->nullable()->after('dni');

            $table->string('foto')->nullable()->after('direccion');

            $table->string('ultima_ip',45)->nullable()->after('foto');

            $table->timestamp('ultimo_acceso')->nullable()->after('ultima_ip');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([

                'telefono',

                'dni',

                'direccion',

                'foto',

                'ultima_ip',

                'ultimo_acceso'

            ]);

        });
    }
};