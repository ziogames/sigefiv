<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Periodo;
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
                'saldo_inicial' => self::saldoInicial($fecha->year, $fecha->month),
                'total_ingresos' => 0,
                'total_egresos' => 0,
                'saldo_final' => self::saldoInicial($fecha->year, $fecha->month),
                'estado' => 'Abierto',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Si el período fue creado por primera vez,
        | cerrar automáticamente el período anterior y sincronizar saldo inicial.
        |--------------------------------------------------------------------------
        */
        if ($periodo->wasRecentlyCreated) {
            $mesAnterior = $fecha->month - 1;
            $anioAnterior = $fecha->year;

            if ($mesAnterior === 0) {
                $mesAnterior = 12;
                $anioAnterior--;
            }

            $periodoAnterior = Periodo::where('anio', $anioAnterior)
                ->where('mes', $mesAnterior)
                ->where('estado', 'Abierto')
                ->first();

            if ($periodoAnterior) {
                self::cerrar($periodoAnterior);
                self::actualizarTotales($periodo);
            }
        }

        return $periodo;
    }

    /**
     * Obtiene el saldo inicial del período (cierre del mes anterior o apertura inicial).
     */
    private static function saldoInicial(int $anio, int $mes): float
    {
        $mesAnterior = $mes - 1;
        $anioAnterior = $anio;

        if ($mesAnterior === 0) {
            $mesAnterior = 12;
            $anioAnterior--;
        }

        $periodoAnterior = Periodo::where('anio', $anioAnterior)
            ->where('mes', $mesAnterior)
            ->first();

        if ($periodoAnterior) {
            return (float) $periodoAnterior->saldo_final;
        }

        $config = Configuracion::first();

        if (
            $config &&
            $config->contabilidad_iniciada &&
            $config->anio_inicio == $anio &&
            $config->mes_inicio == $mes
        ) {
            return (float) $config->saldo_apertura;
        }

        return 0.0;
    }

    /**
     * Recalcula los totales del período aplicando:
     * Saldo Disponible = Saldo Inicial + Ingresos
     * Saldo Final = Saldo Disponible - Egresos
     */
    public static function actualizarTotales(Periodo $periodo): void
    {
        $saldoInicial = self::saldoInicial($periodo->anio, $periodo->mes);

        $ingresos = $periodo->movimientos()
            ->where('tipo', 'Ingreso')
            ->where('estado', 'Registrado')
            ->sum('monto');

        $egresos = $periodo->movimientos()
            ->where('tipo', 'Egreso')
            ->where('estado', 'Registrado')
            ->sum('monto');

        $saldoDisponible = $saldoInicial + $ingresos;
        $saldoFinal = $saldoDisponible - $egresos;

        $periodo->update([
            'saldo_inicial'  => $saldoInicial,
            'total_ingresos' => $ingresos,
            'total_egresos'  => $egresos,
            'saldo_final'    => $saldoFinal,
        ]);
    }

    /**
     * Cerrar período.
     */
    public static function cerrar(Periodo $periodo): void
    {
        self::actualizarTotales($periodo);

        $periodo->update([
            'estado' => 'Cerrado',
            'fecha_cierre' => now(),
        ]);
    }

    /**
     * Abrir período.
     */
    public static function abrir(Periodo $periodo): void
    {
        $periodo->update([
            'estado' => 'Abierto',
            'fecha_cierre' => null,
        ]);

        $notificacionService = app(\App\Services\NotificacionService::class);

        $notificacionService->enviarATodos(
            titulo: 'Período abierto',
            mensaje: 'Zoe ha abierto el período ' . $periodo->nombre_completo . '.',
            tipo: 'periodo_abierto',
            data: [
                'periodo_id' => (string) $periodo->id,
                'anio' => (string) $periodo->anio,
                'mes' => (string) $periodo->mes,
                'evento' => 'periodo_abierto',
            ],
        );
    }

    /**
     * Crear el primer período del sistema.
     */
    public static function crearPrimerPeriodo($configuracion): Periodo
    {
        return Periodo::firstOrCreate(
            [
                'anio' => $configuracion->anio_inicio,
                'mes' => $configuracion->mes_inicio,
            ],
            [
                'nombre' => Periodo::nombreMes($configuracion->mes_inicio),
                'saldo_inicial' => $configuracion->saldo_apertura,
                'total_ingresos' => 0,
                'total_egresos' => 0,
                'saldo_final' => $configuracion->saldo_apertura,
                'estado' => 'Abierto',
            ]
        );
    }

    /**
     * Verifica si existe un cambio de mes que requiera cierre.
     */
    public static function verificarCambioDeMes(): array
    {
        $periodo = Periodo::obtenerAbierto();

        if (! $periodo) {
            return [
                'requiere_cierre' => false,
                'periodo' => null,
                'siguiente_periodo' => null,
                'resumen' => null,
            ];
        }

        self::actualizarTotales($periodo);

        $fecha = Carbon::create($periodo->anio, $periodo->mes, 1)->addMonth();

        return [
            'requiere_cierre' => true,
            'periodo' => $periodo,
            'siguiente_periodo' => Periodo::nombreMes($fecha->month) . ' ' . $fecha->year,
            'resumen' => [
                'saldo_inicial' => $periodo->saldo_inicial,
                'ingresos' => $periodo->total_ingresos,
                'egresos' => $periodo->total_egresos,
                'saldo_final' => $periodo->saldo_final,
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
            self::actualizarTotales($periodo);

            $periodo->update([
                'estado' => 'Cerrado',
                'fecha_cierre' => now(),
            ]);

            self::crearSiguientePeriodo($periodo);

            $notificacionService = app(\App\Services\NotificacionService::class);

            $notificacionService->enviarATodos(
                titulo: 'Cierre de período',
                mensaje: 'Zoe ha cerrado el período ' .
                    $periodo->nombre_completo .
                    '. Saldo final: S/ ' .
                    number_format((float) $periodo->saldo_final, 2, '.', ''),
                tipo: 'periodo_cerrado',
                data: [
                    'periodo_id' => (string) $periodo->id,
                    'anio' => (string) $periodo->anio,
                    'mes' => (string) $periodo->mes,
                    'saldo_inicial' => (string) $periodo->saldo_inicial,
                    'total_ingresos' => (string) $periodo->total_ingresos,
                    'total_egresos' => (string) $periodo->total_egresos,
                    'saldo_final' => (string) $periodo->saldo_final,
                    'evento' => 'periodo_cerrado',
                ],
            );
        });
    }

    /**
     * Crea automáticamente el siguiente período.
     */
    private static function crearSiguientePeriodo(Periodo $periodo): Periodo
    {
        $mes = $periodo->mes + 1;
        $anio = $periodo->anio;

        if ($mes > 12) {
            $mes = 1;
            $anio++;
        }

        return Periodo::firstOrCreate(
            [
                'anio' => $anio,
                'mes' => $mes,
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
            throw new \Exception("El período {$periodo->nombre_completo} está cerrado.");
        }
    }
}