<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use App\Models\User;
class BitacoraController extends Controller

{
    public function index(Request $request)
{
    $query = Bitacora::with('user');

    if ($request->filled('buscar')) {

        $query->where(function ($q) use ($request) {

            $q->where('descripcion', 'like', '%' . $request->buscar . '%')
              ->orWhere('accion', 'like', '%' . $request->buscar . '%')
              ->orWhere('modulo', 'like', '%' . $request->buscar . '%');

        });

    }

    if ($request->filled('usuario')) {

        $query->where('user_id', $request->usuario);

    }

    if ($request->filled('modulo')) {

        $query->where('modulo', $request->modulo);

    }

    if ($request->filled('desde')) {

        $query->whereDate(
            'created_at',
            '>=',
            $request->desde
        );

    }

    if ($request->filled('hasta')) {

        $query->whereDate(
            'created_at',
            '<=',
            $request->hasta
        );

    }

    $bitacoras = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

        $totalRegistros = Bitacora::count();

$totalUsuarios = User::count();

$accionesHoy = Bitacora::whereDate(
    'created_at',
    today()
)->count();

$totalModulos = Bitacora::distinct()
    ->count('modulo');

    return view(
    'bitacora.index',
    [

        'bitacoras'      => $bitacoras,

        'usuarios'       => User::orderBy('name')->get(),

        'modulos'        => Bitacora::select('modulo')
                                ->distinct()
                                ->pluck('modulo'),

        'totalRegistros' => $totalRegistros,

        'totalUsuarios'  => $totalUsuarios,

        'accionesHoy'    => $accionesHoy,

        'totalModulos'   => $totalModulos,

    ]
);
}
}