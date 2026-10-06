<?php

namespace App\Services\Estadisticas;

use App\Models\Movimiento;
use App\Models\Periodo;
use Illuminate\Support\Collection;

class ZoeEstadisticasService
{



/**
 * Obtener resumen financiero de los últimos períodos.
 *
 * No modifica ningún dato.
 */
public function resumenFinanciero(int $limite = 6): array
{
    $limite = max(1, min($limite, 24));

    $periodos = Periodo::query()
        ->orderByDesc('anio')
        ->orderByDesc('mes')
        ->limit($limite)
        ->get();

    $periodos = $periodos->reverse()->values();

    if ($periodos->isEmpty()) {
        return [
            'tipo' => 'resumen_financiero',
            'titulo' => 'Resumen financiero',
            'descripcion' => 'No hay datos disponibles para mostrar.',
            'unidad' => 'S/',
            'cantidad_periodos' => 0,
            'ingresos_total' => 0,
            'egresos_total' => 0,
            'diferencia' => 0,
            'saldo_inicial' => 0,
            'saldo_final' => 0,
        ];
    }

    $ingresosTotal = round(
        (float) $periodos->sum('total_ingresos'),
        2
    );

    $egresosTotal = round(
        (float) $periodos->sum('total_egresos'),
        2
    );

    $diferencia = round(
        $ingresosTotal - $egresosTotal,
        2
    );

    $saldoInicial = round(
        (float) $periodos->first()->saldo_inicial,
        2
    );

    $saldoFinal = round(
        (float) $periodos->last()->saldo_final,
        2
    );

    $descripcion = 'En los últimos '
        . $periodos->count()
        . ' períodos ingresaron '
        . $this->soles($ingresosTotal)
        . ' y se gastaron '
        . $this->soles($egresosTotal)
        . '. La diferencia entre ambos fue de '
        . $this->soles(abs($diferencia))
        . '.';

    if ($diferencia < 0) {
        $descripcion = 'En los últimos '
            . $periodos->count()
            . ' períodos se gastaron '
            . $this->soles(abs($diferencia))
            . ' más de lo que ingresó.';
    }

    return [
        'tipo' => 'resumen_financiero',
        'titulo' => 'Resumen financiero',
        'descripcion' => $descripcion,
        'unidad' => 'S/',
        'cantidad_periodos' => $periodos->count(),
        'ingresos_total' => $ingresosTotal,
        'egresos_total' => $egresosTotal,
        'diferencia' => $diferencia,
        'saldo_inicial' => $saldoInicial,
        'saldo_final' => $saldoFinal,
    ];
}
    /**
     * Obtener ingresos y egresos de los últimos períodos.
     *
     * No modifica ningún dato.
     */
    public function ingresosVsEgresos(int $limite = 6): array
    {
        $limite = max(1, min($limite, 24));

        $periodos = Periodo::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->limit($limite)
            ->get();

        $periodos = $periodos->reverse()->values();

        $datos = $periodos->map(function (Periodo $periodo) {
            return [
                'periodo_id' => $periodo->id,
                'anio' => (int) $periodo->anio,
                'mes' => (int) $periodo->mes,
                'periodo' => $periodo->nombre . ' ' . $periodo->anio,
                'ingresos' => (float) $periodo->total_ingresos,
                'egresos' => (float) $periodo->total_egresos,
            ];
        })->values()->all();

        $mesesIngresosMayores = collect($datos)
            ->filter(fn ($dato) => $dato['ingresos'] > $dato['egresos'])
            ->count();

        $mesesEgresosMayores = collect($datos)
            ->filter(fn ($dato) => $dato['egresos'] > $dato['ingresos'])
            ->count();

        $mesesIguales = collect($datos)
            ->filter(fn ($dato) => $dato['ingresos'] == $dato['egresos'])
            ->count();

        if (empty($datos)) {
            $descripcion = 'No hay datos disponibles para mostrar.';
        } elseif ($mesesIngresosMayores > $mesesEgresosMayores) {
            $descripcion = "En los últimos "
                . count($datos)
                . " meses, los ingresos superaron a los egresos en "
                . $mesesIngresosMayores
                . " período(s).";

            if ($mesesEgresosMayores > 0) {
                $descripcion .= " En "
                    . $mesesEgresosMayores
                    . " período(s), los egresos fueron mayores.";
            }
        } elseif ($mesesEgresosMayores > $mesesIngresosMayores) {
            $descripcion = "En los últimos "
                . count($datos)
                . " meses, los egresos superaron a los ingresos en "
                . $mesesEgresosMayores
                . " período(s).";

            if ($mesesIngresosMayores > 0) {
                $descripcion .= " En "
                    . $mesesIngresosMayores
                    . " período(s), los ingresos fueron mayores.";
            }
        } else {
            $descripcion = "Los ingresos y egresos estuvieron equilibrados en la cantidad de períodos analizados.";

            if ($mesesIguales > 0) {
                $descripcion .= " En {$mesesIguales} período(s), ambos valores fueron iguales.";
            }
        }

        return [
            'tipo' => 'ingresos_vs_egresos',
            'titulo' => 'Ingresos vs egresos',
            'descripcion' => $descripcion,
            'unidad' => 'S/',
            'cantidad_periodos' => count($datos),
            'datos' => $datos,
        ];
    }

