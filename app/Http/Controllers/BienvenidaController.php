<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class BienvenidaController extends Controller
{
    /**
     * Marcar la pantalla de bienvenida como vista
     * y continuar hacia el dashboard.
     */
    public function completar(): RedirectResponse
    {
        $usuario = Auth::user();

        if ($usuario) {

            $usuario->update([
                'bienvenida_vista' => true,
            ]);

        }

        return redirect()->route('dashboard');
    }
}
