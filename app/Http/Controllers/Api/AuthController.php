<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Iniciar sesión desde la aplicación Android.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | BUSCAR USUARIO
        |--------------------------------------------------------------------------
        */

        $user = User::where(
            'email',
            $validated['email']
        )->first();


        /*
        |--------------------------------------------------------------------------
        | VALIDAR CREDENCIALES
        |--------------------------------------------------------------------------
        */

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {

            return response()->json([

                'success' => false,

                'message' =>
                    'Las credenciales son incorrectas.',

            ], 401);
        }


        /*
        |--------------------------------------------------------------------------
        | ELIMINAR TOKENS ANTERIORES DEL DISPOSITIVO
        |--------------------------------------------------------------------------
        |
        | Por ahora mantenemos un token activo por usuario.
        | Más adelante podremos identificar cada dispositivo.
        |
        */

        $user->tokens()->delete();


        /*
        |--------------------------------------------------------------------------
        | CREAR TOKEN SANCTUM
        |--------------------------------------------------------------------------
        */

        $token = $user->createToken(
            'sigefiv-android'
        )->plainTextToken;


        /*
        |--------------------------------------------------------------------------
        | OBTENER ROL
        |--------------------------------------------------------------------------
        */

        $rol = $user->getRoleNames()->first();


        /*
        |--------------------------------------------------------------------------
        | OBTENER PERMISOS
        |--------------------------------------------------------------------------
        */

        $permisos = $user
            ->getAllPermissions()
            ->pluck('name')
            ->values();


        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR ÚLTIMO ACCESO
        |--------------------------------------------------------------------------
        */

        $user->ultimo_acceso =
            now();

        $user->ultima_ip =
            $request->ip();

        $user->save();


        /*
        |--------------------------------------------------------------------------
        | RESPUESTA
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' => true,

            'message' =>
                'Inicio de sesión correcto.',

            'token' =>
                $token,

            'token_type' =>
                'Bearer',

            'usuario' => [

                'id' =>
                    $user->id,

                'name' =>
                    $user->name,

                'email' =>
                    $user->email,

                'telefono' =>
                    $user->telefono,

                'dni' =>
                    $user->dni,

                'direccion' =>
                    $user->direccion,

                'foto' =>
                    $user->avatar,

                'rol' =>
                    $rol,

                'permisos' =>
                    $permisos,
            ],
        ]);
    }


    /**
     * Obtener información del usuario autenticado.
     */
    public function user(Request $request): JsonResponse
    {
        $user =
            $request->user();


        return response()->json([

            'success' => true,

            'usuario' => [

                'id' =>
                    $user->id,

                'name' =>
                    $user->name,

                'email' =>
                    $user->email,

                'telefono' =>
                    $user->telefono,

                'dni' =>
                    $user->dni,

                'direccion' =>
                    $user->direccion,

                'foto' =>
                    $user->avatar,

                'rol' =>
                    $user->getRoleNames()->first(),

                'permisos' =>
                    $user
                        ->getAllPermissions()
                        ->pluck('name')
                        ->values(),
            ],
        ]);
    }


    /**
     * Cerrar sesión en Android.
     */
    public function logout(Request $request): JsonResponse
    {
        $request
            ->user()
            ->currentAccessToken()
            ?->delete();


        return response()->json([

            'success' => true,

            'message' =>
                'Sesión cerrada correctamente.',
        ]);
    }
}