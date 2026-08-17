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
        'plantilla_citacion',
        'estado',
        'created_by',
        'alerta_enviada',
        'alerta_enviada_at',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora' => 'datetime:H:i',
        'primera_citacion' => 'datetime:H:i',
        'segunda_citacion' => 'datetime:H:i',
        'alerta_enviada' => 'boolean',
        'alerta_enviada_at' => 'datetime',
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