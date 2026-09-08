<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatMessage extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Campos que pueden asignarse masivamente.
     */
    protected $fillable = [
        'conversation_id',
        'user_id',
        'tipo',
        'mensaje',
        'mensaje_padre_id',
        'reply_to_id',
        'editado',
    ];

    /**
     * Conversación a la que pertenece el mensaje.
     */
    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(
            ChatConversation::class,
            'conversation_id'
        );
    }

    /**
     * Usuario que envió el mensaje.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Mensaje al que este mensaje está respondiendo
     * mediante mensaje_padre_id.
     */
    public function mensajePadre(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'mensaje_padre_id'
        );
    }

    /**
     * Mensaje al que este mensaje está respondiendo
     * mediante reply_to_id.
     */
    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reply_to_id'
        );
    }

    /**
     * Respuestas de este mensaje.
     */
    public function respuestas()
    {
        return $this->hasMany(
            self::class,
            'mensaje_padre_id'
        );
    }

    /**
     * Conversiones de atributos.
     */
    protected function casts(): array
    {
        return [
            'editado' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }
    public function reacciones(): HasMany
{
    return $this->hasMany(ChatMessageReaction::class, 'chat_message_id');
}
}