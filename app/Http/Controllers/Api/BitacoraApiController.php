<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Http\Request;

class BitacoraApiController extends Controller
{
    public function index(Request $request)
    {
        $query = Bitacora::with('user');

        // 1. Filtro de búsqueda general (insensible a mayúsculas/minúsculas)
        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q->where('descripcion', 'ilike', "%{$buscar}%")
                  ->orWhere('accion', 'ilike', "%{$buscar}%")
                  ->orWhere('modulo', 'ilike', "%{$buscar}%")
                  ->orWhereHas('user', function ($qu) use ($buscar) {
                      $qu->where('name', 'ilike', "%{$buscar}%")
                         ->orWhere('email', 'ilike', "%{$buscar}%");
                  });
            });
        }

        // 2. Filtros específicos
        if ($request->filled('usuario')) {
            $query->where('user_id', $request->usuario);
        }

        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        // 3. Mapeo de registros
        $bitacoras = $query->latest()->limit(50)->get()->map(function ($item) {
            return [
                'id'          => $item->id,
                'fecha'       => $item->created_at?->format('d/m/Y') ?? '',
                'hora'        => $item->created_at?->format('H:i:s') ?? '',
                'usuario'     => $item->user->name ?? 'Sistema',
                'email'       => $item->user->email ?? '-',
                'modulo'      => $item->modulo ?? 'General',
                'accion'      => $item->accion ?? 'Acción',
                'descripcion' => $item->descripcion,
                'ip'          => $item->ip ?? '127.0.0.1',
                'color'       => $item->color,
                'icono'       => $item->icono,
            ];
        });

        // 4. Métricas para los KPIs de la app
        $totalRegistros = Bitacora::count();
        $totalUsuarios  = User::count();
        $accionesHoy    = Bitacora::whereDate('created_at', today())->count();
        $totalModulos   = Bitacora::distinct('modulo')->count('modulo');

        return response()->json([
            'success'  => true,
            'metricas' => [
                'total_registros'  => $totalRegistros,
                'usuarios_activos' => $totalUsuarios,
                'modulos'          => $totalModulos,
                'acciones_hoy'     => $accionesHoy,
            ],
            'registros' => $bitacoras,
            'filtros_disponibles' => [
                'usuarios' => User::orderBy('name')->select('id', 'name')->get(),
                'modulos'  => Bitacora::select('modulo')->distinct()->pluck('modulo'),
            ]
        ]);
    }
}