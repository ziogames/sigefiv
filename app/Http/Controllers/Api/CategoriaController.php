<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;

class CategoriaController extends Controller
{
    /**
     * Devuelve las categorías activas.
     */
    public function index(): JsonResponse
    {
        $categorias = Categoria::query()
            ->where('activo', true)
            ->orderBy('tipo')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
                'tipo',
                'icono',
                'color',
                'orden',
            ]);

        return response()->json([
            'success' => true,
            'categorias' => $categorias,
        ]);
    }
}