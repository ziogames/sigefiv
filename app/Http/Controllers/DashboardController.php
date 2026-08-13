<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index()
    {
        return view(
            'dashboard.index',
            DashboardService::obtenerDatos()
        );
    }

    public function datos(Request $request)
    {
        return response()->json(

            DashboardService::obtenerDatosAjax(

                (int) $request->get('anio'),

                (int) $request->get('mes')

            )

        );
    }
}