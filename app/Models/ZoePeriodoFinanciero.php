<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoePeriodoFinanciero extends Model
{
    /**
     * Conexión exclusiva de lectura para ZOE.
     */
    protected $connection = 'zoe';

    /**
     * Vista PostgreSQL autorizada para ZOE.
     */
    protected $table = 'zoe_periodos_financieros';

    /**
     * Las vistas no manejan timestamps.
     */
    public $timestamps = false;

    /**
     * Campos disponibles en la vista.
     */
    protected $casts = [
        'id'             => 'integer',
        'anio'           => 'integer',
        'mes'            => 'integer',
        'saldo_inicial'  => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_egresos'  => 'decimal:2',
        'saldo_final'    => 'decimal:2',
    ];
}