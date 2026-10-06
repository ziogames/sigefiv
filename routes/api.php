<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\PeriodoController;
use App\Http\Controllers\Api\ChatDeviceController;
use App\Http\Controllers\Api\ConsultaInteligenteController;
use App\Http\Controllers\Api\AsambleaController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\NotificacionController;
use App\Http\Controllers\Api\FcmPreferenciaController;
use App\Http\Controllers\Api\ZoeController;
use App\Http\Controllers\Api\ActividadController;
use App\Http\Controllers\Api\ZoeEstadisticasController;
use App\Http\Controllers\Api\ZoeEstadisticasBridgeController;
use App\Http\Controllers\Api\ZoeVozController;


/*
|--------------------------------------------------------------------------
| PRUEBA DE LA API
|--------------------------------------------------------------------------
*/

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API de SIGEFIV funcionando correctamente.',
    ]);
});

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN (RUTAS PÚBLICAS)
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/auth/google', [AuthController::class, 'google']);
});

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (SANCTUM)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | USUARIO AUTENTICADO
    |--------------------------------------------------------------------------
    */

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | MI CUENTA — PERFIL DEL USUARIO AUTENTICADO
    |--------------------------------------------------------------------------
    |
    | Estas rutas solo permiten modificar al usuario que posee
    | el token de autenticación.
    |
    */

    Route::put('/mi-cuenta', [
        AuthController::class,
        'actualizarPerfil',
    ]);

    Route::post('/mi-cuenta/foto', [
        AuthController::class,
        'actualizarFoto',
    ]);

    /*
    |--------------------------------------------------------------------------
    | BIENVENIDA / ONBOARDING
    |--------------------------------------------------------------------------
    */

    Route::post('/usuario/marcar-bienvenida', function (Request $request) {
        $user = $request->user();

        $user->bienvenida_vista = true;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Bienvenida registrada correctamente.',
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [
        DashboardController::class,
        'index',
    ])->middleware('permission:dashboard');

    /*
    |--------------------------------------------------------------------------
    | CAJA
    |--------------------------------------------------------------------------
    */

    Route::get('/caja', [
        CajaController::class,
        'index',
    ])->middleware('permission:caja.index');

    /*
    |--------------------------------------------------------------------------
    | MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    Route::get('/movimientos', [
        MovimientoController::class,
        'index',
    ])->middleware('permission:movimientos.index');

    Route::get('/movimientos/categorias', [
    MovimientoController::class,
    'categorias',
])->middleware('permission:movimientos.index');


    Route::get('/movimientos/{movimiento}', [
    MovimientoController::class,
    'show',
])->middleware('permission:movimientos.index');

    Route::post('/movimientos', [
        MovimientoController::class,
        'store',
    ])->middleware('permission:movimientos.index');

    Route::put('/movimientos/{movimiento}', [
        MovimientoController::class,
        'update',
    ])->middleware('permission:movimientos.index');

    Route::delete('/movimientos/{movimiento}', [
        MovimientoController::class,
        'destroy',
    ])->middleware('permission:movimientos.index');
    


    /*
    |--------------------------------------------------------------------------
    | CATEGORÍAS
    |--------------------------------------------------------------------------
    */

    Route::get('/categorias', [
        CategoriaController::class,
        'index',
    ])->middleware('permission:categorias.index');

    /*
    |--------------------------------------------------------------------------
    | PERÍODOS
    |--------------------------------------------------------------------------
    */

    Route::get('/periodo/abierto', [
        PeriodoController::class,
        'abierto',
    ])->middleware('permission:periodos.index');

    Route::get('/periodos', [
        PeriodoController::class,
        'index',
    ])->middleware('permission:periodos.index');

    Route::get('/periodos/{id}', [
        PeriodoController::class,
        'show',
    ])->middleware('permission:periodos.index');

    Route::get('/periodos/{id}/movimientos', [
        PeriodoController::class,
        'movimientos',
    ])->middleware('permission:periodos.index');
Route::post('/periodos/{id}/cerrar', [
    PeriodoController::class,
    'cerrar',
])->middleware('permission:periodos.cerrar');
    /*
    |--------------------------------------------------------------------------
    | ZOE — ASISTENTE INTELIGENTE
    |--------------------------------------------------------------------------
    */

    Route::post('/consulta-inteligente', [
        ConsultaInteligenteController::class,
        'consultar',
    ]);

    /*
    |--------------------------------------------------------------------------
    | CHAT DEVICES
    |--------------------------------------------------------------------------
    */

    Route::get('/chat-devices/{device}/estado', [
        ChatDeviceController::class,
        'estado',
    ]);

    /*
    |--------------------------------------------------------------------------
    | USUARIOS
    |--------------------------------------------------------------------------
    */

    Route::get('/usuarios', [
        UsuarioController::class,
        'index',
    ])->middleware('permission:usuarios.index');

    Route::patch('/usuarios/{usuario}/estado', [
        UsuarioController::class,
        'cambiarEstado',
    ])->middleware('permission:usuarios.index');

    Route::patch('/usuarios/{usuario}/rol', [
        UsuarioController::class,
        'cambiarRol',
    ])->middleware('permission:usuarios.index');

    Route::delete('/usuarios/{usuario}', [
        UsuarioController::class,
        'eliminar',
    ])->middleware('permission:usuarios.index');

    /*
    |--------------------------------------------------------------------------
    | ROLES Y PERMISOS
    |--------------------------------------------------------------------------
    */

    Route::get('/roles', [
        RolController::class,
        'index',
    ])->middleware('permission:roles.index');

    Route::get('/roles/{id}', [
        RolController::class,
        'show',
    ])->middleware('permission:roles.index');

    Route::post('/roles', [
        RolController::class,
        'store',
    ])->middleware('permission:roles.index');

    Route::put('/roles/{id}', [
        RolController::class,
        'update',
    ])->middleware('permission:roles.index');

    Route::delete('/roles/{id}', [
        RolController::class,
        'destroy',
    ])->middleware('permission:roles.index');

    /*
    |--------------------------------------------------------------------------
    | CHAT VECINAL
    |--------------------------------------------------------------------------
    */

    Route::get('/chat', [
        ChatApiController::class,
        'index',
    ])->middleware('permission:chat.index');

    Route::post('/chat', [
        ChatApiController::class,
        'store',
    ])->middleware('permission:chat.index');

    Route::post('/chat/presencia', [
        ChatApiController::class,
        'presencia',
    ])->middleware('permission:chat.index');

    Route::post('/chat/escribiendo', [
        ChatApiController::class,
        'escribiendo',
    ])->middleware('permission:chat.index');

    Route::get('/chat/nuevos', [
        ChatApiController::class,
        'nuevos',
    ])->middleware('permission:chat.index');
    Route::post('/chat/leido', [
    ChatApiController::class,
    'marcarLeido',
])->middleware('permission:chat.index');

    Route::post('/chat/{chatMessage}/reaccion', [
        ChatApiController::class,
        'reaccion',
    ])->middleware('permission:chat.index');

    Route::get('/chat/{chatMessage}/reacciones', [
        ChatApiController::class,
        'reacciones',
    ])->middleware('permission:chat.index');

    /*
    |--------------------------------------------------------------------------
    | ASAMBLEAS
    |--------------------------------------------------------------------------
    */

    Route::get('/asambleas', [
        AsambleaController::class,
        'index',
    ])->middleware('permission:asambleas.index');

    Route::post('/asambleas', [
        AsambleaController::class,
        'store',
    ])->middleware('permission:asambleas.index');

Route::get('/asambleas/convocatoria/proxima', [
    AsambleaController::class,
    'proxima',
]);
Route::get('/asambleas/aviso-sin-asamblea', [
    AsambleaController::class,
    'avisoSinAsamblea',
]);

Route::get('/asambleas/convocatoria/{asamblea}', [
    AsambleaController::class,
    'showPublicada',
]);

    Route::get('/asambleas/{asamblea}', [
        AsambleaController::class,
        'show',
    ])->middleware('permission:asambleas.index');

    Route::put('/asambleas/{asamblea}', [
        AsambleaController::class,
        'update',
    ])->middleware('permission:asambleas.index');

    Route::delete('/asambleas/{asamblea}', [
        AsambleaController::class,
        'destroy',
    ])->middleware('permission:asambleas.index');

    Route::post('/asambleas/{asamblea}/publicar', [
        AsambleaController::class,
        'publicar',
    ])->middleware('permission:asambleas.index');

    /*
    |--------------------------------------------------------------------------
    | FIREBASE CLOUD MESSAGING (FCM)
    |--------------------------------------------------------------------------
    */

    Route::post('/fcm/token', [
        FcmTokenController::class,
        'registrar',
    ]);

    Route::post('/fcm/token/desactivar', [
        FcmTokenController::class,
        'desactivar',
    ]);

    /*
    |--------------------------------------------------------------------------
    | NOTIFICACIONES
    |--------------------------------------------------------------------------
    */

    Route::get('/notificaciones', [
        NotificacionController::class,
        'index',
    ])->middleware('permission:notificaciones.index');

    Route::get('/notificaciones/no-leidas', [
        NotificacionController::class,
        'noLeidas',
    ])->middleware('permission:notificaciones.index');

    Route::post('/notificaciones/{id}/leer', [
        NotificacionController::class,
        'marcarLeida',
    ])->middleware('permission:notificaciones.index');

    Route::post('/notificaciones/leer-todas', [
        NotificacionController::class,
        'marcarTodasLeidas',
    ])->middleware('permission:notificaciones.index');

 /*
|--------------------------------------------------------------------------
| PREFERENCIA FCM
|--------------------------------------------------------------------------
*/

Route::get('/fcm/preferencia', [
    FcmPreferenciaController::class,
    'obtener',
]);

Route::post('/fcm/preferencia', [
    FcmPreferenciaController::class,
    'actualizar',
]);
    /*
    |--------------------------------------------------------------------------
    | ZOE
    |--------------------------------------------------------------------------
    */

      Route::post('/zoe', [
        ZoeController::class,
        'consultar',
    ]);

    Route::post('/zoe/mascota', [
        ZoeController::class,
        'mensajeMascota',
    ]);
    /*
    |--------------------------------------------------------------------------
    | ENVÍO DE NOTIFICACIONES
    |--------------------------------------------------------------------------
    */

    Route::post('/notificaciones/enviar', [
        NotificacionController::class,
        'enviar',
    ]);

    Route::get('/actividad', [
    ActividadController::class,
    'index',
])->middleware('permission:actividad.index');
Route::get('/estadisticas/ingresos-egresos', [
    ZoeEstadisticasController::class,
    'ingresosVsEgresos'
]);

Route::get('/estadisticas/evolucion-saldo', [
    ZoeEstadisticasController::class,
    'evolucionSaldo'
]);

Route::get('/estadisticas/egresos-categoria', [
    ZoeEstadisticasController::class,
    'egresosPorCategoria'
]);
Route::get(
    '/estadisticas/resumen-financiero',
    [ZoeEstadisticasController::class, 'resumenFinanciero']
);
Route::post(
    '/zoe-estadisticas',
    [ZoeEstadisticasBridgeController::class, 'consultar']
);

Route::post('/zoe-voz', [
    ZoeVozController::class,
    'transcribir'
]);

});

/*
|--------------------------------------------------------------------------
| CHAT DEVICES — ACCESO POR TOKEN
|--------------------------------------------------------------------------
*/

Route::get('/chat-devices/{device}/estado-device', [
    ChatDeviceController::class,
    'estadoPorToken',
]);

Route::post('/chat-devices/{device}/control', [
    ChatDeviceController::class,
    'controlarPorToken',
]);

