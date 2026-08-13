<?php

namespace App\Http\Controllers;

use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $configuracion = Configuracion::firstOrCreate(
            ['id' => 1],
            [
                'nombre_sistema'   => 'SIGEFIV',
                'organizacion'     => 'Grupo Vecinal',
                'moneda'           => 'Sol Peruano',
                'simbolo_moneda'   => 'S/',
                'decimales'        => 2,
                'zona_horaria'     => 'America/Lima',
                'color_principal'  => '#321fdb',
            ]
        );

        return view('configuracion.index', compact('configuracion'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'bitacora_activa'  => $request->has('bitacora_activa'),
            'sesion_unica'     => $request->has('sesion_unica'),
            'bloqueo_intentos' => $request->has('bloqueo_intentos'),
            'expirar_password' => $request->has('expirar_password'),
        ]);

        $datos = $request->validate([

            'nombre_sistema' => 'required|max:100',
            'organizacion'   => 'required|max:150',
            'direccion'      => 'nullable|max:255',
            'telefono'       => 'nullable|max:50',
            'correo'         => 'nullable|email',

            'presidente' => 'nullable|max:150',
            'tesorero'   => 'nullable|max:150',
            'secretario' => 'nullable|max:150',
            'ruc'        => 'nullable|max:20',
            'web'        => 'nullable|max:255',
            'facebook'   => 'nullable|max:255',
            'instagram'  => 'nullable|max:255',

            'logo' => 'nullable|image|max:2048',

            'favicon' => 'nullable|image|max:1024',

            'imagen_login' => 'nullable|image|max:4096',

            'color_principal' => 'required',

            'moneda'         => 'required|max:50',
            'simbolo_moneda' => 'required|max:10',
            'decimales'      => 'required|integer|min:0|max:4',
            'zona_horaria'   => 'required',

            'bitacora_activa'   => 'boolean',
            'sesion_unica'      => 'boolean',
            'bloqueo_intentos'  => 'boolean',
            'expirar_password'  => 'boolean',

            'intentos_login'    => 'required|integer|min:3|max:10',
            'tiempo_sesion'     => 'required|integer|min:5|max:240',
            'longitud_password' => 'required|integer|min:6|max:30',


            'anio_inicio' => 'nullable|integer|min:2020|max:2100',

            'mes_inicio' => 'nullable|integer|min:1|max:12',

            'saldo_apertura' => 'nullable|numeric|min:0',

            'contabilidad_iniciada' => 'boolean',

        ]);

        $configuracion = Configuracion::first();

        if ($request->hasFile('logo')) {

            if ($configuracion?->logo) {
                Storage::disk('public')->delete($configuracion->logo);
            }

            $datos['logo'] = $request
                ->file('logo')
                ->store('configuracion', 'public');
        }

        if ($request->hasFile('favicon')) {

            if ($configuracion?->favicon) {
                Storage::disk('public')->delete($configuracion->favicon);
            }

            $datos['favicon'] = $request
                ->file('favicon')
                ->store('configuracion', 'public');
        }

        if ($request->hasFile('imagen_login')) {

            if ($configuracion?->imagen_login) {
                Storage::disk('public')->delete($configuracion->imagen_login);
            }

            $datos['imagen_login'] = $request
                ->file('imagen_login')
                ->store('configuracion', 'public');
        }

        $configuracion = Configuracion::first();

if (

    $configuracion &&
    $configuracion->contabilidad_iniciada

) {

    unset(

        $datos['anio_inicio'],

        $datos['mes_inicio'],

        $datos['saldo_apertura']

    );

}

$configuracion = Configuracion::updateOrCreate(

    ['id'=>1],

    $datos

);

        if (
            !$configuracion->contabilidad_iniciada &&
            $configuracion->saldo_apertura > 0
        ) {

            \App\Services\PeriodoService::crearPrimerPeriodo(
                $configuracion
            );

            $configuracion->update([

                'contabilidad_iniciada' => true,

            ]);

        }

        return back()->with(
            'success',
            'Configuración guardada correctamente.'
        );
    }
}