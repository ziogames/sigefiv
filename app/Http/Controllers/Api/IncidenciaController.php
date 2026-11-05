<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ZoeIncidencia; // O el nombre de tu modelo Eloquent para la tabla zoe_incidencias

class IncidenciaController extends Controller
{
    // 1. Listar incidencias (Si pasas ?user_id=X trae las del vecino, si no, trae todas para la directiva)
    public function index(Request $request)
    {
        $query = ZoeIncidencia::orderBy('id', 'desc');

        if ($request->has('user_id')) {
            $query->where('user_id',$request->user_id);
        }

        $incidencias =$query->get();

        return response()->json([
            'success' => true,
            'incidencias' => $incidencias
        ]);
    }

    // 2. Crear una nueva incidencia desde el formulario del vecino (¡NUEVO!)
    public function store(Request $request)
{
    $validated = $request->validate([
        'titulo' => 'required|string|max:255',
        'categoria' => 'required|string|max:255',
        'mensaje' => 'required|string',
        'imagen' => 'nullable|image|max:5120' // 📸 Validamos el archivo adjunto (máximo 5MB)
    ]);

    $imagenPath = null;
    if ($request->hasFile('imagen')) {
        // Almacena el archivo en storage/app/public/incidencias
        // Recuerda haber corrido: php artisan storage:link
        $imagenPath = $request->file('imagen')->store('incidencias', 'public');
    }

    $incidencia = ZoeIncidencia::create([
        'user_id' => $request->user()->id, // 🛡️ Obtenido de forma segura por el token de Sanctum
        'titulo' => $validated['titulo'],
        'categoria' => $validated['categoria'],
        'mensaje' => $validated['mensaje'],
        'imagen_path' => $imagenPath, // 📁 Guardamos la ruta física real generada
        'estado' => 'pendiente' // Por defecto nace pendiente para que la directiva lo revise
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Incidencia creada con éxito',
        'incidencia' => $incidencia
    ], 201);
}

    // 3. Listar únicamente los reportes del vecino autenticado (Rol Consulta / Vecino)
    public function misReportes(Request $request)
    {
        // Tomamos el ID directamente del token de Sanctum del usuario logueado
        $userId =$request->user()->id;

        $incidencias = ZoeIncidencia::where('user_id',$userId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'incidencias' => $incidencias
        ]);
    }

    // 4. Actualizar el estado de una incidencia (Usado por la directiva)
    public function actualizarEstado(Request $request,$id)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,en_proceso,atendido'
        ]);

        $incidencia = ZoeIncidencia::find($id);

        if (!$incidencia) {
            return response()->json([
                'success' => false,
                'message' => 'Incidencia no encontrada'
            ], 404);
        }

        $incidencia->estado =$request->estado;
        $incidencia->save();

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'incidencia' => $incidencia
        ]);
    }
}