<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ActividadController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->input('buscar');
        $usuarioId = $request->input('usuario');
        $modulo = $request->input('modulo');
        $accion = $request->input('accion');
        $fechaDesde = $request->input('fecha_desde');
        $fechaHasta = $request->input('fecha_hasta');

        $consulta = Actividad::query()
            ->with('user:id,name,email,foto,google_id');

        // Búsqueda general
        $consulta->when($buscar, function ($query) use ($buscar) {
            $query->where(function ($q) use ($buscar) {
                $q->where('modulo', 'like', "%{$buscar}%")
                    ->orWhere('accion', 'like', "%{$buscar}%")
                    ->orWhere('ruta', 'like', "%{$buscar}%")
                    ->orWhereHas('user', function ($usuario) use ($buscar) {
                        $usuario->where('name', 'like', "%{$buscar}%")
                            ->orWhere('email', 'like', "%{$buscar}%");
                    });
            });
        });

        // Filtros
        $consulta->when($usuarioId, fn ($q) =>
            $q->where('user_id', $usuarioId)
        );

        $consulta->when($modulo, fn ($q) =>
            $q->where('modulo', $modulo)
        );

        $consulta->when($accion, fn ($q) =>
            $q->where('accion', $accion)
        );

        $consulta->when($fechaDesde, fn ($q) =>
            $q->whereDate('created_at', '>=', $fechaDesde)
        );

        $consulta->when($fechaHasta, fn ($q) =>
            $q->whereDate('created_at', '<=', $fechaHasta)
        );

        // Estadísticas
        $totalActividades = (clone $consulta)->count();

        $usuariosActivos = (clone $consulta)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $actividadesHoy = (clone $consulta)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $moduloMasUtilizado = (clone $consulta)
            ->select('modulo')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('modulo')
            ->orderByDesc('total')
            ->first();

        $modulosEstadisticas = (clone $consulta)
            ->select('modulo')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('modulo')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $usuariosEstadisticas = (clone $consulta)
            ->select('user_id')
            ->selectRaw('COUNT(*) as total')
            ->whereNotNull('user_id')
            ->with('user:id,name,email,foto,google_id')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Actividades paginadas
        $actividades = (clone $consulta)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'success' => true,

            'estadisticas' => [
                'total_actividades' => $totalActividades,
                'usuarios_activos' => $usuariosActivos,
                'actividades_hoy' => $actividadesHoy,
                'modulo_mas_utilizado' => $moduloMasUtilizado,
                'modulos' => $modulosEstadisticas,
                'usuarios' => $usuariosEstadisticas,
            ],

            'actividades' => $actividades,
        ]);
    }
}