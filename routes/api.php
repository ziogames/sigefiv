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

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

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
            'message' => 'Bienvenida registrada correctamente.'
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | CAJA
    |--------------------------------------------------------------------------
    */

    Route::get('/caja', [CajaController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    Route::get('/movimientos', [MovimientoController::class, 'index']);
    Route::post('/movimientos', [MovimientoController::class, 'store']);

    /*
    |--------------------------------------------------------------------------
    | CATEGORÍAS
    |--------------------------------------------------------------------------
    */

    Route::get('/categorias', [CategoriaController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | PERÍODOS
    |--------------------------------------------------------------------------
    */

    Route::get('/periodo/abierto', [PeriodoController::class, 'abierto']);
    Route::get('/periodos', [PeriodoController::class, 'index']);
    Route::get('/periodos/{id}', [PeriodoController::class, 'show']);
    Route::get('/periodos/{id}/movimientos', [PeriodoController::class, 'movimientos']);

    /*
    |--------------------------------------------------------------------------
    | ZOE — ASISTENTE INTELIGENTE
    |--------------------------------------------------------------------------
    */

    Route::post('/consulta-inteligente', [ConsultaInteligenteController::class, 'consultar']);

    /*
    |--------------------------------------------------------------------------
    | CHAT DEVICES
    |--------------------------------------------------------------------------
    */

    Route::get('/chat-devices/{device}/estado', [ChatDeviceController::class, 'estado']);

    /*
    |--------------------------------------------------------------------------
    | USUARIOS
    |--------------------------------------------------------------------------
    */

    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::patch('/usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado']);
    Route::patch('/usuarios/{usuario}/rol', [UsuarioController::class, 'cambiarRol']);
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'eliminar']);

    /*
    |--------------------------------------------------------------------------
    | ROLES Y PERMISOS
    |--------------------------------------------------------------------------
    */

    Route::get('/roles', [RolController::class, 'index']);
    Route::get('/roles/{id}', [RolController::class, 'show']);
    Route::post('/roles', [RolController::class, 'store']);
    Route::put('/roles/{id}', [RolController::class, 'update']);
    Route::delete('/roles/{id}', [RolController::class, 'destroy']);

    /*
    |--------------------------------------------------------------------------
    | CHAT VECINAL
    |--------------------------------------------------------------------------
    */

    Route::get('/chat', [ChatApiController::class, 'index']);
    Route::post('/chat', [ChatApiController::class, 'store']);
    Route::post('/chat/presencia', [ChatApiController::class, 'presencia']);
    Route::post('/chat/escribiendo', [ChatApiController::class, 'escribiendo']);
    Route::get('/chat/nuevos', [ChatApiController::class, 'nuevos']);
    Route::post('/chat/{chatMessage}/reaccion', [ChatApiController::class, 'reaccion']);
Route::get('/chat/{chatMessage}/reacciones', [ChatApiController::class, 'reacciones']);

    /*
    |--------------------------------------------------------------------------
    | ASAMBLEAS
    |--------------------------------------------------------------------------
    */

    Route::get('/asambleas', [AsambleaController::class, 'index']);
    Route::post('/asambleas', [AsambleaController::class, 'store']);
    Route::get('/asambleas/{asamblea}', [AsambleaController::class, 'show']);
    Route::put('/asambleas/{asamblea}', [AsambleaController::class, 'update']);
    Route::delete('/asambleas/{asamblea}', [AsambleaController::class, 'destroy']);
    Route::post('/asambleas/{asamblea}/publicar', [AsambleaController::class, 'publicar']);

    /*
    |--------------------------------------------------------------------------
    | FIREBASE CLOUD MESSAGING (FCM)
    |--------------------------------------------------------------------------
    */

    Route::post('/fcm/token', [FcmTokenController::class, 'registrar']);
    Route::post('/fcm/token/desactivar', [FcmTokenController::class, 'desactivar']);
});

/*
|--------------------------------------------------------------------------
| CHAT DEVICES — ACCESO POR TOKEN
|--------------------------------------------------------------------------
*/

Route::get('/chat-devices/{device}/estado-device', [ChatDeviceController::class, 'estadoPorToken']);
Route::post('/chat-devices/{device}/control', [ChatDeviceController::class, 'controlarPorToken']);