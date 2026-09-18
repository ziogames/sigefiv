<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'user_id',
        'titulo',
        'mensaje',
        'tipo',
        'data',
        'leida',
        'fecha_lectura',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'leida' => 'boolean',
            'fecha_lectura' => 'datetime',
        ];
    }

    /**
     * Usuario que recibe la notificación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }
}