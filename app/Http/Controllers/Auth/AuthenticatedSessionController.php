<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActividadService;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Autenticar usuario
        |--------------------------------------------------------------------------
        */

        $request->authenticate();


        /*
        |--------------------------------------------------------------------------
        | Regenerar sesión
        |--------------------------------------------------------------------------
        */

        $request->session()->regenerate();


        /*
        |--------------------------------------------------------------------------
        | Obtener usuario autenticado
        |--------------------------------------------------------------------------
        */

        $usuario = Auth::user();


        /*
        |--------------------------------------------------------------------------
        | Registrar inicio de sesión y agregar al Chat Vecinal
        |--------------------------------------------------------------------------
        */

        if ($usuario) {

            ActividadService::loginPassword(
                $usuario
            );

            app(ChatService::class)->agregarUsuario(
                $usuario
            );


            /*
            |--------------------------------------------------------------------------
            | Primera entrada al sistema
            |--------------------------------------------------------------------------
            |
            | Si el usuario todavía no ha visto la pantalla de bienvenida,
            | la mostramos antes de llevarlo al dashboard.
            |
            */

            if (!$usuario->bienvenida_vista) {

                return redirect()->route(
                    'bienvenida'
                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Redirigir al dashboard
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

    /**
     * Destroy an authenticated session.
     */
    public function destroy(
        Request $request
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Guardar usuario antes de cerrar sesión
        |--------------------------------------------------------------------------
        */

        $usuario = Auth::user();


        /*
        |--------------------------------------------------------------------------
        | Registrar cierre de sesión
        |--------------------------------------------------------------------------
        */

        if ($usuario) {

            ActividadService::logout(
                $usuario
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Cerrar sesión
        |--------------------------------------------------------------------------
        */

        Auth::guard('web')->logout();


        /*
        |--------------------------------------------------------------------------
        | Invalidar sesión
        |--------------------------------------------------------------------------
        */

        $request->session()->invalidate();

        $request->session()->regenerateToken();


        return redirect('/');
    }
}
