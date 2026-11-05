<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // Evidencia oculta para mensajes interceptados por ZOE
            if (!Schema::hasColumn('chat_messages', 'bloqueado_moderacion')) {
                $table->boolean('bloqueado_moderacion')->default(false)->after('editado');
            }
            if (!Schema::hasColumn('chat_messages', 'motivo_moderacion')) {
                $table->string('motivo_moderacion', 500)->nullable()->after('bloqueado_moderacion');
            }

            // Auditoría para saber qué usuario/directiva eliminó el mensaje
            if (!Schema::hasColumn('chat_messages', 'deleted_by')) {
                $table->foreignId('deleted_by')
                    ->nullable()
                    ->after('deleted_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'deleted_by')) {
                $table->dropConstrainedForeignId('deleted_by');
            }
            if (Schema::hasColumn('chat_messages', 'motivo_moderacion')) {
                $table->dropColumn('motivo_moderacion');
            }
            if (Schema::hasColumn('chat_messages', 'bloqueado_moderacion')) {
                $table->dropColumn('bloqueado_moderacion');
            }
        });
    }
};