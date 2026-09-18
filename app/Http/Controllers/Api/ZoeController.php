<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoeController extends Controller
{
    public function consultar(Request $request)
    {
        $request->validate([
            'mensaje' => 'required|string|max:1000',
        ]);

        try {
            $response = Http::timeout(120)
                ->post(config('services.n8n.zoe_webhook'), [
                    'mensaje' => $request->mensaje,
                ]);

            Log::info('ZOE respuesta n8n', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener respuesta de ZOE.',
                    'error' => $response->body(),
                ], 502);
            }

            $respuesta =
                $response->json('respuesta')
                ?? $response->json('mensaje')
                ?? $response->json('text');

            return response()->json([
                'success' => true,
                'respuesta' => $respuesta,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al comunicarse con ZOE.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}