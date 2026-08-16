<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clima extends Model
{
    protected $table = 'climas';

    protected $fillable = [
        'ubicacion',
        'pais',
        'latitud',
        'longitud',
        'temperatura',
        'sensacion',
        'humedad',
        'viento',
        'codigo',
        'descripcion',
        'proveedor',
        'consultado_en',
        'expira_en',
    ];

    protected $casts = [
        'latitud' => 'decimal:7',
        'longitud' => 'decimal:7',
        'temperatura' => 'decimal:2',
        'sensacion' => 'decimal:2',
        'humedad' => 'integer',
        'viento' => 'decimal:2',
        'codigo' => 'integer',
        'consultado_en' => 'datetime',
        'expira_en' => 'datetime',
    ];
}