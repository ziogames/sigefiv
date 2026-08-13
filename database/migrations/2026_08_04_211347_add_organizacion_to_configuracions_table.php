<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->string('presidente')->nullable();

            $table->string('tesorero')->nullable();

            $table->string('secretario')->nullable();

            $table->string('ruc')->nullable();

            $table->string('web')->nullable();

            $table->string('facebook')->nullable();

            $table->string('instagram')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('configuracions', function (Blueprint $table) {

            $table->dropColumn([

                'presidente',

                'tesorero',

                'secretario',

                'ruc',

                'web',

                'facebook',

                'instagram',

            ]);

        });
    }
};