<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarUltimoAcceso
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {

            auth()->user()->update([

                'ultimo_acceso' => now(),

                'ultima_ip' => $request->ip(),

            ]);

        }

        return $next($request);
    }
}