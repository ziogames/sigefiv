<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Movimiento;
use Illuminate\Http\UploadedFile;

class MovimientoService
{
    /**
     * Guardar movimiento
     */
    public static function guardar(array $datos): Movimiento
    {
        // Número automático
        $datos['numero'] = Movimiento::siguienteNumero();

        // Estado inicial
        $datos['estado'] = 'Registrado';

        // Obtener o crear el período
       $periodo = PeriodoService::obtenerPorFecha(
    $datos['fecha']
);

PeriodoService::validarPeriodoAbierto($periodo);

        // Asociar período
        $datos['periodo_id'] = $periodo->id;

        // Obtener tipo desde la categoría
        $categoria = Categoria::findOrFail(
            $datos['categoria_id']
        );

        $datos['tipo'] = $categoria->tipo;

        // Subir comprobante
        if (
            isset($datos['comprobante']) &&
            $datos['comprobante'] instanceof UploadedFile
        ) {

            $datos['comprobante'] = $datos['comprobante']
                ->store(
                    'movimientos',
                    'public'
                );

        }

        // Guardar movimiento
        $movimiento = Movimiento::create($datos);

        // Actualizar totales del período
        PeriodoService::actualizarTotales(
            $periodo
        );

        return $movimiento;
    }

    /**
     * Actualizar movimiento
     */
    public static function actualizar(
        Movimiento $movimiento,
        array $datos
    ): Movimiento {

        // Categoría
        $categoria = Categoria::findOrFail(
            $datos['categoria_id']
        );

        $datos['tipo'] = $categoria->tipo;

        // Períodos
        $periodoAnterior = $movimiento->periodo;

        

        $periodoNuevo = PeriodoService::obtenerPorFecha(
            $datos['fecha']
        );
        PeriodoService::validarPeriodoAbierto($periodoNuevo);
        $datos['periodo_id'] = $periodoNuevo->id;

        // Subir comprobante
        if (
            isset($datos['comprobante']) &&
            $datos['comprobante'] instanceof UploadedFile
        ) {

            $datos['comprobante'] = $datos['comprobante']
                ->store(
                    'movimientos',
                    'public'
                );

        }

        // Actualizar movimiento
        $movimiento->update($datos);

        // Recalcular período anterior
        if ($periodoAnterior) {

            PeriodoService::actualizarTotales(
                $periodoAnterior
            );

        }

        // Recalcular período nuevo
        PeriodoService::actualizarTotales(
            $periodoNuevo
        );

        return $movimiento->fresh();
    }
    /**
 * Eliminar movimiento
 */
public static function eliminar(Movimiento $movimiento): void
{
    // Obtener el período del movimiento
    $periodo = $movimiento->periodo;

    // Verificar que el período esté abierto
    PeriodoService::validarPeriodoAbierto($periodo);

    // Eliminar movimiento
    $movimiento->delete();

    // Recalcular totales del período
    if ($periodo) {
        PeriodoService::actualizarTotales($periodo);
    }
}
}