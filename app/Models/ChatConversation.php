<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    use HasFactory;

    /**
     * Campos que pueden asignarse masivamente.
     */
    protected $fillable = [
        'nombre',
        'tipo',
        'descripcion',
        'asamblea_id',
        'activo',
    ];

    /**
     * Conversación asociada a una asamblea.
     */
    public function asamblea(): BelongsTo
    {
        return $this->belongsTo(Asamblea::class);
    }

    /**
     * Usuarios que pertenecen a la conversación.
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'chat_conversation_user',
            'conversation_id',
            'user_id'
        )->withPivot([
            'rol',
            'ultimo_leido_at',
        ])->withTimestamps();
    }

    /**
     * Mensajes de la conversación.
     */
    public function mensajes(): HasMany
    {
        return $this->hasMany(
            ChatMessage::class,
            'conversation_id'
        );
    }

    /**
     * Devuelve si la conversación está activa.
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}