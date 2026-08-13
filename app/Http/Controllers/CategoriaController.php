<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriaRequest;
use App\Http\Requests\UpdateCategoriaRequest;
use App\Models\Categoria;
use App\Services\CategoriaService;
use App\Services\BitacoraService;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Listado de categorías
     */
    public function index(Request $request)
    {
        $buscar = $request->buscar;
        $tipo = $request->tipo;

        $categorias = Categoria::query()

            ->when($buscar, function ($query) use ($buscar) {

                $query->where(
                    'nombre',
                    'like',
                    "%{$buscar}%"
                );

            })

            ->when($tipo, function ($query) use ($tipo) {

                $query->where(
                    'tipo',
                    $tipo
                );

            })

            ->orderBy('orden')
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view(
            'categorias.index',
            compact(
                'categorias',
                'buscar',
                'tipo'
            )
        );
    }

    /**
     * Formulario crear categoría
     */
    public function create()
    {
        return view('categorias.create');
    }

    /**
     * Guardar categoría
     */
    public function store(StoreCategoriaRequest $request)
    {
        $datos = $request->validated();

        $datos['activo'] = $request->boolean('activo');

        $datos['orden'] = $datos['orden'] ?? 0;

        $categoria = CategoriaService::guardar($datos);

        BitacoraService::registrar(
            'Categorías',
            'Crear',
            'Se creó la categoría: ' . $categoria->nombre
        );

        return redirect()
            ->route('categorias.index')
            ->with(
                'success',
                'Categoría creada correctamente.'
            );
    }

    /**
     * Mostrar categoría
     */
    public function show(Categoria $categoria)
    {
        return view(
            'categorias.show',
            compact('categoria')
        );
    }

    /**
     * Formulario editar categoría
     */
    public function edit(Categoria $categoria)
    {
        return view(
            'categorias.edit',
            compact('categoria')
        );
    }

    /**
     * Actualizar categoría
     */
    public function update(
        UpdateCategoriaRequest $request,
        Categoria $categoria
    ) {
        $datos = $request->validated();

        $datos['activo'] = $request->boolean('activo');

        $datos['orden'] = $datos['orden'] ?? 0;

        CategoriaService::actualizar(
            $categoria,
            $datos
        );

        BitacoraService::registrar(
            'Categorías',
            'Editar',
            'Se actualizó la categoría: ' . $categoria->nombre
        );

        return redirect()
            ->route('categorias.index')
            ->with(
                'success',
                'Categoría actualizada correctamente.'
            );
    }

    /**
     * Eliminar categoría
     */
    public function destroy(Categoria $categoria)
    {
        $nombre = $categoria->nombre;

        $categoria->delete();

        BitacoraService::registrar(
            'Categorías',
            'Eliminar',
            'Se eliminó la categoría: ' . $nombre
        );

        return redirect()
            ->route('categorias.index')
            ->with(
                'success',
                'Categoría eliminada correctamente.'
            );
    }
}