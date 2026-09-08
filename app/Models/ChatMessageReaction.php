<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessageReaction extends Model
{
    use HasFactory;

    /**
     * Campos que pueden asignarse masivamente.
     */
    protected $fillable = [
        'chat_message_id',
        'user_id',
        'emoji',
    ];

    /**
     * Mensaje al que pertenece la reacción.
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(
            ChatMessage::class,
            'chat_message_id'
        );
    }

    /**
     * Usuario que realizó la reacción.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}