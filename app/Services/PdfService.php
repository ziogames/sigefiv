<?php

namespace App\Services;

use App\Models\Configuracion;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfService
{
    /**
     * Genera el PDF de reportes.
     */
    public static function generar(array $datos)
    {
        $configuracion = Configuracion::first();

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

        /*
        |--------------------------------------------------------------------------
        | Movimientos
        |--------------------------------------------------------------------------
        */

      /*
|--------------------------------------------------------------------------
| Estados financieros por mes
|--------------------------------------------------------------------------
*/

$estados = $datos['estados'];

foreach ($estados as &$estado) {

    $estado['maxFilas'] = max(

        $estado['ingresos']->count(),

        $estado['egresos']->count()

    );

}

unset($estado);
        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView(

            'pdf.reporte',

            [

                'configuracion' => $configuracion,

                'meses' => $meses,

                'estados' => $estados,

                'resumen' => $datos['resumen'],

                'consolidado' => $datos['consolidado'],

                'generarEstado' => $datos['generarEstado'],

                'generarConsolidado' => $datos['generarConsolidado'],

                'anioEstado' => $datos['anioEstado'],

                'mesEstado' => $datos['mesEstado'],

                'anioConsolidado' => $datos['anioConsolidado'],

                'desde' => $datos['desde'],

                'hasta' => $datos['hasta'],

            ]

        );

        $pdf->setPaper(

            'A4',

            'landscape'

        );

        return $pdf;
    }
}