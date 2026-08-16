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


Route::get('/', function () {
    return redirect()->route('login');
});


Route::middleware(['auth'])->group(function () {


    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard');


    Route::resource('usuarios', UsuarioController::class)
        ->middleware('permission:usuarios.index');


    Route::resource('roles', RolController::class)
        ->middleware('permission:roles.index');


    Route::get('/mi-cuenta', [PerfilController::class, 'index'])
        ->name('perfil.index');


    Route::put('/mi-cuenta', [PerfilController::class, 'update'])
        ->name('perfil.update');


    Route::put('/mi-cuenta/password', [PerfilController::class, 'password'])
        ->name('perfil.password');


    Route::post(
        '/mi-cuenta/foto',
        [PerfilController::class, 'foto']
    )->name('perfil.foto');


    Route::get(
        '/bitacora',
        [BitacoraController::class, 'index']
    )
        ->name('bitacora.index')
        ->middleware('permission:bitacora.index');


    Route::resource(
        'configuracion',
        ConfiguracionController::class
    )->middleware('permission:configuracion');


    Route::resource(
        'categorias',
        CategoriaController::class
    )->middleware('permission:categorias.index');


    Route::resource(
        'movimientos',
        MovimientoController::class
    )->middleware('permission:movimientos.index');


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


    Route::post(
        '/periodos/{periodo}/cerrar',
        [PeriodoController::class, 'cerrar']
    )
        ->name('periodos.cerrar')
        ->middleware('permission:dashboard');


    Route::get(
        '/caja',
        [CajaController::class, 'index']
    )
        ->name('caja.index')
        ->middleware('permission:caja.index');


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
    |
    | Aquí llegará la pregunta escrita por el usuario.
    |
    */

    Route::post(
        '/consulta-inteligente',
        [ConsultaInteligenteController::class, 'consultar']
    )
        ->name('consulta.inteligente')
        ->middleware('auth');

        
          Route::post('/push-subscriptions', [
        PushSubscriptionController::class,
        'store'
    ])->name('push-subscriptions.store');

    Route::delete('/push-subscriptions', [
        PushSubscriptionController::class,
        'destroy'
    ])->name('push-subscriptions.destroy');


        Route::get('/push-test', function () {
    $suscripcion = \App\Models\PushSubscription::latest()->first();

    if (!$suscripcion) {
        return response()->json([
            'success' => false,
            'message' => 'No existe ninguna suscripción Push.',
        ], 404);
    }

    $resultado = app(\App\Services\PushNotificationService::class)->enviar(
        $suscripcion,
        'SIGEFIV',
        'Esta es una notificación de prueba.',
        '/dashboard'
    );

    return response()->json([
        'success' => $resultado,
        'message' => $resultado
            ? 'Notificación enviada correctamente.'
            : 'No se pudo enviar la notificación.',
        'subscription_id' => $suscripcion->id,
    ]);
})->name('push.test');

});


require __DIR__.'/auth.php';