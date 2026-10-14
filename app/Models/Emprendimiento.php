<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Emprendimiento extends Model
{
    use SoftDeletes;

    protected $table = 'emprendimientos';

    protected $fillable = [
        'user_id', 
        'categoria_id', 
        'name', 
        'slug', 
        'description',
        'phone', 
        'show_whatsapp', 
        'modality', 
        'attention_zone',
        'schedule', 
        'status'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relación con la categoría exclusiva de emprendimientos
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(EmprendimientoCategoria::class, 'categoria_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EmprendimientoFoto::class);
    }
}