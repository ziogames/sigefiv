<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Periodo;
use App\Services\PdfService;
use App\Services\ReporteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteApiController extends Controller
{
    /**
     * Retorna los años disponibles en la base de datos y la lista de meses.
     * La app llama a esto al abrir la pantalla para llenar los DropdownMenu.
     */
    public function filtros(): JsonResponse
    {
        $anios = Periodo::select('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');

        $meses = [
            ['id' => 1, 'nombre' => 'Enero'],
            ['id' => 2, 'nombre' => 'Febrero'],
            ['id' => 3, 'nombre' => 'Marzo'],
            ['id' => 4, 'nombre' => 'Abril'],
            ['id' => 5, 'nombre' => 'Mayo'],
            ['id' => 6, 'nombre' => 'Junio'],
            ['id' => 7, 'nombre' => 'Julio'],
            ['id' => 8, 'nombre' => 'Agosto'],
            ['id' => 9, 'nombre' => 'Septiembre'],
            ['id' => 10, 'nombre' => 'Octubre'],
            ['id' => 11, 'nombre' => 'Noviembre'],
            ['id' => 12, 'nombre' => 'Diciembre'],
        ];

        return response()->json([
            'success' => true,
            'anios'   => $anios,
            'meses'   => $meses,
        ]);
    }

    /**
     * Retorna los totales en JSON para las tarjetas de Jetpack Compose.
     */
    public function resumen(Request $request): JsonResponse
    {
        $request->validate([
            'anio'    => 'required|integer',
            'mes'     => 'nullable|integer|between:1,12',
            'reporte' => 'nullable|string|in:estado,ingresos,egresos,caja',
        ]);

        $anio = (int) $request->anio;
        $mes = $request->filled('mes') ? (int) $request->mes : null;
        $reporte = $request->get('reporte', 'estado');

        $movimientos = ReporteService::consultar($reporte, $anio, $mes);
        $resumen = ReporteService::obtenerResumen($movimientos, $anio, $mes);

        return response()->json([
            'success' => true,
            'anio'    => $anio,
            'mes'     => $mes,
            'reporte' => $reporte,
            'data'    => [
                'saldo_inicial' => (float) $resumen['saldo_inicial'],
                'ingresos'      => (float) $resumen['ingresos'],
                'disponible'    => (float) $resumen['disponible'],
                'egresos'       => (float) $resumen['egresos'],
                'saldo_caja'    => (float) $resumen['saldo_caja'],
                'total_filas'   => $movimientos->count(),
            ],
        ]);
    }

    public function pdf(Request $request)
    {
        $request->validate([
            'anio'  => 'required|integer',
            'mes'   => 'nullable|integer|between:1,12',
            'desde' => 'nullable|integer|between:1,12',
            'hasta' => 'nullable|integer|between:1,12',
        ]);

        if ($request->filled('mes')) {
            // Mes individual
            $request->merge([
                'desde'        => (int) $request->mes,
                'hasta'        => (int) $request->mes,
                'estado'       => true,
                'consolidado'  => false,
            ]);
        } else {
            // "Todo el año": genera balance de 1 a 12 con consolidado
            $request->merge([
                'desde'        => 1,
                'hasta'        => 12,
                'estado'       => true,
                'consolidado'  => true,
            ]);
        }

        $datos = ReporteService::prepararDatosPdf($request);
        $nombreArchivo = 'SIGEFIV_' . $request->anio . ($request->mes ? '_mes_' . $request->mes : '_anual') . '.pdf';

        return response()->stream(
            function () use ($datos) {
                echo PdfService::generar($datos)->output();
            },
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $nombreArchivo . '"',
                'Cache-Control'       => 'no-store, no-cache',
            ]
        );
    }

    /**
     * Genera y descarga el Excel para Android.
     */
    public function excel(Request $request): StreamedResponse
    {
        $request->validate([
            'anio' => 'required|integer',
            'mes'  => 'nullable|integer|between:1,12',
        ]);

        // Delegamos al mismo método excel que ya tienes afinado en ReporteController
        return app(\App\Http\Controllers\ReporteController::class)->excel($request);
    }
}