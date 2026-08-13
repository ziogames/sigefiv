<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimientoRequest;
use App\Http\Requests\UpdateMovimientoRequest;
use App\Models\Categoria;
use App\Models\Movimiento;
use App\Services\MovimientoService;
use Illuminate\Http\Request;
use App\Services\PeriodoService;

class MovimientoController extends Controller
{
    /**
     * Listado
     */
    public function index(Request $request)
    {
        $buscar = $request->buscar;

        $tipo = $request->tipo;

        $movimientos = Movimiento::with([
                'categoria',
                'periodo',
                'usuario'
            ])
            ->when($buscar, function ($query) use ($buscar) {

                $query->where(function ($q) use ($buscar) {

                    $q->where('numero', 'like', "%{$buscar}%")
                        ->orWhere('concepto', 'like', "%{$buscar}%")
                        ->orWhere('persona', 'like', "%{$buscar}%");

                });

            })
            ->when($tipo, function ($query) use ($tipo) {

                $query->where('tipo', $tipo);

            })
            ->orderByDesc('fecha')
            ->paginate(15)
            ->withQueryString();

        return view(
            'movimientos.index',
            compact(
                'movimientos',
                'buscar',
                'tipo'
            )
        );
    }

    /**
     * Nuevo movimiento
     */
    public function create()
    {
        $categorias = Categoria::orderBy('nombre')->get();

        $numero = Movimiento::siguienteNumero();

        return view(
            'movimientos.create',
            compact(
                'categorias',
                'numero'
            )
        );
    }

    /**
     * Guardar
     */
   public function store(StoreMovimientoRequest $request)
{
    try {

        $datos = $request->validated();

        $datos['user_id'] = auth()->id();

        MovimientoService::guardar($datos);

        return redirect()
            ->route('movimientos.index')
            ->with(
                'success',
                'Movimiento registrado correctamente.'
            );

    } catch (\Exception $e) {

        return redirect()
            ->route('movimientos.create')
            ->withInput()
            ->with(
                'error',
                'No se pudo registrar el movimiento: ' . $e->getMessage()
            );
    }
}

    /**
     * Mostrar
     */
    public function show(Movimiento $movimiento)
    {
        return view(
            'movimientos.show',
            compact('movimiento')
        );
    }

    /**
     * Editar
     */
    public function edit(Movimiento $movimiento)
    {
        $categorias = Categoria::orderBy('nombre')->get();

        return view(
            'movimientos.edit',
            compact(
                'movimiento',
                'categorias'
            )
        );
    }

    /**
     * Actualizar
     */
  public function update(
    UpdateMovimientoRequest $request,
    Movimiento $movimiento
) {
    try {

        $datos = $request->validated();

        MovimientoService::actualizar(
            $movimiento,
            $datos
        );

        return redirect()
            ->route('movimientos.index')
            ->with(
                'success',
                'Movimiento actualizado correctamente.'
            );

    } catch (\Exception $e) {

        return redirect()
            ->route('movimientos.index')
            ->with(
                'error',
                $e->getMessage()
            );
    }
}

    /**
     * Eliminar
     */
public function destroy(Movimiento $movimiento)
{
    try {

        MovimientoService::eliminar($movimiento);

        return redirect()
            ->route('movimientos.index')
            ->with(
                'success',
                'Movimiento eliminado correctamente.'
            );

    } catch (\Exception $e) {

        return redirect()
            ->route('movimientos.index')
            ->with(
                'error',
                $e->getMessage()
            );
    }
}
}