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
        Schema::table('chat_conversation_user', function (Blueprint $table) {
            $table->unsignedBigInteger('ultimo_leido_message_id')
                ->nullable()
                ->after('ultimo_leido_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_conversation_user', function (Blueprint $table) {
            $table->dropColumn('ultimo_leido_message_id');
        });
    }
};