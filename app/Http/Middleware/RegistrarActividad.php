<?php

namespace App\Http\Middleware;

use App\Services\ActividadService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarActividad
{
    /**
     * Registrar la actividad de navegación del usuario.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | Ejecutar primero la petición
        |--------------------------------------------------------------------------
        */

        $response = $next($request);


        /*
        |--------------------------------------------------------------------------
        | Solo usuarios autenticados
        |--------------------------------------------------------------------------
        */

        if (!auth()->check()) {

            return $response;

        }


        /*
        |--------------------------------------------------------------------------
        | Solo peticiones GET
        |--------------------------------------------------------------------------
        |
        | Las acciones POST, PUT, PATCH y DELETE pertenecen
        | a la auditoría de Bitácora.
        |
        */

        if (!$request->isMethod('GET')) {

            return $response;

        }


        /*
        |--------------------------------------------------------------------------
        | No registrar AJAX ni respuestas JSON
        |--------------------------------------------------------------------------
        */

        if (
            $request->ajax() ||
            $request->expectsJson()
        ) {

            return $response;

        }


        /*
        |--------------------------------------------------------------------------
        | Obtener nombre de la ruta
        |--------------------------------------------------------------------------
        */

        $routeName = $request->route()?->getName();


        if (!$routeName) {

            return $response;

        }


        /*
        |--------------------------------------------------------------------------
        | Determinar módulo
        |--------------------------------------------------------------------------
        */

        $modulo = $this->obtenerModulo(
            $routeName
        );


        /*
        |--------------------------------------------------------------------------
        | Si no es un acceso principal a un módulo,
        | no registrar actividad.
        |--------------------------------------------------------------------------
        */

        if (!$modulo) {

            return $response;

        }


        /*
        |--------------------------------------------------------------------------
        | Registrar actividad
        |--------------------------------------------------------------------------
        */

        ActividadService::registrar(

            auth()->user(),

            $modulo,

            'Acceso',

            $request->path()

        );


        return $response;
    }


    /**
     * Determinar el módulo a partir del nombre de la ruta.
     */
    private function obtenerModulo(
        string $routeName
    ): ?string {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        if ($routeName === 'dashboard') {

            return 'Dashboard';

        }


        /*
        |--------------------------------------------------------------------------
        | Rutas principales de recursos
        |--------------------------------------------------------------------------
        */

        $modulos = [

            'usuarios.index' => 'Usuarios',

            'roles.index' => 'Roles',

            'categorias.index' => 'Categorías',

            'movimientos.index' => 'Movimientos',

            'asambleas.index' => 'Asambleas',

        ];


        if (isset($modulos[$routeName])) {

            return $modulos[$routeName];

        }


        /*
        |--------------------------------------------------------------------------
        | Rutas principales individuales
        |--------------------------------------------------------------------------
        */

        $rutas = [

            'perfil.index' => 'Mi Cuenta',

            'bitacora.index' => 'Bitácora',

            'configuracion.index' => 'Configuración',

            'reportes.index' => 'Reportes',

            'caja.index' => 'Caja',

            'sigi.index' => 'SIGI',

            'sigi.estatutos' => 'SIGI',

        ];


        if (isset($rutas[$routeName])) {

            return $rutas[$routeName];

        }


        /*
        |--------------------------------------------------------------------------
        | Periodos
        |--------------------------------------------------------------------------
        |
        | Actualmente no existe una ruta periodos.index
        | en el routes/web.php proporcionado.
        |
        | No registramos periodos.cerrar porque es POST.
        |
        */


        return null;
    }
}