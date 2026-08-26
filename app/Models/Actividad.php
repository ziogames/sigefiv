<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Actividad extends Model
{
    /**
     * Nombre real de la tabla.
     */
    protected $table = 'actividades';

    protected $fillable = [

        'user_id',

        'modulo',

        'accion',

        'ruta',

        'ip',

        'user_agent',

    ];

    /**
     * Usuario que realizó la actividad.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * Color visual según la acción.
     */
    public function getColorAttribute(): string
    {
        return match ($this->accion) {

            'Acceso' => 'primary',

            'Consulta' => 'info',

            'Crear' => 'success',

            'Editar' => 'warning',

            'Eliminar' => 'danger',

            default => 'secondary',

        };
    }

    /**
     * Icono visual según la acción.
     */
    public function getIconoAttribute(): string
    {
        return match ($this->accion) {

            'Acceso' => 'cil-door',

            'Consulta' => 'cil-magnifying-glass',

            'Crear' => 'cil-plus',

            'Editar' => 'cil-pencil',

            'Eliminar' => 'cil-trash',

            default => 'cil-notes',

        };
    }
}