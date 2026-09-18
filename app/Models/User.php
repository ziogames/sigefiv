<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'seudonimo',
    'email',
    'password',
    'google_id',
    'estado',
    'recibir_notificaciones',
    'telefono',
    'dni',
    'direccion',
    'foto',
    'ultimo_acceso',
    'ultima_ip',
    'bienvenida_vista',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',

            'password' => 'hashed',

            'ultimo_acceso' => 'datetime',

            'recibir_notificaciones' => 'boolean',

            'bienvenida_vista' => 'boolean',

        ];
    }

    /**
     * Avatar del usuario.
     *
     * Soporta:
     *
     * 1. URL externa de Google.
     * 2. Foto almacenada localmente.
     * 3. Avatar generado automáticamente.
     */
    public function getAvatarAttribute(): string
    {
        /*
        |--------------------------------------------------------------------------
        | Foto de Google
        |--------------------------------------------------------------------------
        */

        if (
            $this->foto &&
            filter_var($this->foto, FILTER_VALIDATE_URL)
        ) {

            return $this->foto;

        }


        /*
        |--------------------------------------------------------------------------
        | Foto local de SIGEFIV
        |--------------------------------------------------------------------------
        */

        if (
            $this->foto &&
            file_exists(
                public_path(
                    'storage/' . $this->foto
                )
            )
        ) {

            return asset(
                'storage/' . $this->foto
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Avatar generado
        |--------------------------------------------------------------------------
        */

        return 'https://ui-avatars.com/api/?name=' .
            urlencode($this->name) .
            '&background=0d6efd&color=ffffff&size=300';
    }

    /**
     * Conversaciones de chat a las que pertenece el usuario.
     */
    public function conversaciones(): BelongsToMany
    {
        return $this->belongsToMany(
            ChatConversation::class,
            'chat_conversation_user',
            'user_id',
            'conversation_id'
        )->withPivot([
            'rol',
            'ultimo_leido_at',
        ])->withTimestamps();
    }

    /**
     * Mensajes enviados por el usuario en el chat.
     */
    public function mensajesChat(): HasMany
    {
        return $this->hasMany(
            ChatMessage::class,
            'user_id'
        );
    }

    /**
     * Tokens FCM registrados en los dispositivos del usuario.
     */
    public function fcmTokens(): HasMany
    {
        return $this->hasMany(
            FcmToken::class,
            'user_id'
        );
    }
}