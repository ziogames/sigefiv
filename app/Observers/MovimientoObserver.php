<?php

namespace App\Observers;

use App\Models\Movimiento;
use App\Services\BitacoraService;

class MovimientoObserver
{
    /**
     * Se ejecuta automáticamente al CREAR un movimiento (Ingreso / Egreso).
     */
    public function created(Movimiento $movimiento): void
    {
        $tipo = ucfirst(strtolower($movimiento->tipo ?? 'Movimiento'));
        $monto = number_format((float) ($movimiento->monto ?? 0), 2);
        $concepto = $movimiento->concepto ?? 'Sin concepto';

        $descripcion = "Registró un {$tipo} por S/ {$monto} (Concepto: \"{$concepto}\")";

        BitacoraService::registrar(
            modulo: 'Movimientos',
            accion: 'Creación',
            descripcion: $descripcion
        );
    }

    /**
     * Se ejecuta automáticamente al EDITAR un movimiento.
     */
    public function updated(Movimiento $movimiento): void
    {
        $tipo = ucfirst(strtolower($movimiento->tipo ?? 'Movimiento'));
        $cambios = [];

        if ($movimiento->wasChanged('monto')) {
            $montoViejo = number_format((float) $movimiento->getOriginal('monto'), 2);
            $montoNuevo = number_format((float) $movimiento->monto, 2);
            $cambios[] = "monto de S/ {$montoViejo} a S/ {$montoNuevo}";
        }

        if ($movimiento->wasChanged('concepto')) {
            $cambios[] = "concepto a \"{$movimiento->concepto}\"";
        }

        if ($movimiento->wasChanged('fecha')) {
            $cambios[] = "fecha a {$movimiento->fecha}";
        }

        if ($movimiento->wasChanged('categoria_id') || $movimiento->wasChanged('categoria')) {
            $cambios[] = "categoría";
        }

        $detalle = empty($cambios) ? 'datos generales' : implode(', ', $cambios);
        $descripcion = "Editó {$tipo} #{$movimiento->id}: modificó {$detalle}";

        BitacoraService::registrar(
            modulo: 'Movimientos',
            accion: 'Edición',
            descripcion: $descripcion
        );
    }

    /**
     * Se ejecuta automáticamente al ELIMINAR un movimiento.
     */
    public function deleted(Movimiento $movimiento): void
    {
        $tipo = ucfirst(strtolower($movimiento->tipo ?? 'Movimiento'));
        $monto = number_format((float) ($movimiento->monto ?? 0), 2);
        $concepto = $movimiento->concepto ?? 'Sin concepto';

        $descripcion = "Eliminó el {$tipo} #{$movimiento->id} de S/ {$monto} (Concepto: \"{$concepto}\")";

        BitacoraService::registrar(
            modulo: 'Movimientos',
            accion: 'Eliminación',
            descripcion: $descripcion
        );
    }
}