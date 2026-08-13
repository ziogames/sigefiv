<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $fillable = [

        'nombre_sistema',

        'organizacion',

        'direccion',

        'telefono',

        'correo',

        'logo',

        'moneda',

        'simbolo_moneda',

        'decimales',

        'zona_horaria',
        'presidente',

        'tesorero',

        'secretario',

        'ruc',

        'web',

        'facebook',

        'instagram',
        'favicon',

        'imagen_login',

        'color_principal',
        'bitacora_activa',

        'sesion_unica',

        'bloqueo_intentos',

        'expirar_password',

        'intentos_login',

        'tiempo_sesion',

        'longitud_password',
        'anio_inicio',

'mes_inicio',

'saldo_apertura',

'contabilidad_iniciada',

    ];
}