<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Movimiento;
use App\Services\BitacoraService;
use App\Services\NotificacionService;
use App\Services\PeriodoService;
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
        $periodo = PeriodoService::obtenerPorFecha($datos['fecha']);
        PeriodoService::validarPeriodoAbierto($periodo);

        // Asociar período
        $datos['periodo_id'] = $periodo->id;

        // Obtener tipo desde la categoría
        $categoria = Categoria::findOrFail($datos['categoria_id']);
        $datos['tipo'] = $categoria->tipo;

        // Subir comprobante
        if (
            isset($datos['comprobante']) &&
            $datos['comprobante'] instanceof UploadedFile
        ) {
            $datos['comprobante'] = $datos['comprobante']->store(
                'movimientos',
                'public'
            );
        }

        // Guardar movimiento
        $movimiento = Movimiento::create($datos);

        // Actualizar totales del período
        PeriodoService::actualizarTotales($periodo);

        // ============================================================
        // 🔍 REGISTRO EN BITÁCORA DEL SISTEMA
        // ============================================================
        $tipoStr = ucfirst(strtolower($movimiento->tipo ?? 'Movimiento'));
        $montoStr = number_format((float) ($movimiento->monto ?? 0), 2);
        $conceptoStr = $movimiento->concepto ?? 'Sin concepto';

        BitacoraService::registrar(
            modulo: 'Movimientos',
            accion: 'Crear',
            descripcion: "Registró un {$tipoStr} por S/ {$montoStr} (Concepto: \"{$conceptoStr}\")",
            userId: $movimiento->user_id
        );

        // ============================================================
        // NOTIFICACIÓN AUTOMÁTICA
        // ============================================================
        $tipoNotificacion = strtolower(trim($movimiento->tipo));

        if ($tipoNotificacion === 'ingreso' || $tipoNotificacion === 'egreso') {
            $notificacionService = app(NotificacionService::class);
            $nombreTipo = $tipoNotificacion === 'ingreso' ? 'Ingreso' : 'Egreso';
            $titulo = 'Nuevo ' . $nombreTipo;
            $mensaje = 'Se registró un nuevo ' . strtolower($nombreTipo) .
                       ' por S/ ' . number_format((float) $movimiento->monto, 2, '.', '');

            $notificacionService->enviarATodos(
                titulo: $titulo,
                mensaje: $mensaje,
                tipo: $tipoNotificacion,
                data: [
                    'movimiento_id' => (string) $movimiento->id,
                    'periodo_id'    => (string) $movimiento->periodo_id,
                ]
            );
        }

        return $movimiento;
    }

    /**
     * Actualizar movimiento
     */
    public static function actualizar(
        Movimiento $movimiento,
        array $datos
    ): Movimiento {
        // Guardar valores previos para el detalle de la bitácora
        $montoAnterior = (float) $movimiento->monto;
        $conceptoAnterior = $movimiento->concepto;

        // Categoría
        $categoria = Categoria::findOrFail($datos['categoria_id']);
        $datos['tipo'] = $categoria->tipo;

        // Períodos
        $periodoAnterior = $movimiento->periodo;
        $periodoNuevo = PeriodoService::obtenerPorFecha($datos['fecha']);
        PeriodoService::validarPeriodoAbierto($periodoNuevo);

        $datos['periodo_id'] = $periodoNuevo->id;

        // Subir comprobante
        if (
            isset($datos['comprobante']) &&
            $datos['comprobante'] instanceof UploadedFile
        ) {
            $datos['comprobante'] = $datos['comprobante']->store(
                'movimientos',
                'public'
            );
        }

        // Actualizar movimiento
        $movimiento->update($datos);

        // Recalcular períodos
        if ($periodoAnterior) {
            PeriodoService::actualizarTotales($periodoAnterior);
        }
        PeriodoService::actualizarTotales($periodoNuevo);

        // ============================================================
        // 🔍 REGISTRO EN BITÁCORA DEL SISTEMA
        // ============================================================
        $tipoStr = ucfirst(strtolower($movimiento->tipo ?? 'Movimiento'));
        $cambios = [];
        if ((float) $datos['monto'] !== $montoAnterior) {
            $cambios[] = 'monto de S/ ' . number_format($montoAnterior, 2) . ' a S/ ' . number_format((float) $datos['monto'], 2);
        }
        if (($datos['concepto'] ?? '') !== $conceptoAnterior) {
            $cambios[] = 'concepto a "' . ($datos['concepto'] ?? '') . '"';
        }
        $detalle = empty($cambios) ? 'datos generales' : implode(', ', $cambios);

        BitacoraService::registrar(
            modulo: 'Movimientos',
            accion: 'Editar',
            descripcion: "Editó {$tipoStr} #{$movimiento->id}: modificó {$detalle}",
            userId: auth()->id() ?? $movimiento->user_id
        );

        return $movimiento->fresh();
    }

    /**
     * Eliminar movimiento
     */
    public static function eliminar(Movimiento $movimiento): void
    {
        $periodo = $movimiento->periodo;
        PeriodoService::validarPeriodoAbierto($periodo);

        // Guardar datos antes de eliminar
        $tipoStr = ucfirst(strtolower($movimiento->tipo ?? 'Movimiento'));
        $montoStr = number_format((float) ($movimiento->monto ?? 0), 2);
        $conceptoStr = $movimiento->concepto ?? 'Sin concepto';
        $idMovimiento = $movimiento->id;
        $userId = auth()->id() ?? $movimiento->user_id;

        // Eliminar movimiento
        $movimiento->delete();

        // Recalcular totales
        if ($periodo) {
            PeriodoService::actualizarTotales($periodo);
        }

        // ============================================================
        // 🔍 REGISTRO EN BITÁCORA DEL SISTEMA
        // ============================================================
        BitacoraService::registrar(
            modulo: 'Movimientos',
            accion: 'Eliminar',
            descripcion: "Eliminó el {$tipoStr} #{$idMovimiento} de S/ {$montoStr} (Concepto: \"{$conceptoStr}\")",
            userId: $userId
        );
    }
}