<?php

namespace App\Services;

use App\Models\Periodo;
use Illuminate\Support\Collection;

class ConsolidadoService
{
    /**
     * Obtiene los períodos para el consolidado.
     */
    public static function obtener(
        int $anio,
        int $desde,
        int $hasta
    ): Collection {

        return Periodo::where('anio', $anio)

            ->whereBetween(
                'mes',
                [$desde, $hasta]
            )

            ->orderBy('mes')

            ->get();

    }

    /**
     * Calcula el resumen del consolidado.
     */
    public static function resumen(
        Collection $periodos
    ): array {

        if ($periodos->isEmpty()) {

            return [

                'saldo_inicial' => 0,

                'ingresos' => 0,

                'egresos' => 0,

                'saldo_final' => 0,

            ];

        }

        return [

            'saldo_inicial' =>

                $periodos->first()->saldo_inicial,

            'ingresos' =>

                $periodos->sum('total_ingresos'),

            'egresos' =>

                $periodos->sum('total_egresos'),

            'saldo_final' =>

                $periodos->last()->saldo_final,

        ];

    }
}