<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmprendimientoOpinion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmprendimientoOpinionController extends Controller {
    
   public function index($emprendimientoId) {
    $userId = Auth::id();
    
    $opiniones = EmprendimientoOpinion::with('user')
        ->where('emprendimiento_id', $emprendimientoId)
        ->latest()
        ->get();

    $promedio = $opiniones->avg('calificacion') ?? 0;
    $total = $opiniones->count();

    // Buscamos si el usuario actual ya opinó en este negocio
    $userOpinion = $opiniones->firstWhere('user_id', $userId);

    return response()->json([
        'promedio' => round($promedio, 1),
        'total' => $total,
        'opiniones' => $opiniones,
        'user_opinion' => $userOpinion // 👈 Enviamos la opinión del usuario actual si existe
    ]);
}

    // Guardar o actualizar opinión del usuario (Unica reseña por usuario por negocio)
    public function store(Request $request, $emprendimientoId) {
        $request->validate([
            'calificacion' => 'required|integer|min:1|max:5',
            'comentario' => 'nullable|string|max:500',
        ]);

        $userId = Auth::id();

        // 🔄 updateOrCreate: Si ya existe una opinión para este usuario y emprendimiento, la actualiza. Si no, la crea.
        $opinion = EmprendimientoOpinion::updateOrCreate(
            [
                'emprendimiento_id' => $emprendimientoId,
                'user_id' => $userId,
            ],
            [
                'calificacion' => $request->calificacion,
                'comentario' => $request->comentario,
            ]
        );

        // Verificamos si se acaba de crear o si fue una actualización
        $fueCreada = $opinion->wasRecentlyCreated;

        return response()->json([
            'message' => $fueCreada ? 'Opinión registrada con éxito' : 'Opinión actualizada con éxito',
            'opinion' => $opinion->load('user')
        ], 200);
    }
}