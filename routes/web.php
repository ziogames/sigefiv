<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PeriodoController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\ConsultaInteligenteController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\AsambleaController;
use App\Http\Controllers\SigiMemoriaController;
use App\Http\Controllers\ActividadController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SigiVoiceController;
use App\Http\Middleware\CheckUserNotBlocked;


/*
|--------------------------------------------------------------------------
| INICIO
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});


/*
|--------------------------------------------------------------------------
| CITACIÓN PÚBLICA DE ASAMBLEA
|--------------------------------------------------------------------------
|
| Esta ruta NO requiere autenticación.
|
*/

Route::get(
    '/asambleas/{asamblea}/citacion',
    [AsambleaController::class, 'citacion']
)->name('asambleas.citacion');


/*
|--------------------------------------------------------------------------
| CUENTA PENDIENTE
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get(
        '/cuenta/pendiente',
        function () {
            return view('auth.pendiente');
        }
    )->name('cuenta.pendiente');

});


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Incluye el sistema de ZOE y el Chat Vecinal)
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'estado.usuario',
    CheckUserNotBlocked::class, // <-- ZOE bloquea automáticamente el acceso si el usuario está suspendido
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | BIENVENIDA DE SIGEFIV
    |--------------------------------------------------------------------------
    */

    Route::view(
        '/bienvenida',
        'bienvenida'
    )->name('bienvenida');

    Route::post(
        '/bienvenida/completar',
        [\App\Http\Controllers\BienvenidaController::class, 'completar']
    )->name('bienvenida.completar');

    /*
    |--------------------------------------------------------------------------
    | SIGI — ASISTENTE INTELIGENTE
    |--------------------------------------------------------------------------
    */

    Route::view(
        '/sigi',
        'sigi.index'
    )->name('sigi.index');

    Route::post(
        '/sigi/voz',
        [SigiVoiceController::class, 'speak']
    )->name('sigi.voz');

    Route::get(
        '/sigi/estatutos',
        function () {
            $ruta = storage_path(
                'documentos/estatutos-grupo-21.pdf'
            );

            if (!file_exists($ruta)) {
                abort(
                    404,
                    'El archivo de estatutos todavía no está disponible.'
                );
            }

            return response()->file($ruta);
        }
    )->name('sigi.estatutos');


    /*
    |--------------------------------------------------------------------------
    | CHAT VECINAL (Protegido por ZOE)
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/chat',
        [ChatController::class, 'index']
    )->name('chat.index');


    Route::post(
        '/chat',
        [ChatController::class, 'store']
    )->name('chat.store');


    Route::get(
        '/chat/nuevos',
        [ChatController::class, 'nuevos']
    )->name('chat.nuevos');


    Route::post(
        '/chat/presencia',
        [ChatController::class, 'presencia']
    )->name('chat.presencia');


    Route::post(
        '/chat/escribiendo',
        [ChatController::class, 'escribiendo']
    )->name('chat.escribiendo');


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )
        ->name('dashboard')
        ->middleware('permission:dashboard');


    /*
    |--------------------------------------------------------------------------
    | USUARIOS
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'usuarios',
        UsuarioController::class
    )
        ->middleware('permission:usuarios.index');


    Route::patch(
        '/usuarios/{usuario}/estado',
        [UsuarioController::class, 'cambiarEstado']
    )
        ->name('usuarios.estado')
        ->middleware('permission:usuarios.index');


    /*
    |--------------------------------------------------------------------------
    | ROLES
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'roles',
        RolController::class
    )
        ->middleware('permission:roles.index');


    /*
    |--------------------------------------------------------------------------
    | MI CUENTA
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/mi-cuenta',
        [PerfilController::class, 'index']
    )
        ->name('perfil.index');


    Route::put(
        '/mi-cuenta',
        [PerfilController::class, 'update']
    )
        ->name('perfil.update');


    Route::put(
        '/mi-cuenta/password',
        [PerfilController::class, 'password']
    )
        ->name('perfil.password');


    Route::post(
        '/mi-cuenta/foto',
        [PerfilController::class, 'foto']
    )
        ->name('perfil.foto');


    /*
    |--------------------------------------------------------------------------
    | BITÁCORA
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/bitacora',
        [BitacoraController::class, 'index']
    )
        ->name('bitacora.index')
        ->middleware('permission:bitacora.index');


    Route::get(
        '/actividad',
        [ActividadController::class, 'index']
    )
        ->middleware('permission:actividad.index')
        ->name('actividad.index');


    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'configuracion',
        ConfiguracionController::class
    )
        ->middleware('permission:configuracion');


    /*
    |--------------------------------------------------------------------------
    | CATEGORÍAS
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'categorias',
        CategoriaController::class
    )
        ->middleware('permission:categorias.index');


    /*
    |--------------------------------------------------------------------------
    | MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'movimientos',
        MovimientoController::class
    )
        ->middleware('permission:movimientos.index');


    /*
    |--------------------------------------------------------------------------
    | REPORTES
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reportes',
        [ReporteController::class, 'index']
    )
        ->name('reportes.index')
        ->middleware('permission:reportes.index');


    Route::get(
        '/reportes/pdf',
        [ReporteController::class, 'pdf']
    )
        ->name('reportes.pdf')
        ->middleware('permission:reportes.index');


    Route::get(
        '/dashboard/datos',
        [DashboardController::class, 'datos']
    )
        ->name('dashboard.datos')
        ->middleware('permission:dashboard');


    /*
    |--------------------------------------------------------------------------
    | PERIODOS
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/periodos/{periodo}/cerrar',
        [PeriodoController::class, 'cerrar']
    )
        ->name('periodos.cerrar')
        ->middleware('permission:dashboard');


    /*
    |--------------------------------------------------------------------------
    | CAJA
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/caja',
        [CajaController::class, 'index']
    )
        ->name('caja.index')
        ->middleware('permission:caja.index');


    /*
    |--------------------------------------------------------------------------
    | REPORTE EXCEL
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/reportes/excel',
        [ReporteController::class, 'excel']
    )
        ->name('reportes.excel')
        ->middleware('permission:reportes.index');


    /*
    |--------------------------------------------------------------------------
    | CONSULTA INTELIGENTE
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/consulta-inteligente',
        [ConsultaInteligenteController::class, 'consultar']
    )
        ->name('consulta.inteligente')
        ->middleware('auth');


    /*
    |--------------------------------------------------------------------------
    | MEMORIA DE SIGI
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/sigi/memoria',
        [SigiMemoriaController::class, 'index']
    )
        ->name('sigi.memoria.index');


    Route::delete(
        '/sigi/memoria/{memoria}',
        [SigiMemoriaController::class, 'destroy']
    )
        ->name('sigi.memoria.destroy');


    /*
    |--------------------------------------------------------------------------
    | NOTIFICACIONES PUSH
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/push-subscriptions',
        [
            PushSubscriptionController::class,
            'store'
        ]
    )
        ->name('push-subscriptions.store');


    Route::delete(
        '/push-subscriptions',
        [
            PushSubscriptionController::class,
            'destroy'
        ]
    )
        ->name('push-subscriptions.destroy');


    /*
    |--------------------------------------------------------------------------
    | PRUEBA DE NOTIFICACIÓN PUSH
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/push-test',
        function () {

            $suscripcion =
                \App\Models\PushSubscription::latest()->first();

            if (!$suscripcion) {
                return response()->json([
                    'success' => false,
                    'message' =>
                        'No existe ninguna suscripción Push.',
                ], 404);
            }

            $resultado =
                app(
                    \App\Services\PushNotificationService::class
                )->enviar(
                    $suscripcion,
                    'SIGEFIV',
                    'Esta es una notificación de prueba.',
                    '/dashboard'
                );

            return response()->json([
                'success' =>
                    $resultado,
                'message' =>
                    $resultado
                        ? 'Notificación enviada correctamente.'
                        : 'No se pudo enviar la notificación.',
                'subscription_id' =>
                    $suscripcion->id,
            ]);

        }
    )
        ->name('push.test');


    /*
    |--------------------------------------------------------------------------
    | ASAMBLEAS
    |--------------------------------------------------------------------------
    */

    Route::get(
        'asambleas/{asamblea}/imprimir',
        [
            AsambleaController::class,
            'imprimir'
        ]
    )
        ->name('asambleas.imprimir');


    Route::post(
        'asambleas/{asamblea}/enviar',
        [
            AsambleaController::class,
            'enviar'
        ]
    )
        ->name('asambleas.enviar');


    Route::resource(
        'asambleas',
        AsambleaController::class
    )
        ->middleware('permission:asambleas.index');


});


/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';