<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    protected $fillable = [

        'user_id',

        'modulo',

        'accion',

        'descripcion',

        'ip',

        'user_agent',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getColorAttribute()
{
    return match ($this->accion) {

        'Crear'    => 'success',

        'Editar'   => 'warning',

        'Eliminar' => 'danger',

        'Login'    => 'primary',

        'Logout'   => 'secondary',

        default    => 'info',

    };
}
public function getIconoAttribute()
{
    return match ($this->accion) {

        'Crear'    => 'cil-plus',

        'Editar'   => 'cil-pencil',

        'Eliminar' => 'cil-trash',

        'Login'    => 'cil-user',

        'Logout'   => 'cil-account-logout',

        default    => 'cil-notes',

    };
}
}