<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZoeVozController extends Controller
{
    public function transcribir(Request $request)
    {
        $request->validate([
            'audio' => [
                'required',
                'file',
                'mimes:wav',
                'max:10240',
            ],
        ]);

        try {
            $archivo = $request->file('audio');

            $webhook = 'http://172.17.0.4:5678/webhook/zoe-voz';

            $response = Http::timeout(120)
                ->attach(
                    'audio',
                    fopen($archivo->getRealPath(), 'r'),
                    $archivo->getClientOriginalName()
                )
                ->post($webhook, [
                    'nombre_usuario' =>
                        $request->user()?->name ?? 'vecino',
                ]);

            Log::info('ZOE voz n8n', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo transcribir el audio.',
                ], 502);
            }

            $texto =
                $response->json('texto')
                ?? $response->json('text')
                ?? '';

            return response()->json([
                'success' => true,
                'texto' => trim((string) $texto),
            ]);

        } catch (\Throwable $e) {

            Log::error('Error en ZOE voz', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la voz.',
            ], 500);
        }
    }
}