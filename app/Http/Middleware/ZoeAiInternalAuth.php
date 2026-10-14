<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ZoeAiInternalAuth
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $claveEsperada = config('services.zoe_ai.key');

        $claveRecibida = $request->header('X-ZOE-AI-KEY');

        if (
            empty($claveEsperada) ||
            empty($claveRecibida) ||
            !hash_equals($claveEsperada, $claveRecibida)
        ) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No autorizado.',
            ], 401);
        }

        return $next($request);
    }
}