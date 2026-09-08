<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoeMovimiento extends Model
{
    /**
     * Conexión exclusiva de lectura para ZOE.
     */
    protected $connection = 'zoe';

    /**
     * Vista PostgreSQL autorizada para ZOE.
     */
    protected $table = 'zoe_movimientos_detallados';

    /**
     * Las vistas no manejan timestamps.
     */
    public $timestamps = false;

    /**
     * Campos disponibles en la vista.
     */
    protected $casts = [
        'id'            => 'integer',
        'numero'        => 'string',
        'fecha'         => 'date',
        'categoria_id'  => 'integer',
        'monto'         => 'decimal:2',
        'periodo_id'    => 'integer',
        'anio'          => 'integer',
        'mes'           => 'integer',
    ];
}