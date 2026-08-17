<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asamblea extends Model
{
    protected $fillable = [
        'tipo',
        'titulo',
        'convoca',
        'sector',
        'grupo',
        'manzana',
        'lote',
        'fecha',
        'hora',
        'primera_citacion',
        'segunda_citacion',
        'lugar',
        'descripcion',
        'importancia',
        'estado',
        'created_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i',
        'primera_citacion' => 'datetime:H:i',
        'segunda_citacion' => 'datetime:H:i',
    ];

    /**
     * Usuario que creó la asamblea.
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /**
     * Puntos de agenda de la asamblea.
     */
    public function agendas(): HasMany
    {
        return $this->hasMany(
            AsambleaAgenda::class,
            'asamblea_id'
        )->orderBy('numero');
    }
}