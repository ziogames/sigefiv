<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmprendimientoFoto extends Model
{
    protected $table = 'emprendimiento_fotos';
    protected $fillable = ['emprendimiento_id', 'path', 'is_primary'];

    public function emprendimiento(): BelongsTo
    {
        return $this->belongsTo(Emprendimiento::class);
    }
}