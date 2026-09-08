<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserNotBlocked
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Si el usuario está autenticado y su estado es bloqueado
        if ($user && $user->estado === 'bloqueado') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta ha sido suspendida permanentemente debido a infracciones en las normas de convivencia.'
            ], 403);
        }

        return $next($request);
    }
}