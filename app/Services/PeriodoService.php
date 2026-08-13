<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Periodo;
use App\Models\Bitacora;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodoService
{
    /**
     * Obtiene el período correspondiente a una fecha.
     * Si no existe, lo crea automáticamente.
     */
    public static function obtenerPorFecha($fecha): Periodo
    {
        $fecha = Carbon::parse($fecha);

        $periodo = Periodo::firstOrCreate(

            [

                'anio' => $fecha->year,

                'mes' => $fecha->month,

            ],

            [

                'nombre' => Periodo::nombreMes($fecha->month),

                'saldo_inicial' => self::saldoInicial(
                    $fecha->year,
                    $fecha->month
                ),

                'total_ingresos' => 0,

                'total_egresos' => 0,

                'saldo_final' => self::saldoInicial(
                    $fecha->year,
                    $fecha->month
                ),

                'estado' => 'Abierto',

            ]

        );

        /*
        |--------------------------------------------------------------------------
        | Si el período fue creado por primera vez,
        | cerrar automáticamente el período anterior.
        |--------------------------------------------------------------------------
        */

        if ($periodo->wasRecentlyCreated) {

            $mesAnterior = $fecha->month - 1;
            $anioAnterior = $fecha->year;

            if ($mesAnterior == 0) {

                $mesAnterior = 12;
                $anioAnterior--;

            }

            $periodoAnterior = Periodo::where('anio', $anioAnterior)
                ->where('mes', $mesAnterior)
                ->where('estado', 'Abierto')
                ->first();

            if ($periodoAnterior) {

                self::cerrar($periodoAnterior);

            }

        }

        return $periodo;
    }

    /**
     * Obtiene el saldo inicial del período.
     */
    private static function saldoInicial(
        int $anio,
        int $mes
    ): float {

        $mesAnterior = $mes - 1;
        $anioAnterior = $anio;

        if ($mesAnterior == 0) {

            $mesAnterior = 12;
            $anioAnterior--;

        }

        $periodoAnterior = Periodo::where('anio', $anioAnterior)
            ->where('mes', $mesAnterior)
            ->first();

        if ($periodoAnterior) {

            return $periodoAnterior->saldo_final;

        }

        $config = Configuracion::first();

        if (

            $config &&
            $config->contabilidad_iniciada &&
            $config->anio_inicio == $anio &&
            $config->mes_inicio == $mes

        ) {

            return $config->saldo_apertura;

        }

        return 0;

    }

    /**
     * Recalcula los totales del período.
     */
    public static function actualizarTotales(
        Periodo $periodo
    ): void {

        $ingresos = $periodo->movimientos()

            ->where('tipo', 'Ingreso')

            ->where('estado', 'Registrado')

            ->sum('monto');

        $egresos = $periodo->movimientos()

            ->where('tipo', 'Egreso')

            ->where('estado', 'Registrado')

            ->sum('monto');

        $periodo->update([

            'total_ingresos' => $ingresos,

            'total_egresos' => $egresos,

            'saldo_final' =>

                $periodo->saldo_inicial +

                $ingresos -

                $egresos,

        ]);

    }

    /**
     * Cerrar período.
     */
    public static function cerrar(
        Periodo $periodo
    ): void {

        self::actualizarTotales($periodo);

        $periodo->update([

            'estado' => 'Cerrado',

        ]);

    }

    /**
     * Abrir período.
     */
    public static function abrir(
        Periodo $periodo
    ): void {

        $periodo->update([

            'estado' => 'Abierto',

        ]);

    }

    /**
     * Crear el primer período del sistema.
     */
    public static function crearPrimerPeriodo(
        $configuracion
    ): Periodo {

        return Periodo::firstOrCreate(

            [

                'anio' => $configuracion->anio_inicio,

                'mes' => $configuracion->mes_inicio,

            ],

            [

                'nombre' => Periodo::nombreMes(
                    $configuracion->mes_inicio
                ),

                'saldo_inicial' => $configuracion->saldo_apertura,

                'total_ingresos' => 0,

                'total_egresos' => 0,

                'saldo_final' => $configuracion->saldo_apertura,

                'estado' => 'Abierto',

            ]

        );

    }
    public static function verificarCambioDeMes(): array
{
    /*
    |--------------------------------------------------------------------------
    | Buscar el último período abierto
    |--------------------------------------------------------------------------
    */

    $periodo = Periodo::obtenerAbierto();
    /*
    |--------------------------------------------------------------------------
    | No existe ningún período abierto
    |--------------------------------------------------------------------------
    */

    if (!$periodo) {

        return [

            'requiere_cierre' => false,

            'periodo' => null,

            'siguiente_periodo' => null,

            'resumen' => null,

        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Recalcular totales
    |--------------------------------------------------------------------------
    */

    self::actualizarTotales($periodo);

    /*
    |--------------------------------------------------------------------------
    | Calcular siguiente período
    |--------------------------------------------------------------------------
    */

    $fecha = Carbon::create(
        $periodo->anio,
        $periodo->mes,
        1
    )->addMonth();

    /*
    |--------------------------------------------------------------------------
    | Respuesta
    |--------------------------------------------------------------------------
    */

    return [

        'requiere_cierre' => true,

        'periodo' => $periodo,

        'siguiente_periodo' =>

            Periodo::nombreMes($fecha->month)

            .' '

            .$fecha->year,

        'resumen' => [

            'saldo_inicial' =>

                $periodo->saldo_inicial,

            'ingresos' =>

                $periodo->total_ingresos,

            'egresos' =>

                $periodo->total_egresos,

            'saldo_final' =>

                $periodo->saldo_final,

        ],

    ];
}
/**
 * Devuelve el período abierto.
 */
public static function obtenerPeriodoAbierto(): ?Periodo
{
    return Periodo::obtenerAbierto();
}
/**
 * Cierra un período contable.
 */
public static function cerrarPeriodo(Periodo $periodo): void
{
    DB::transaction(function () use ($periodo) {

        /*
        |----------------------------------------------------------
        | Recalcular totales
        |----------------------------------------------------------
        */

        self::actualizarTotales($periodo);

        /*
        |----------------------------------------------------------
        | Cerrar período
        |----------------------------------------------------------
        */

        $periodo->update([

            'estado' => 'Cerrado',

        ]);

        /*
        |----------------------------------------------------------
        | Crear siguiente período
        |----------------------------------------------------------
        */

        self::crearSiguientePeriodo($periodo);

    });
}
/**
 * Crea automáticamente el siguiente período.
 */
private static function crearSiguientePeriodo(
    Periodo $periodo
): Periodo {

    /*
    |--------------------------------------------------------------------------
    | Calcular siguiente período
    |--------------------------------------------------------------------------
    */

    $mes = $periodo->mes + 1;

    $anio = $periodo->anio;

    if ($mes > 12) {

        $mes = 1;

        $anio++;

    }

    /*
    |--------------------------------------------------------------------------
    | Crear si no existe
    |--------------------------------------------------------------------------
    */

    return Periodo::firstOrCreate(

        [

            'anio' => $anio,

            'mes'  => $mes,

        ],

        [

            'nombre' => Periodo::nombreMes($mes),

            'saldo_inicial' => $periodo->saldo_final,

            'total_ingresos' => 0,

            'total_egresos' => 0,

            'saldo_final' => $periodo->saldo_final,

            'estado' => 'Abierto',

        ]

    );

}
/**
 * Verifica que un período permita registrar movimientos.
 */
public static function validarPeriodoAbierto(Periodo $periodo): void
{
    if ($periodo->estaCerrado()) {
        throw new \Exception(
            "El período {$periodo->nombre_completo} está cerrado."
        );
    }
}
}