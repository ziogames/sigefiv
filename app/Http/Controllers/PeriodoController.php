<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use App\Services\PeriodoService;

class PeriodoController extends Controller
{
    /**
     * Cierra un período contable.
     */
    public function cerrar(Periodo $periodo)
    {
        if ($periodo->estaCerrado()) {

            return redirect()
                ->route('dashboard')
                ->with(
                    'error',
                    'El período ya se encuentra cerrado.'
                );

        }

       PeriodoService::cerrarPeriodo($periodo);

        return redirect()
            ->route('dashboard')
            ->with(
                'success',
                'El período fue cerrado correctamente.'
            );
    }
}