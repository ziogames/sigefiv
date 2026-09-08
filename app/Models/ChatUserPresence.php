<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatUserPresence extends Model
{
    use HasFactory;

    /**
     * Tabla asociada.
     */
    protected $table = 'chat_user_presence';

    /**
     * Campos que pueden asignarse masivamente.
     */
    protected $fillable = [
        'user_id',
        'ultimo_visto_at',
    ];

    /**
     * Conversión de atributos.
     */
    protected function casts(): array
    {
        return [
            'ultimo_visto_at' => 'datetime',
        ];
    }

    /**
     * Usuario asociado a esta presencia.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Determina si el usuario está actualmente en línea.
     *
     * Consideramos conectado a un usuario cuya última
     * actividad ocurrió dentro de los últimos 30 segundos.
     */
    public function estaEnLinea(): bool
    {
        if (!$this->ultimo_visto_at) {
            return false;
        }

        return $this->ultimo_visto_at->greaterThan(
            now()->subSeconds(30)
        );
    }
}