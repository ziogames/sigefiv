<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovimientoRequest;
use App\Http\Requests\UpdateMovimientoRequest;
use App\Models\Categoria;
use App\Models\Movimiento;
use App\Services\MovimientoService;
use Illuminate\Http\Request;
use App\Services\PeriodoService;
use App\Models\Periodo;

class MovimientoController extends Controller
{
    
    public function index(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | Filtros
    |--------------------------------------------------------------------------
    */

    $buscar = $request->buscar;
    $anio = $request->anio;
    $tipo = $request->tipo;
    $periodo_id = $request->periodo_id;
    $categoria_id = $request->categoria_id;
    $desde = $request->desde;
    $hasta = $request->hasta;
    


    /*
    |--------------------------------------------------------------------------
    | Período activo
    |--------------------------------------------------------------------------
    */

    $periodo = Periodo::where('estado', 'Abierto')
        ->orderByDesc('anio')
        ->orderByDesc('mes')
        ->first();


    /*
    |--------------------------------------------------------------------------
    | Períodos disponibles
    |--------------------------------------------------------------------------
    */

    $periodos = Periodo::orderByDesc('anio')
        ->orderByDesc('mes')
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Categorías disponibles
    |--------------------------------------------------------------------------
    */

    $categorias = Categoria::orderBy('nombre')
        ->get();

        /*
|--------------------------------------------------------------------------
| Categorías utilizadas por tipo
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Categorías por tipo
|--------------------------------------------------------------------------
*/

$categoriasIngreso = Categoria::where('tipo', 'Ingreso')
    ->orderBy('nombre')
    ->get();

$categoriasEgreso = Categoria::where('tipo', 'Egreso')
    ->orderBy('nombre')
    ->get();


    /*
    |--------------------------------------------------------------------------
    | Consulta base de movimientos
    |--------------------------------------------------------------------------
    */

    $movimientosQuery = Movimiento::with([
        'categoria',
        'periodo',
        'usuario'
    ])


        /*
        | Buscar
        */

        
        ->when($buscar, function ($query) use ($buscar) {

            $query->where(function ($q) use ($buscar) {

                $q->where(
                    'numero',
                    'like',
                    "%{$buscar}%"
                )

                ->orWhere(
                    'concepto',
                    'like',
                    "%{$buscar}%"
                )

                ->orWhere(
                    'persona',
                    'like',
                    "%{$buscar}%"
                )

                ->orWhere(
                    'referencia',
                    'like',
                    "%{$buscar}%"
                );

            });

        })


        /*
        | Tipo
        */

->when($anio, function ($query) use ($anio) {

    $query->whereHas('periodo', function ($q) use ($anio) {

        $q->where('anio', $anio);

    });

})

        ->when($tipo, function ($query) use ($tipo) {

            $query->where(
                'tipo',
                $tipo
            );

        })


        /*
        | Período
        */

        ->when($periodo_id, function ($query) use ($periodo_id) {

            $query->where(
                'periodo_id',
                $periodo_id
            );

        })


        /*
        | Categoría
        */

        ->when($categoria_id, function ($query) use ($categoria_id) {

            $query->where(
                'categoria_id',
                $categoria_id
            );

        })


        /*
        | Fecha desde
        */

        ->when($desde, function ($query) use ($desde) {

            $query->whereDate(
                'fecha',
                '>=',
                $desde
            );

        })


        /*
        | Fecha hasta
        */

        ->when($hasta, function ($query) use ($hasta) {

            $query->whereDate(
                'fecha',
                '<=',
                $hasta
            );

        });


    /*
    |--------------------------------------------------------------------------
    | RESUMEN
    |--------------------------------------------------------------------------
    |
    | Aquí calculamos los totales ANTES de paginar.
    |
    */


    $totalMovimientos = (clone $movimientosQuery)
        ->count();


    $totalIngresos = (clone $movimientosQuery)
        ->where('tipo', 'Ingreso')
        ->sum('monto');


    $totalEgresos = (clone $movimientosQuery)
        ->where('tipo', 'Egreso')
        ->sum('monto');


    $cantidadIngresos = (clone $movimientosQuery)
        ->where('tipo', 'Ingreso')
        ->count();


    $cantidadEgresos = (clone $movimientosQuery)
        ->where('tipo', 'Egreso')
        ->count();


    /*
    |--------------------------------------------------------------------------
    | Período utilizado para el saldo inicial
    |--------------------------------------------------------------------------
    */

    $periodoResumen = $periodo_id
        ? Periodo::find($periodo_id)
        : $periodo;


    $saldoInicial = $periodoResumen?->saldo_inicial ?? 0;


    /*
    |--------------------------------------------------------------------------
    | Cálculo del disponible y saldo en caja
    |--------------------------------------------------------------------------
    */

    $disponible = $saldoInicial + $totalIngresos;

    $saldoCaja = $disponible - $totalEgresos;
    /*
|--------------------------------------------------------------------------
| DATOS MENSUALES PARA GRÁFICOS
|--------------------------------------------------------------------------
*/

$movimientosGrafico = (clone $movimientosQuery)
    ->with('categoria')
    ->select([
        'periodo_id',
        'categoria_id',
        'tipo',
        'monto',
    ])
    ->get();


    /*
|--------------------------------------------------------------------------
| Categorías para gráfico
|--------------------------------------------------------------------------
*/

$movimientosCategorias = $movimientosGrafico;

if ($tipo) {

    $movimientosCategorias =
        $movimientosCategorias->where(
            'tipo',
            $tipo
        );

}

$categoriasGrafico = $movimientosCategorias
    ->filter(function ($movimiento) {

        return $movimiento->categoria !== null;

    })
    ->groupBy(function ($movimiento) {

        return $movimiento->categoria->nombre;

    })
    ->map(function ($items) {

        return (float) $items->sum('monto');

    });


/*
|--------------------------------------------------------------------------
| Orden de períodos para gráficos
|--------------------------------------------------------------------------
*/

$periodosGrafico = $periodo_id
    ? $periodos->where('id', $periodo_id)
    : (
        $anio
            ? $periodos->where('anio', $anio)
            : $periodos
    );


/*
|--------------------------------------------------------------------------
| Preparar datos mensuales
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Preparar datos mensuales para gráficos
|--------------------------------------------------------------------------
*/

$graficoMensualLabels = [];

$graficoMensualIngresos = [];

$graficoMensualEgresos = [];
$graficoMensualCantidad = [];

$graficoMensualIngresosCantidad = [];

$graficoMensualEgresosCantidad = [];


/*
|--------------------------------------------------------------------------
| Año seleccionado + todos los períodos
|--------------------------------------------------------------------------
*/

if ($anio && !$periodo_id) {

    $meses = [
        1  => 'Enero',
        2  => 'Febrero',
        3  => 'Marzo',
        4  => 'Abril',
        5  => 'Mayo',
        6  => 'Junio',
        7  => 'Julio',
        8  => 'Agosto',
        9  => 'Septiembre',
        10 => 'Octubre',
        11 => 'Noviembre',
        12 => 'Diciembre',
    ];


    foreach ($meses as $numeroMes => $nombreMes) {

        $periodoGrafico =
            $periodosGrafico->firstWhere(
                'mes',
                $numeroMes
            );


        $graficoMensualLabels[] =
            $nombreMes . ' ' . $anio;


        if ($periodoGrafico) {

            $ingresosMes =
                $movimientosGrafico
                    ->where(
                        'periodo_id',
                        $periodoGrafico?->id
                    )
                    ->where(
                        'tipo',
                        'Ingreso'
                    )
                    ->sum('monto');


            $egresosMes =
                $movimientosGrafico
                    ->where(
                        'periodo_id',
                       $periodoGrafico?->id
                    )
                    ->where(
                        'tipo',
                        'Egreso'
                    )
                    ->sum('monto');

        } else {

            $ingresosMes = 0;

            $egresosMes = 0;

        }


    $graficoMensualIngresos[] =
    (float) $ingresosMes;

$graficoMensualEgresos[] =
    (float) $egresosMes;

    $cantidadIngresosMes =
    $movimientosGrafico
        ->where(
            'periodo_id',
            $periodoGrafico?->id
        )
        ->where(
            'tipo',
            'Ingreso'
        )
        ->count();

$cantidadEgresosMes =
    $movimientosGrafico
        ->where(
            'periodo_id',
            $periodoGrafico?->id
        )
        ->where(
            'tipo',
            'Egreso'
        )
        ->count();

$graficoMensualIngresosCantidad[] =
    $cantidadIngresosMes;

$graficoMensualEgresosCantidad[] =
    $cantidadEgresosMes;

$graficoMensualCantidad[] =
    $cantidadIngresosMes + $cantidadEgresosMes;



            

    }

}


/*
|--------------------------------------------------------------------------
| Período específico
|--------------------------------------------------------------------------
*/

else {

    foreach (
        $periodosGrafico->sortBy([
            ['anio', 'asc'],
            ['mes', 'asc'],
        ])
        as $periodoGrafico
    ) {

        $graficoMensualLabels[] =
            $periodoGrafico->nombre .
            ' ' .
            $periodoGrafico->anio;


        $ingresosMes =
            $movimientosGrafico
                ->where(
                    'periodo_id',
                    $periodoGrafico?->id
                )
                ->where(
                    'tipo',
                    'Ingreso'
                )
                ->sum('monto');


        $egresosMes =
            $movimientosGrafico
                ->where(
                    'periodo_id',
                    $periodoGrafico?->id
                )
                ->where(
                    'tipo',
                    'Egreso'
                )
                ->sum('monto');


        $graficoMensualIngresos[] =
            (float) $ingresosMes;


        $graficoMensualEgresos[] =
            (float) $egresosMes;

    }

}


    /*
    |--------------------------------------------------------------------------
    | Paginación
    |--------------------------------------------------------------------------
    */

    $movimientos = $movimientosQuery
        ->orderByDesc('fecha')
        ->paginate(15)
        ->withQueryString();


    /*
    |--------------------------------------------------------------------------
    | Vista
    |--------------------------------------------------------------------------
    */

    return view(
        'movimientos.index',

        compact(
            'movimientos',
            'buscar',
            'anio',
            'tipo',
            'periodo',
            'periodos',
            'categorias',
            'categoriasIngreso',
            'categoriasEgreso',
            'periodo_id',
            'categoria_id',
            'desde',
            'hasta',

            'totalMovimientos',
            'totalIngresos',
            'totalEgresos',
            'cantidadIngresos',
            'cantidadEgresos',

            'saldoInicial',
            'disponible',
            'saldoCaja',

           'graficoMensualLabels',
            'graficoMensualIngresos',
            'graficoMensualEgresos',
            'graficoMensualCantidad',
            'graficoMensualIngresosCantidad',
            'graficoMensualEgresosCantidad',
            'categoriasGrafico'
        )
    );
}

