<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {
            $table->unsignedTinyInteger('plantilla_citacion')
                ->default(1)
                ->after('importancia');
        });
    }

    public function down(): void
    {
        Schema::table('asambleas', function (Blueprint $table) {
            $table->dropColumn('plantilla_citacion');
        });
    }
};