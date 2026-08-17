<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsambleaAgenda extends Model
{
    protected $fillable = [
        'asamblea_id',
        'numero',
        'descripcion',
    ];

    /**
     * Asamblea a la que pertenece este punto de agenda.
     */
    public function asamblea(): BelongsTo
    {
        return $this->belongsTo(
            Asamblea::class,
            'asamblea_id'
        );
    }
}