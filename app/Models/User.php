<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'telefono',
    'dni',
    'direccion',
    'foto',
    'ultimo_acceso',
    'ultima_ip',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',

            'password' => 'hashed',

            'ultimo_acceso' => 'datetime',

        ];
    }

    /**
     * Avatar del usuario
     */
    public function getAvatarAttribute(): string
    {
        if ($this->foto && file_exists(public_path('storage/' . $this->foto))) {

            return asset('storage/' . $this->foto);

        }

        return 'https://ui-avatars.com/api/?name=' .
            urlencode($this->name) .
            '&background=0d6efd&color=ffffff&size=300';
    }
}