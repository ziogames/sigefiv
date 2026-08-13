<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Services\ReporteService;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function index(Request $request)
    {
        $anios = Periodo::select('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');

        $anio = $request->get(
            'anio',
            $anios->first()
        );

        $consolidado = [];

        if ($anio) {

$consolidado = ReporteService::obtenerConsolidadoDinamico(
    (int) $anio,
    1,
    12
);

        }

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

        return view(
            'caja.index',
            compact(
                'anios',
                'anio',
                'meses',
                'consolidado'
            )
        );
    }
}