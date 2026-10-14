<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoeConversationContext extends Model
{
    protected $table = 'zoe_conversation_contexts';

    protected $fillable = [
        'usuario_id',
        'contexto',
    ];

    protected $casts = [
        'contexto' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }
}