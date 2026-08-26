<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\User;

class ActividadService
{
    /**
     * Registrar una actividad del usuario.
     */
    public static function registrar(
        User $usuario,
        string $modulo,
        string $accion,
        string $ruta
    ): Actividad {
        return Actividad::create([

            'user_id' => $usuario->id,

            'modulo' => $modulo,

            'accion' => $accion,

            'ruta' => $ruta,

            'ip' => request()->ip(),

            'user_agent' => request()->userAgent(),

        ]);
    }

    /**
     * Registrar inicio de sesión mediante Google.
     */
    public static function loginGoogle(
        User $usuario
    ): Actividad {
        return self::registrar(

            usuario: $usuario,

            modulo: 'Autenticación',

            accion: 'Login',

            ruta: 'auth.google'

        );
    }

    /**
     * Registrar inicio de sesión mediante contraseña.
     */
    public static function loginPassword(
        User $usuario
    ): Actividad {
        return self::registrar(

            usuario: $usuario,

            modulo: 'Autenticación',

            accion: 'Login',

            ruta: 'login'

        );
    }

    /**
     * Registrar cierre de sesión.
     */
    public static function logout(
        User $usuario
    ): Actividad {
        return self::registrar(

            usuario: $usuario,

            modulo: 'Autenticación',

            accion: 'Logout',

            ruta: 'logout'

        );
    }
}