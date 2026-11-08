<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConfiguracionController extends Controller
{
    /**
     * Obtener los datos actuales de la configuración global.
     */
    public function show()
    {
        $configuracion = Configuracion::firstOrCreate(
            ['id' => 1],
            [
                'nombre_sistema'  => 'SIGEFIV',
                'organizacion'    => 'Grupo Vecinal',
                'moneda'          => 'Sol Peruano',
                'simbolo_moneda'  => 'S/',
                'decimales'       => 2,
                'zona_horaria'    => 'America/Lima',
                'color_principal' => '#15803D',
            ]
        );

        return response()->json([
            'success' => true,
            'data'    => $configuracion,
        ]);
    }

    public function update(Request $request)
{
    $reglas = [
        'nombre_sistema'        => 'nullable|string|max:100',
        'organizacion'          => 'nullable|string|max:150',
        'direccion'             => 'nullable|string|max:255',
        'telefono'              => 'nullable|string|max:50',
        'correo'                => 'nullable|email',

        'presidente'            => 'nullable|string|max:150',
        'tesorero'              => 'nullable|string|max:150',
        'secretario'            => 'nullable|string|max:150',
        'ruc'                   => 'nullable|string|max:20',
        'web'                   => 'nullable|string|max:255',
        'facebook'              => 'nullable|string|max:255',
        'instagram'             => 'nullable|string|max:255',

        'color_principal'       => 'nullable|string',
        'moneda'                => 'nullable|string|max:50',
        'simbolo_moneda'        => 'nullable|string|max:10',
        'decimales'             => 'nullable|integer|min:0|max:4',
        'zona_horaria'          => 'nullable|string',

        'bitacora_activa'       => 'nullable|boolean',
        'sesion_unica'          => 'nullable|boolean',
        'bloqueo_intentos'      => 'nullable|boolean',
        'expirar_password'      => 'nullable|boolean',

        'intentos_login'        => 'nullable|integer|min:1|max:10',
        'tiempo_sesion'         => 'nullable|integer|min:1|max:480',
        'longitud_password'     => 'nullable|integer|min:4|max:30',

        'saldo_apertura'        => 'nullable|numeric|min:0',
        'contabilidad_iniciada' => 'nullable|boolean',
    ];

    // Solo validar como imagen si realmente se está enviando un archivo nuevo
    if ($request->hasFile('logo')) {
        $reglas['logo'] = 'image|max:2048';
    }

    if ($request->hasFile('favicon')) {
        $reglas['favicon'] = 'image|max:1024';
    }

    if ($request->hasFile('imagen_login')) {
        $reglas['imagen_login'] = 'image|max:4096';
    }

    $datos = $request->validate($reglas);

    $configuracion = Configuracion::first();

    // Procesar carga de archivos binarios si existen
    if ($request->hasFile('logo')) {
        if ($configuracion?->logo) {
            Storage::disk('public')->delete($configuracion->logo);
        }
        $datos['logo'] = $request->file('logo')->store('configuracion', 'public');
    }

    if ($request->hasFile('favicon')) {
        if ($configuracion?->favicon) {
            Storage::disk('public')->delete($configuracion->favicon);
        }
        $datos['favicon'] = $request->file('favicon')->store('configuracion', 'public');
    }

    if ($request->hasFile('imagen_login')) {
        if ($configuracion?->imagen_login) {
            Storage::disk('public')->delete($configuracion->imagen_login);
        }
        $datos['imagen_login'] = $request->file('imagen_login')->store('configuracion', 'public');
    }

    // Evitar sobreescribir los valores contables si ya fue inicializada
    if ($configuracion && $configuracion->contabilidad_iniciada) {
        unset(
            $datos['anio_inicio'],
            $datos['mes_inicio'],
            $datos['saldo_apertura']
        );
    }

    // Evitar sobreescribir con null campos de imagen si solo se enviaron datos de texto
    if (!$request->hasFile('logo')) {
        unset($datos['logo']);
    }
    if (!$request->hasFile('favicon')) {
        unset($datos['favicon']);
    }
    if (!$request->hasFile('imagen_login')) {
        unset($datos['imagen_login']);
    }

    $configuracion = Configuracion::updateOrCreate(
        ['id' => 1],
        array_filter($datos, fn($value) => !is_null($value))
    );

    return response()->json([
        'success' => true,
        'message' => 'Configuración actualizada correctamente.',
        'data'    => $configuracion->fresh(),
    ]);
}
}