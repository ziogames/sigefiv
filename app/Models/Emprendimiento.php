<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Emprendimiento extends Model
{
    use SoftDeletes;

    protected $table = 'emprendimientos';

    protected $fillable = [
        'user_id', 
        'name', 
        'slug', 
        'keywords',    // 👈 Campo para las búsquedas de Zoe
        'description',
        'phone', 
        'show_whatsapp', 
        'modality', 
        'attention_zone',
        'schedule', 
        'status'
    ];

    // Relación con el vecino propietario del negocio
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Relación con las fotos del emprendimiento
    public function photos(): HasMany
    {
        return $this->hasMany(EmprendimientoFoto::class);
    }

    // 👇 NUEVA: Relación con las opiniones y calificaciones de los vecinos
    public function opiniones(): HasMany
    {
        return $this->hasMany(EmprendimientoOpinion::class);
    }

    // 👇 NUEVA: Relación con los usuarios que marcaron este negocio como favorito
    public function favoritos(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'emprendimiento_favoritos')
                    ->withTimestamps();
    }
}