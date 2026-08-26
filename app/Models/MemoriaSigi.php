<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemoriaSigi extends Model
{
    use HasFactory;

    protected $table = 'memorias_sigi';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'clave',
        'contenido',
        'importancia',
    ];

    protected $casts = [
        'importancia' => 'integer',
        'usuario_id' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }
}