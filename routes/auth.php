<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Registro
    |--------------------------------------------------------------------------
    */

    Route::get(
        'register',
        [RegisteredUserController::class, 'create']
    )->name('register');

    Route::post(
        'register',
        [RegisteredUserController::class, 'store']
    );


    /*
    |--------------------------------------------------------------------------
    | Login tradicional
    |--------------------------------------------------------------------------
    */

    Route::get(
        'login',
        [AuthenticatedSessionController::class, 'create']
    )->name('login');

    Route::post(
        'login',
        [AuthenticatedSessionController::class, 'store']
    );


    /*
    |--------------------------------------------------------------------------
    | Login con Google
    |--------------------------------------------------------------------------
    */

    Route::get(
        'auth/google',
        [GoogleController::class, 'redirect']
    )->name('google.redirect');

    Route::get(
        'auth/google/callback',
        [GoogleController::class, 'callback']
    )->name('google.callback');


    /*
    |--------------------------------------------------------------------------
    | Recuperación de contraseña
    |--------------------------------------------------------------------------
    */

    Route::get(
        'forgot-password',
        [PasswordResetLinkController::class, 'create']
    )->name('password.request');

    Route::post(
        'forgot-password',
        [PasswordResetLinkController::class, 'store']
    )->name('password.email');

    Route::get(
        'reset-password/{token}',
        [NewPasswordController::class, 'create']
    )->name('password.reset');

    Route::post(
        'reset-password',
        [NewPasswordController::class, 'store']
    )->name('password.store');
});


/*
|--------------------------------------------------------------------------
| Rutas para usuarios autenticados
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Verificación de correo
    |--------------------------------------------------------------------------
    */

    Route::get(
        'verify-email',
        EmailVerificationPromptController::class
    )->name('verification.notice');

    Route::get(
        'verify-email/{id}/{hash}',
        VerifyEmailController::class
    )
        ->middleware([
            'signed',
            'throttle:6,1'
        ])
        ->name('verification.verify');

    Route::post(
        'email/verification-notification',
        [
            EmailVerificationNotificationController::class,
            'store'
        ]
    )
        ->middleware('throttle:6,1')
        ->name('verification.send');


    /*
    |--------------------------------------------------------------------------
    | Confirmación de contraseña
    |--------------------------------------------------------------------------
    */

    Route::get(
        'confirm-password',
        [
            ConfirmablePasswordController::class,
            'show'
        ]
    )->name('password.confirm');

    Route::post(
        'confirm-password',
        [
            ConfirmablePasswordController::class,
            'store'
        ]
    );


    /*
    |--------------------------------------------------------------------------
    | Cambio de contraseña
    |--------------------------------------------------------------------------
    */

    Route::put(
        'password',
        [
            PasswordController::class,
            'update'
        ]
    )->name('password.update');


    /*
    |--------------------------------------------------------------------------
    | Cerrar sesión
    |--------------------------------------------------------------------------
    */

    Route::post(
        'logout',
        [
            AuthenticatedSessionController::class,
            'destroy'
        ]
    )->name('logout');

});