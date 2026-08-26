<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ActividadController extends Controller
{
    /**
     * Mostrar la actividad y estadísticas de uso.
     */
    public function index(Request $request): View
    {
        /*
        |--------------------------------------------------------------------------
        | Filtros
        |--------------------------------------------------------------------------
        */

        $buscar = $request->input('buscar');

        $usuarioId = $request->input('usuario');

        $modulo = $request->input('modulo');

        $accion = $request->input('accion');

        $fechaDesde = $request->input('fecha_desde');

        $fechaHasta = $request->input('fecha_hasta');


        /*
        |--------------------------------------------------------------------------
        | Consulta base
        |--------------------------------------------------------------------------
        */

        $consulta = Actividad::query();


        /*
        |--------------------------------------------------------------------------
        | Búsqueda general
        |--------------------------------------------------------------------------
        */

        $consulta->when(
            $buscar,
            function ($query) use ($buscar) {

                $query->where(function ($q) use ($buscar) {

                    $q->where(
                        'modulo',
                        'like',
                        "%{$buscar}%"
                    )

                    ->orWhere(
                        'accion',
                        'like',
                        "%{$buscar}%"
                    )

                    ->orWhere(
                        'ruta',
                        'like',
                        "%{$buscar}%"
                    )

                    ->orWhereHas(
                        'user',
                        function ($usuario) use ($buscar) {

                            $usuario
                                ->where(
                                    'name',
                                    'like',
                                    "%{$buscar}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$buscar}%"
                                );

                        }
                    );

                });

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Filtro usuario
        |--------------------------------------------------------------------------
        */

        $consulta->when(
            $usuarioId,
            function ($query) use ($usuarioId) {

                $query->where(
                    'user_id',
                    $usuarioId
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Filtro módulo
        |--------------------------------------------------------------------------
        */

        $consulta->when(
            $modulo,
            function ($query) use ($modulo) {

                $query->where(
                    'modulo',
                    $modulo
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Filtro acción
        |--------------------------------------------------------------------------
        */

        $consulta->when(
            $accion,
            function ($query) use ($accion) {

                $query->where(
                    'accion',
                    $accion
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Filtro fecha desde
        |--------------------------------------------------------------------------
        */

        $consulta->when(
            $fechaDesde,
            function ($query) use ($fechaDesde) {

                $query->whereDate(
                    'created_at',
                    '>=',
                    $fechaDesde
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Filtro fecha hasta
        |--------------------------------------------------------------------------
        */

        $consulta->when(
            $fechaHasta,
            function ($query) use ($fechaHasta) {

                $query->whereDate(
                    'created_at',
                    '<=',
                    $fechaHasta
                );

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Actividades paginadas
        |--------------------------------------------------------------------------
        */

        $actividades = (clone $consulta)

            ->with('user')

            ->latest()

            ->paginate(15)

            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Total de actividades
        |--------------------------------------------------------------------------
        */

        $totalActividades = (clone $consulta)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Usuarios activos
        |--------------------------------------------------------------------------
        */

        $usuariosActivos = (clone $consulta)

            ->distinct('user_id')

            ->count('user_id');


        /*
        |--------------------------------------------------------------------------
        | Actividades de hoy
        |--------------------------------------------------------------------------
        */

        $actividadesHoy = (clone $consulta)

            ->whereDate(
                'created_at',
                Carbon::today()
            )

            ->count();


        /*
        |--------------------------------------------------------------------------
        | Módulo más utilizado
        |--------------------------------------------------------------------------
        */

        $moduloMasUtilizado = (clone $consulta)

            ->select('modulo')

            ->selectRaw('COUNT(*) as total')

            ->groupBy('modulo')

            ->orderByDesc('total')

            ->first();


        /*
        |--------------------------------------------------------------------------
        | Módulos más utilizados
        |--------------------------------------------------------------------------
        */

        $modulosEstadisticas = (clone $consulta)

            ->select('modulo')

            ->selectRaw('COUNT(*) as total')

            ->groupBy('modulo')

            ->orderByDesc('total')

            ->limit(5)

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Usuarios más activos
        |--------------------------------------------------------------------------
        */

        $usuariosEstadisticas = (clone $consulta)

            ->select('user_id')

            ->selectRaw('COUNT(*) as total')

            ->with(
                'user:id,name,email,foto,google_id'
            )

            ->groupBy('user_id')

            ->orderByDesc('total')

            ->limit(5)

            ->get();


        /*
        |--------------------------------------------------------------------------
        | Usuarios disponibles para filtros
        |--------------------------------------------------------------------------
        */

        $usuarios = User::orderBy('name')->get();


        /*
        |--------------------------------------------------------------------------
        | Módulos disponibles
        |--------------------------------------------------------------------------
        */

        $modulos = Actividad::query()

            ->select('modulo')

            ->distinct()

            ->orderBy('modulo')

            ->pluck('modulo');


        /*
        |--------------------------------------------------------------------------
        | Acciones disponibles
        |--------------------------------------------------------------------------
        |
        | Se obtienen directamente de la base de datos.
        |
        | Esto permite que nuevas acciones aparezcan
        | automáticamente en el filtro.
        |
        */

        $acciones = Actividad::query()

            ->select('accion')

            ->distinct()

            ->orderBy('accion')

            ->pluck('accion');


        return view(
            'actividad.index',
            compact(

                'actividades',

                'buscar',

                'usuarioId',

                'modulo',

                'accion',

                'fechaDesde',

                'fechaHasta',

                'usuarios',

                'modulos',

                'acciones',

                'totalActividades',

                'usuariosActivos',

                'actividadesHoy',

                'moduloMasUtilizado',

                'modulosEstadisticas',

                'usuariosEstadisticas'

            )
        );
    }
}