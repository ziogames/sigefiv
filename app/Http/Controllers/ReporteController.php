<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Services\PdfService;
use App\Services\ReporteService;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    /**
     * Pantalla de reportes
     */
    public function index(Request $request)
    {
        $reporte = $request->get('reporte', 'estado');

        $movimientos = collect();

        $consolidado = [];


        /*
        |--------------------------------------------------------------------------
        | Movimientos
        |--------------------------------------------------------------------------
        */

        if ($request->filled('anio')) {

            $movimientos = ReporteService::consultar(
                $reporte,
                (int) $request->anio,
                $request->filled('mes')
                    ? (int) $request->mes
                    : null
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Consolidado para Reporte de Caja
        |--------------------------------------------------------------------------
        */

        if (
            $reporte === 'caja' &&
            $request->filled('anio')
        ) {

            $consolidado = ReporteService::obtenerConsolidadoDinamico(
                (int) $request->anio,
                1,
                12
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Años disponibles
        |--------------------------------------------------------------------------
        */

        $anios = Periodo::select('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio');


        /*
        |--------------------------------------------------------------------------
        | Meses
        |--------------------------------------------------------------------------
        */

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
        | Resumen
        |--------------------------------------------------------------------------
        */

        $resumen = ReporteService::obtenerResumen(
            $movimientos
        );


        /*
        |--------------------------------------------------------------------------
        | Período seleccionado
        |--------------------------------------------------------------------------
        */

        $periodo = null;

        if (
            $request->filled('anio') &&
            $request->filled('mes')
        ) {

            $periodo = Periodo::where(
                'anio',
                $request->anio
            )
            ->where(
                'mes',
                $request->mes
            )
            ->first();

        }


        /*
        |--------------------------------------------------------------------------
        | Título
        |--------------------------------------------------------------------------
        */

        $titulo = match ($reporte) {

            'estado'   => 'Estado de Cuenta',

            'ingresos' => 'Reporte de Ingresos',

            'egresos'  => 'Reporte de Egresos',

            'caja'     => 'Reporte de Caja',

            default    => 'Reportes',

        };


        /*
        |--------------------------------------------------------------------------
        | Vista
        |--------------------------------------------------------------------------
        */

        return view(
            'reportes.index',
            compact(
                'anios',
                'meses',
                'movimientos',
                'resumen',
                'periodo',
                'reporte',
                'titulo',
                'consolidado'
            )
        );
    }


    /**
     * Genera el PDF
     */
    public function pdf(Request $request)
    {
        $datos = ReporteService::prepararDatosPdf(
            $request
        );

        return PdfService::generar(
            $datos
        )->stream(
            'EstadoFinanciero.pdf'
        );
    }

    /**
     * Genera el archivo Excel completo.
     *
     * Hojas:
     * 1. Caja
     * 2. Consolidado del periodo
     * 3+. Un detalle por cada mes hasta el mes seleccionado.
     */
    public function excel(Request $request)
    {
        $anio = (int) $request->get('anio');

        $mesSeleccionado = $request->filled('mes')
            ? (int) $request->get('mes')
            : 12;

        if ($mesSeleccionado < 1 || $mesSeleccionado > 12) {
            $mesSeleccionado = 12;
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

        /*
        |--------------------------------------------------------------------------
        | Consolidado dinámico
        |--------------------------------------------------------------------------
        | Se calcula desde los movimientos reales para evitar utilizar totales
        | antiguos almacenados en Periodo.
        */

        $consolidado = ReporteService::obtenerConsolidadoDinamico(
            $anio,
            1,
            $mesSeleccionado
        );

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        /*
        |--------------------------------------------------------------------------
        | HOJA 1 - CAJA
        |--------------------------------------------------------------------------
        */

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Caja');

        $saldoInicial = $consolidado[0]->saldo_inicial ?? 0;
        $totalIngresos = collect($consolidado)->sum('total_ingresos');
        $totalEgresos = collect($consolidado)->sum('total_egresos');
        $saldoFinal = collect($consolidado)->last()->saldo_final ?? 0;

        $sheet->setCellValue('A1', 'SIGEFIV');
        $sheet->setCellValue('A2', 'CAJA');
        $sheet->setCellValue(
            'A3',
            'Año ' . $anio . ' - Hasta ' . $meses[$mesSeleccionado]
        );

        $sheet->mergeCells('A1:E1');
        $sheet->mergeCells('A2:E2');
        $sheet->mergeCells('A3:E3');

        $this->aplicarTituloExcel($sheet, 'A1:E1', 18);
        $this->aplicarSubtituloExcel($sheet, 'A2:E2', 14);
        $this->aplicarTextoInformativoExcel($sheet, 'A3:E3');

        $sheet->setCellValue('A5', 'Saldo inicial');
        $sheet->setCellValue('B5', $saldoInicial);

        $sheet->setCellValue('C5', 'Ingresos');
        $sheet->setCellValue('D5', $totalIngresos);

        $sheet->setCellValue('E5', 'Egresos');
        $sheet->setCellValue('F5', $totalEgresos);

        $sheet->setCellValue('G5', 'Saldo final');
        $sheet->setCellValue('H5', $saldoFinal);

        $this->aplicarEncabezadoExcel($sheet, 'A5:H5');

        $sheet->getStyle('B5')->getNumberFormat()
            ->setFormatCode('"S/" #,##0.00');

        $sheet->getStyle('D5')->getNumberFormat()
            ->setFormatCode('"S/" #,##0.00');

        $sheet->getStyle('F5')->getNumberFormat()
            ->setFormatCode('"S/" #,##0.00');

        $sheet->getStyle('H5')->getNumberFormat()
            ->setFormatCode('"S/" #,##0.00');

        $fila = 8;

        $sheet->setCellValue('A' . $fila, 'Mes');
        $sheet->setCellValue('B' . $fila, 'Saldo inicial');
        $sheet->setCellValue('C' . $fila, 'Ingresos');
        $sheet->setCellValue('D' . $fila, 'Egresos');
        $sheet->setCellValue('E' . $fila, 'Saldo final');

        $this->aplicarEncabezadoExcel($sheet, 'A' . $fila . ':E' . $fila);

        $fila++;

        foreach ($consolidado as $estado) {
            $sheet->setCellValue(
                'A' . $fila,
                $meses[$estado->mes] ?? $estado->mes
            );

            $sheet->setCellValue('B' . $fila, $estado->saldo_inicial);
            $sheet->setCellValue('C' . $fila, $estado->total_ingresos);
            $sheet->setCellValue('D' . $fila, $estado->total_egresos);
            $sheet->setCellValue('E' . $fila, $estado->saldo_final);

            $fila++;
        }

        $this->formatearMonedaExcel(
            $sheet,
            'B9:E' . max($fila - 1, 9)
        );

        $sheet->freezePane('A9');
        $this->ajustarColumnasExcel($sheet, 'A', 'H');

        /*
        |--------------------------------------------------------------------------
        | HOJA 2 - CONSOLIDADO DEL PERIODO
        |--------------------------------------------------------------------------
        */

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Consolidado');

        $sheet->setCellValue('A1', 'SIGEFIV');
        $sheet->setCellValue('A2', 'CONSOLIDADO DEL PERÍODO');
        $sheet->setCellValue(
            'A3',
            'Año ' . $anio . ' - Hasta ' . $meses[$mesSeleccionado]
        );

        $sheet->mergeCells('A1:H1');
        $sheet->mergeCells('A2:H2');
        $sheet->mergeCells('A3:H3');

        $this->aplicarTituloExcel($sheet, 'A1:H1', 18);
        $this->aplicarSubtituloExcel($sheet, 'A2:H2', 14);
        $this->aplicarTextoInformativoExcel($sheet, 'A3:H3');

        $fila = 5;

        $encabezados = [
            'Folio',
            'Mes',
            'Saldo anterior',
            'Ingresos',
            'Disponible',
            'Egresos',
            'Saldo caja',
            'Año',
        ];

        foreach ($encabezados as $indice => $encabezado) {
            $columna = chr(65 + $indice);
            $sheet->setCellValue(
                $columna . $fila,
                $encabezado
            );
        }

        $this->aplicarEncabezadoExcel($sheet, 'A5:H5');

        $fila++;

        foreach ($consolidado as $estado) {

            $periodo = Periodo::where('anio', $anio)
                ->where('mes', $estado->mes)
                ->first();

            $disponible =
                $estado->saldo_inicial +
                $estado->total_ingresos;

            $sheet->setCellValue(
                'A' . $fila,
                $periodo?->folio ?? ''
            );

            $sheet->setCellValue(
                'B' . $fila,
                $meses[$estado->mes] ?? $estado->mes
            );

            $sheet->setCellValue(
                'C' . $fila,
                $estado->saldo_inicial
            );

            $sheet->setCellValue(
                'D' . $fila,
                $estado->total_ingresos
            );

            $sheet->setCellValue(
                'E' . $fila,
                $disponible
            );

            $sheet->setCellValue(
                'F' . $fila,
                $estado->total_egresos
            );

            $sheet->setCellValue(
                'G' . $fila,
                $estado->saldo_final
            );

            $sheet->setCellValue(
                'H' . $fila,
                $anio
            );

            $fila++;
        }

        if ($fila > 6) {
            $this->formatearMonedaExcel(
                $sheet,
                'C6:G' . ($fila - 1)
            );
        }

        $sheet->freezePane('A6');
        $this->ajustarColumnasExcel($sheet, 'A', 'H');

        /*
        |--------------------------------------------------------------------------
        | HOJAS MENSUALES
        |--------------------------------------------------------------------------
        */

        for ($mes = 1; $mes <= $mesSeleccionado; $mes++) {

            $nombreMes = $meses[$mes];

            $nombreHoja = substr(
                str_replace(
                    ['/', '\\', '?', '*', '[', ']', ':'],
                    '',
                    $nombreMes
                ),
                0,
                31
            );

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($nombreHoja);

            $movimientosMes = ReporteService::consultar(
                'estado',
                $anio,
                $mes
            );

            $ingresosMes = $movimientosMes
                ->where('tipo', 'Ingreso')
                ->sum('monto');

            $egresosMes = $movimientosMes
                ->where('tipo', 'Egreso')
                ->sum('monto');

            $estadoMes = collect($consolidado)
                ->firstWhere('mes', $mes);

            $sheet->setCellValue('A1', 'SIGEFIV');
            $sheet->setCellValue(
                'A2',
                strtoupper($nombreMes) . ' ' . $anio
            );

            $sheet->setCellValue(
                'A3',
                'Ingresos:'
            );

            $sheet->setCellValue(
                'B3',
                $ingresosMes
            );

            $sheet->setCellValue(
                'C3',
                'Egresos:'
            );

            $sheet->setCellValue(
                'D3',
                $egresosMes
            );

            $sheet->setCellValue(
                'E3',
                'Saldo final:'
            );

            $sheet->setCellValue(
                'F3',
                $estadoMes?->saldo_final ?? 0
            );

            $sheet->mergeCells('A1:F1');
            $sheet->mergeCells('A2:F2');

            $this->aplicarTituloExcel($sheet, 'A1:F1', 18);
            $this->aplicarSubtituloExcel($sheet, 'A2:F2', 14);
            $this->aplicarEncabezadoExcel($sheet, 'A3:F3');

            $fila = 5;

            $encabezados = [
                'Fecha',
                'N.º Recibo',
                'Tipo',
                'Categoría',
                'Concepto',
                'Persona',
                'Monto',
                'Observaciones',
                'Saldo',
            ];

            foreach ($encabezados as $indice => $encabezado) {
                $columna = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                    $indice + 1
                );

                $sheet->setCellValue(
                    $columna . $fila,
                    $encabezado
                );
            }

            $this->aplicarEncabezadoExcel(
                $sheet,
                'A' . $fila . ':I' . $fila
            );

            $fila++;

            foreach ($movimientosMes as $movimiento) {

                $sheet->setCellValue(
                    'A' . $fila,
                    $movimiento->fecha?->format('d/m/Y')
                );

                $sheet->setCellValue(
                    'B' . $fila,
                    $movimiento->numero
                );

                $sheet->setCellValue(
                    'C' . $fila,
                    $movimiento->tipo
                );

                $sheet->setCellValue(
                    'D' . $fila,
                    $movimiento->categoria?->nombre ?? ''
                );

                $sheet->setCellValue(
                    'E' . $fila,
                    $movimiento->concepto
                );

                $sheet->setCellValue(
                    'F' . $fila,
                    $movimiento->persona?->nombre ?? ''
                );

                $sheet->setCellValue(
                    'G' . $fila,
                    $movimiento->monto
                );

                $sheet->setCellValue(
                    'H' . $fila,
                    $movimiento->observaciones
                );

                $sheet->setCellValue(
                    'I' . $fila,
                    $movimiento->saldo
                );

                $fila++;
            }

            if ($fila > 6) {
                $this->formatearMonedaExcel(
                    $sheet,
                    'G6:G' . ($fila - 1)
                );

                $this->formatearMonedaExcel(
                    $sheet,
                    'I6:I' . ($fila - 1)
                );
            }

            $filaTotal = $fila + 1;

            $sheet->setCellValue(
                'F' . $filaTotal,
                'TOTAL INGRESOS'
            );

            $sheet->setCellValue(
                'G' . $filaTotal,
                $ingresosMes
            );

            $sheet->setCellValue(
                'F' . ($filaTotal + 1),
                'TOTAL EGRESOS'
            );

            $sheet->setCellValue(
                'G' . ($filaTotal + 1),
                $egresosMes
            );

            $sheet->setCellValue(
                'F' . ($filaTotal + 2),
                'SALDO FINAL'
            );

            $sheet->setCellValue(
                'G' . ($filaTotal + 2),
                $estadoMes?->saldo_final ?? 0
            );

            $this->aplicarEncabezadoExcel(
                $sheet,
                'F' . $filaTotal . ':G' . ($filaTotal + 2)
            );

            $this->formatearMonedaExcel(
                $sheet,
                'G' . $filaTotal . ':G' . ($filaTotal + 2)
            );

            $sheet->freezePane('A6');
            $this->ajustarColumnasExcel($sheet, 'A', 'I');
        }

        /*
        |--------------------------------------------------------------------------
        | Generación
        |--------------------------------------------------------------------------
        */

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx(
            $spreadsheet
        );

        $nombre = 'SIGEFIV_' .
            $anio .
            '_hasta_' .
            $meses[$mesSeleccionado] .
            '.xlsx';

        return response()->streamDownload(
            function () use ($writer) {
                $writer->save('php://output');
            },
            $nombre,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /**
     * Aplica estilo al título principal.
     */
    private function aplicarTituloExcel(
        $sheet,
        string $rango,
        int $tamano = 18
    ): void {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => $tamano,
            ],
            'alignment' => [
                'horizontal' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);
    }

    /**
     * Aplica estilo a subtítulos.
     */
    private function aplicarSubtituloExcel(
        $sheet,
        string $rango,
        int $tamano = 14
    ): void {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => $tamano,
            ],
            'alignment' => [
                'horizontal' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    /**
     * Aplica estilo a texto informativo.
     */
    private function aplicarTextoInformativoExcel(
        $sheet,
        string $rango
    ): void {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => [
                'italic' => true,
            ],
            'alignment' => [
                'horizontal' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    /**
     * Aplica estilo a encabezados.
     */
    private function aplicarEncabezadoExcel(
        $sheet,
        string $rango
    ): void {
        $sheet->getStyle($rango)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' =>
                        \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    /**
     * Formato monetario en soles.
     */
    private function formatearMonedaExcel(
        $sheet,
        string $rango
    ): void {
        $sheet->getStyle($rango)
            ->getNumberFormat()
            ->setFormatCode('"S/" #,##0.00');
    }

    /**
     * Ajusta automáticamente las columnas.
     */
    private function ajustarColumnasExcel(
        $sheet,
        string $desde,
        string $hasta
    ): void {
        $inicio = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $desde
        );

        $fin = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString(
            $hasta
        );

        for ($columna = $inicio; $columna <= $fin; $columna++) {
            $letra =
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
                    $columna
                );

            $sheet
                ->getColumnDimension($letra)
                ->setAutoSize(true);
        }
    }
}