<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    // Campos que se pueden rellenar de forma masiva (Seeder o formularios)
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}