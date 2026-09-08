<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActividadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Redirigir al usuario hacia Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Recibir la respuesta de Google.
     */
    public function callback(): RedirectResponse
    {
        try {

            $googleUser = Socialite::driver('google')
                ->user();

        } catch (Throwable $e) {

            report($e);

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'No fue posible iniciar sesión con Google. Inténtalo nuevamente.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Datos proporcionados por Google
        |--------------------------------------------------------------------------
        */

        $googleId = $googleUser->getId();

        $email = $googleUser->getEmail();

        $nombre = $googleUser->getName()
            ?: $googleUser->getNickname()
            ?: 'Usuario Google';

        $foto = $googleUser->getAvatar();


        /*
        |--------------------------------------------------------------------------
        | Buscar usuario por correo
        |--------------------------------------------------------------------------
        */

        $usuario = User::where(
            'email',
            $email
        )->first();


        /*
        |--------------------------------------------------------------------------
        | Usuario nuevo
        |--------------------------------------------------------------------------
        */

        if (!$usuario) {

            $usuario = User::create([

                'name' => $nombre,

                'email' => $email,

                'google_id' => $googleId,

                'foto' => $foto,

                /*
                | No utilizaremos esta contraseña para
                | autenticar al usuario con Google.
                */
                'password' => Hash::make(
                    bin2hex(random_bytes(32))
                ),

                'estado' => 'pendiente',

                'recibir_notificaciones' => false,

            ]);

            /*
            |--------------------------------------------------------------------------
            | Asignar rol Consulta
            |--------------------------------------------------------------------------
            */

            $usuario->assignRole('Consulta');

        } else {

            /*
            |--------------------------------------------------------------------------
            | Usuario existente
            |--------------------------------------------------------------------------
            |
            | Vinculamos la cuenta de Google y actualizamos
            | la información proporcionada por Google.
            |
            | No modificamos la contraseña ni el rol.
            |
            */

            $usuario->update([

                'google_id' => $googleId,

                'foto' => $foto,

            ]);

        }


        /*
        |--------------------------------------------------------------------------
        | Cuenta bloqueada
        |--------------------------------------------------------------------------
        */

        if ($usuario->estado === 'bloqueado') {

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Tu cuenta de SIGEFIV está bloqueada. Comunícate con el administrador.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Iniciar sesión en SIGEFIV
        |--------------------------------------------------------------------------
        */

        Auth::login($usuario);

        request()
            ->session()
            ->regenerate();


        /*
        |--------------------------------------------------------------------------
        | Registrar inicio de sesión mediante Google
        |--------------------------------------------------------------------------
        */

        ActividadService::loginGoogle(
            $usuario
        );


        /*
        |--------------------------------------------------------------------------
        | Cuenta pendiente
        |--------------------------------------------------------------------------
        */

        if ($usuario->estado === 'pendiente') {

            return redirect()
                ->route('cuenta.pendiente');
        }


        /*
        |--------------------------------------------------------------------------
        | Primera entrada al sistema
        |--------------------------------------------------------------------------
        */

        if (!$usuario->bienvenida_vista) {

            return redirect()
                ->route('bienvenida');
        }


        /*
        |--------------------------------------------------------------------------
        | Cuenta activa
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->intended(
                route(
                    'dashboard',
                    absolute: false
                )
            );
    }
}