    /**
     * Obtener la evolución del saldo de los últimos períodos.
     *
     * No modifica ningún dato.
     */
    public function evolucionSaldo(int $limite = 6): array
    {
        $limite = max(1, min($limite, 24));

        $periodos = Periodo::query()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->limit($limite)
            ->get();

        $periodos = $periodos->reverse()->values();

        $datos = $periodos->map(function (Periodo $periodo) {
            return [
                'periodo_id' => $periodo->id,
                'anio' => (int) $periodo->anio,
                'mes' => (int) $periodo->mes,
                'periodo' => $periodo->nombre . ' ' . $periodo->anio,
                'saldo_inicial' => (float) $periodo->saldo_inicial,
                'ingresos' => (float) $periodo->total_ingresos,
                'egresos' => (float) $periodo->total_egresos,
                'saldo_final' => (float) $periodo->saldo_final,
            ];
        })->values()->all();

        if (empty($datos)) {
            $descripcion = 'No hay datos disponibles para mostrar.';
        } else {
            $primerSaldo = (float) $datos[0]['saldo_final'];
            $ultimoSaldo = (float) $datos[count($datos) - 1]['saldo_final'];
            $variacion = round($ultimoSaldo - $primerSaldo, 2);

            if ($variacion > 0) {
                $descripcion = 'El saldo aumentó de '
                    . $this->soles($primerSaldo)
                    . ' a '
                    . $this->soles($ultimoSaldo)
                    . ', con una variación de '
                    . $this->soles($variacion)
                    . '.';
            } elseif ($variacion < 0) {
                $descripcion = 'El saldo disminuyó de '
                    . $this->soles($primerSaldo)
                    . ' a '
                    . $this->soles($ultimoSaldo)
                    . ', con una variación de '
                    . $this->soles(abs($variacion))
                    . '.';
            } else {
                $descripcion = 'El saldo se mantuvo en '
                    . $this->soles($ultimoSaldo)
                    . ' entre el primer y el último período mostrado.';
            }
        }

        return [
            'tipo' => 'evolucion_saldo',
            'titulo' => 'Evolución del saldo',
            'descripcion' => $descripcion,
            'unidad' => 'S/',
            'cantidad_periodos' => count($datos),
            'datos' => $datos,
        ];
    }

    /**
     * Obtener los egresos agrupados por categoría
     * de un período determinado.
     *
     * Solo considera movimientos registrados.
     */
    public function egresosPorCategoria(int $anio, int $mes): array
    {
        $periodo = Periodo::query()
            ->where('anio', $anio)
            ->where('mes', $mes)
            ->first();

        if (!$periodo) {
            return [
                'tipo' => 'egresos_por_categoria',
                'titulo' => 'Egresos por categoría',
                'descripcion' => 'No hay datos disponibles para el período solicitado.',
                'unidad' => 'S/',
                'periodo' => Periodo::nombreMes($mes) . ' ' . $anio,
                'periodo_id' => null,
                'total_egresos' => 0,
                'datos' => [],
            ];
        }

        $datos = Movimiento::query()
            ->where('periodo_id', $periodo->id)
            ->where('tipo', 'Egreso')
            ->where('estado', 'Registrado')
            ->with('categoria')
            ->get()
            ->groupBy(function (Movimiento $movimiento) {
                return $movimiento->categoria?->nombre
                    ?? 'Sin categoría';
            })
            ->map(function (Collection $movimientos, string $categoria) {
                return [
                    'categoria' => $categoria,
                    'monto' => round(
                        (float) $movimientos->sum('monto'),
                        2
                    ),
                ];
            })
            ->sortByDesc('monto')
            ->values()
            ->all();

        $totalEgresos = round(
            (float) $periodo->total_egresos,
            2
        );

        if (empty($datos)) {
            $descripcion = 'No se registraron egresos en este período.';
        } else {
            $mayorCategoria = $datos[0]['categoria'];
            $mayorMonto = (float) $datos[0]['monto'];

            $descripcion = 'El mayor egreso corresponde a '
                . $mayorCategoria
                . ', con '
                . $this->soles($mayorMonto)
                . '. El total de egresos del período fue '
                . $this->soles($totalEgresos)
                . '.';
        }

        return [
            'tipo' => 'egresos_por_categoria',
            'titulo' => 'Egresos por categoría',
            'descripcion' => $descripcion,
            'unidad' => 'S/',
            'periodo' => $periodo->nombre . ' ' . $periodo->anio,
            'periodo_id' => $periodo->id,
            'total_egresos' => $totalEgresos,
            'datos' => $datos,
        ];
    }

    /**
     * Formatear un monto como soles.
     */
    private function soles(float $monto): string
    {
        return 'S/ ' . number_format($monto, 2, '.', ',');
    }
}