<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\PeriodoController;


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
| AUTENTICACIÓN
|--------------------------------------------------------------------------
*/

Route::post('/login', [
    AuthController::class,
    'login'
]);


/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [
        AuthController::class,
        'user'
    ]);

    Route::post('/logout', [
        AuthController::class,
        'logout'
    ]);

    Route::get('/dashboard', [
        DashboardController::class,
        'index'
    ]);

    Route::get('/movimientos', [
        MovimientoController::class,
        'index'
    ]);

    Route::post('/movimientos', [
        MovimientoController::class,
        'store'
    ]);

    Route::get('/categorias', [
        CategoriaController::class,
        'index'
    ]);

    Route::get('/periodo/abierto', [
        PeriodoController::class,
        'abierto'
    ]);
    Route::get('/periodos', [
    PeriodoController::class,
    'index'
]);

Route::get('/periodos/{id}', [
    PeriodoController::class,
    'show'
]);

Route::get('/periodos/{id}/movimientos', [
    PeriodoController::class,
    'movimientos'
]);


});