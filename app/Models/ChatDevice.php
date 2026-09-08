<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatDevice extends Model
{
    protected $table = 'chat_devices';

    protected $fillable = [
        'nombre',
        'tipo',
        'ubicacion',
        'estado',
        'apagado_programado',
        'usuario_ultima_orden_id',
        'ultima_orden',
    ];

    protected $casts = [
        'apagado_programado' => 'datetime',
    ];

    /**
     * Usuario que realizó la última orden.
     */
    public function usuarioUltimaOrden(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_ultima_orden_id'
        );
    }
}