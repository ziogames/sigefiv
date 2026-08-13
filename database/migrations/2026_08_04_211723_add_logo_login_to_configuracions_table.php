<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->string('logo')->nullable()->change();

            $table->string('favicon')->nullable();

            $table->string('imagen_login')->nullable();

            $table->string('color_principal')
                  ->default('#321fdb');

        });
    }

    public function down(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->dropColumn([

                'favicon',

                'imagen_login',

                'color_principal'

            ]);

        });
    }
};