<?php

namespace App\Services;

use App\Models\Movimiento;
use App\Models\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReporteService
{
    /**
     * Consulta movimientos aplicando filtros y cálculo de saldos progresivos.
     */
    public static function consultar(
        string $reporte,
        int $anio,
        ?int $mes = null
    ): Collection {
        $consulta = Movimiento::with([
            'categoria',
            'usuario',
          
            'periodo'
        ]);

        $consulta->whereHas('periodo', function ($q) use ($anio, $mes) {
            $q->where('anio', $anio);
            if ($mes) {
                $q->where('mes', $mes);
            }
        });

        match ($reporte) {
            'ingresos' => $consulta->where('tipo', 'Ingreso'),
            'egresos'  => $consulta->where('tipo', 'Egreso'),
            default    => null,
        };

        $movimientos = $consulta
            ->orderBy('fecha')
            ->orderBy('numero')
            ->get();

        // Determinar saldo inicial real
        if ($movimientos->isNotEmpty()) {
            $saldo = (float) ($movimientos->first()->periodo->saldo_inicial ?? 0);
        } elseif ($mes) {
            $periodo = Periodo::where('anio', $anio)->where('mes', $mes)->first();
            $saldo = (float) ($periodo->saldo_inicial ?? 0);
        } else {
            $saldo = 0.0;
        }

        foreach ($movimientos as $movimiento) {
            $movimiento->saldo_anterior = $saldo;

            if ($movimiento->tipo === 'Ingreso') {
                $saldo += $movimiento->monto;
            } else {
                $saldo -= $movimiento->monto;
            }

            $movimiento->saldo = $saldo;
        }

        return $movimientos;
    }

    /**
     * Devuelve el Estado Financiero por cada mes dentro del intervalo.
     */
    public static function consultarIntervalo(
        int $anio,
        int $desde,
        int $hasta
    ): array {
        $estados = [];

        for ($mes = $desde; $mes <= $hasta; $mes++) {
            $movimientos = self::consultar('estado', $anio, $mes);

            $estados[] = [
                'mes'         => $mes,
                'anio'        => $anio,
                'movimientos' => $movimientos,
                'ingresos'    => $movimientos->where('tipo', 'Ingreso')->values(),
                'egresos'     => $movimientos->where('tipo', 'Egreso')->values(),
                'maxFilas'    => max(
                    $movimientos->where('tipo', 'Ingreso')->count(),
                    $movimientos->where('tipo', 'Egreso')->count()
                ),
                'resumen'     => self::obtenerResumen($movimientos, $anio, $mes),
            ];
        }

        return $estados;
    }

    /**
     * Obtiene el resumen financiero.
     * Soporta meses sin movimientos consultando el saldo del período.
     */
    public static function obtenerResumen($movimientos, ?int $anio = null, ?int $mes = null): array
    {
        if ($movimientos->isEmpty()) {
            $saldoInicial = 0.0;
            if ($anio && $mes) {
                $periodo = Periodo::where('anio', $anio)->where('mes', $mes)->first();
                $saldoInicial = (float) ($periodo->saldo_inicial ?? 0);
            }

            return [
                'saldo_inicial' => $saldoInicial,
                'ingresos'      => 0.0,
                'disponible'    => $saldoInicial,
                'egresos'       => 0.0,
                'saldo_caja'    => $saldoInicial,
            ];
        }

        $saldoInicial = (float) $movimientos->first()->saldo_anterior;
        $ingresos = (float) $movimientos->where('tipo', 'Ingreso')->sum('monto');
        $egresos = (float) $movimientos->where('tipo', 'Egreso')->sum('monto');

        return [
            'saldo_inicial' => $saldoInicial,
            'ingresos'      => $ingresos,
            'disponible'    => $saldoInicial + $ingresos,
            'egresos'       => $egresos,
            'saldo_caja'    => $saldoInicial + $ingresos - $egresos,
        ];
    }

    /**
     * Obtiene los períodos del consolidado.
     */
    public static function obtenerConsolidado(
        int $anio,
        int $desde,
        int $hasta
    ) {
        return Periodo::where('anio', $anio)
            ->whereBetween('mes', [$desde, $hasta])
            ->orderBy('mes')
            ->get([
                'id',
                'anio',
                'mes',
                'saldo_inicial',
                'total_ingresos',
                'total_egresos',
                'saldo_final',
            ]);
    }

    /**
     * Obtiene el consolidado recalculando cada mes.
     * Si ya se calcularon los estados mensuales, los reutiliza para evitar duplicar queries.
     */
    public static function obtenerConsolidadoDinamico(
        int $anio,
        int $desde,
        int $hasta,
        ?array $estadosPrecargados = null
    ): array {
        if ($estadosPrecargados !== null) {
            return array_map(function ($estado) {
                return (object)[
                    'anio'           => $estado['anio'],
                    'mes'            => $estado['mes'],
                    'saldo_inicial'  => $estado['resumen']['saldo_inicial'],
                    'total_ingresos' => $estado['resumen']['ingresos'],
                    'total_egresos'  => $estado['resumen']['egresos'],
                    'saldo_final'    => $estado['resumen']['saldo_caja'],
                ];
            }, $estadosPrecargados);
        }

        $consolidado = [];
        for ($mes = $desde; $mes <= $hasta; $mes++) {
            $movimientos = self::consultar('estado', $anio, $mes);
            $resumen = self::obtenerResumen($movimientos, $anio, $mes);

            $consolidado[] = (object)[
                'anio'           => $anio,
                'mes'            => $mes,
                'saldo_inicial'  => $resumen['saldo_inicial'],
                'total_ingresos' => $resumen['ingresos'],
                'total_egresos'  => $resumen['egresos'],
                'saldo_final'    => $resumen['saldo_caja'],
            ];
        }

        return $consolidado;
    }

    /**
     * Prepara toda la información para el PDF sin valores en cero ni consultas redundantes.
     */
    public static function prepararDatosPdf(Request $request): array
    {
        $generarEstado = $request->boolean('estado', true);
        $generarConsolidado = $request->boolean('consolidado', true);

        $anioEstado = (int) $request->anio;
        $mesEstado = $request->filled('mes') ? (int) $request->mes : null;
        $desde = (int) ($request->desde ?: ($mesEstado ?: 1));
        $hasta = (int) ($request->hasta ?: ($mesEstado ?: 12));

        $estados = [];
        if ($generarEstado) {
            $estados = self::consultarIntervalo($anioEstado, $desde, $hasta);
        }

        // Reutiliza los estados ya calculados para no rehacer 12 consultas a la base de datos
        $consolidado = self::obtenerConsolidadoDinamico(
            $anioEstado,
            $desde,
            $hasta,
            !empty($estados) ? $estados : null
        );

        // Movimientos consolidados del período para el resumen general del reporte
        $movimientos = self::consultar('estado', $anioEstado, $mesEstado);

        return [
            'movimientos'        => $movimientos,
            'estados'            => $estados,
            'resumen'            => self::obtenerResumen($movimientos, $anioEstado, $mesEstado),
            'consolidado'        => $consolidado,
            'generarEstado'      => $generarEstado,
            'generarConsolidado' => $generarConsolidado,
            'anioEstado'         => $anioEstado,
            'mesEstado'          => $mesEstado,
            'anioConsolidado'    => $anioEstado,
            'desde'              => $desde,
            'hasta'              => $hasta,
        ];
    }
}