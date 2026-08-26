<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarEstadoUsuario
{
    /**
     * Verificar el estado de la cuenta del usuario autenticado.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response|RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Usuario no autenticado
        |--------------------------------------------------------------------------
        |
        | Si todavía no existe una sesión autenticada,
        | dejamos continuar la petición.
        |
        */

        if (! Auth::check()) {

            return $next($request);

        }


        $usuario = Auth::user();


        /*
        |--------------------------------------------------------------------------
        | Rutas que siempre deben estar disponibles
        |--------------------------------------------------------------------------
        |
        | Un usuario pendiente necesita poder ver su página de espera
        | y también debe poder cerrar sesión.
        |
        */

        if (
            $request->routeIs('cuenta.pendiente') ||
            $request->routeIs('logout')
        ) {

            return $next($request);

        }


        /*
        |--------------------------------------------------------------------------
        | Cuenta bloqueada
        |--------------------------------------------------------------------------
        */

        if ($usuario->estado === 'bloqueado') {

            Auth::logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with(
                    'error',
                    'Tu cuenta de SIGEFIV está bloqueada. Comunícate con el administrador.'
                );

        }


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
        | Cuenta activa
        |--------------------------------------------------------------------------
        |
        | Los usuarios activos pueden continuar normalmente.
        |
        */

        return $next($request);
    }
}