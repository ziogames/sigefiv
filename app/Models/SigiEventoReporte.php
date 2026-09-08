<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SigiEventoReporte extends Model
{
    use HasFactory;

    /**
     * Campos que pueden asignarse masivamente.
     */
    protected $fillable = [
        'evento_id',
        'message_id',
        'user_id',
        'tipo',
        'mensaje',
        'confianza',
        'contextual',
    ];

    /**
     * Evento de SIGI al que pertenece el reporte.
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(
            SigiEvento::class,
            'evento_id'
        );
    }

    /**
     * Mensaje original del Chat Vecinal.
     */
    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(
            ChatMessage::class,
            'message_id'
        );
    }

    /**
     * Usuario que realizó el reporte.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /**
     * Conversión de atributos.
     */
    protected function casts(): array
    {
        return [
            'confianza' => 'float',
            'contextual' => 'boolean',
        ];
    }

    /**
     * Indica si el análisis tuvo contexto.
     */
    public function fueContextual(): bool
    {
        return $this->contextual;
    }

    /**
     * Indica si el análisis tiene alta confianza.
     */
    public function tieneAltaConfianza(): bool
    {
        return $this->confianza >= 0.80;
    }
}