    public function create()
{
    abort_unless(
        auth()->user()->can('movimientos.create'),
        403
    );

    $categorias = Categoria::orderBy('nombre')->get();

    $numero = Movimiento::siguienteNumero();

    $periodoActual = PeriodoService::obtenerPeriodoAbierto();

    return view(
        'movimientos.create',
        compact(
            'categorias',
            'numero',
            'periodoActual'
        )
    );
}

    /**
     * Guardar
     */
   public function store(StoreMovimientoRequest $request)
{
     abort_unless(
        auth()->user()->can('movimientos.create'),
        403
    );

    try {

        $datos = $request->validated();

        $datos['user_id'] = auth()->id();

        MovimientoService::guardar($datos);

        $this->agregarNotificacionMovimiento(
            'Movimiento registrado',
            'Movimiento registrado correctamente.',
            'success',
            'cil-plus'
        );

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

    public function edit(Movimiento $movimiento)
{
    abort_unless(
        auth()->user()->can('movimientos.edit'),
        403
    );

    $categorias = Categoria::orderBy('nombre')->get();

    $periodoActual = PeriodoService::obtenerPeriodoAbierto();

    return view(
        'movimientos.edit',
        compact(
            'movimiento',
            'categorias',
            'periodoActual'
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
     abort_unless(
        auth()->user()->can('movimientos.edit'),
        403
    );

    try {

        $datos = $request->validated();

        MovimientoService::actualizar(
            $movimiento,
            $datos
        );

        $this->agregarNotificacionMovimiento(
            'Movimiento actualizado',
            'Movimiento actualizado correctamente.',
            'info',
            'cil-pencil'
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

    public function destroy(Movimiento $movimiento)
{
    abort_unless(
        auth()->user()->can('movimientos.destroy'),
        403
    );

    try {

        MovimientoService::eliminar($movimiento);

        $this->agregarNotificacionMovimiento(
            'Movimiento eliminado',
            'Movimiento eliminado correctamente.',
            'danger',
            'cil-trash'
        );

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
    /**
     * Guarda una notificación temporal del movimiento en la sesión.
     *
     * No reemplaza las notificaciones flash existentes.
     * Solo mantiene las últimas 20 notificaciones de movimientos.
     */
    private function agregarNotificacionMovimiento(
        string $titulo,
        string $mensaje,
        string $color,
        string $icono
    ): void {
        $notificaciones = session(
            'notificaciones_movimientos',
            []
        );

        $notificaciones[] = [
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'color' => $color,
            'icono' => $icono,
            'created_at' => time(),
        ];

        $notificaciones = array_slice(
            $notificaciones,
            -20
        );

        session()->put(
            'notificaciones_movimientos',
            $notificaciones
        );
    }

}