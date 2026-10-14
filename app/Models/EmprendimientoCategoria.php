<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmprendimientoCategoria extends Model
{
    protected $table = 'emprendimiento_categorias';
    
    protected $fillable = [
        'name', 
        'slug'
    ];

    public function emprendimientos(): HasMany
    {
        return $this->hasMany(Emprendimiento::class, 'categoria_id');
    }
}