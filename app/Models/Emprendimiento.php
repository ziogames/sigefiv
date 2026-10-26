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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(EmprendimientoFoto::class);
    }
}