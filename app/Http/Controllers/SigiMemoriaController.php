<?php

namespace App\Http\Controllers;

use App\Models\MemoriaSigi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SigiMemoriaController extends Controller
{
    /**
     * Devuelve las memorias del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $memorias = MemoriaSigi::query()
            ->where('usuario_id', $usuario->id)
            ->orderByDesc('importancia')
            ->orderByDesc('updated_at')
            ->get([
                'id',
                'tipo',
                'clave',
                'contenido',
                'importancia',
                'created_at',
                'updated_at',
            ]);

        return response()->json([
            'success' => true,
            'memorias' => $memorias,
        ]);
    }

    /**
     * Elimina una memoria perteneciente al usuario autenticado.
     */
    public function destroy(
        Request $request,
        MemoriaSigi $memoria
    ): JsonResponse {

        $usuario = $request->user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        /*
         * Seguridad:
         * el usuario solamente puede eliminar
         * sus propias memorias.
         */
        if ((int) $memoria->usuario_id !== (int) $usuario->id) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar esta memoria.',
            ], 403);
        }

        $memoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'La memoria fue eliminada correctamente.',
        ]);
    }
}