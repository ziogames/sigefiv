<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
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

        $user = User::where(
            'email',
            $validated['email']
        )->first();

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Las credenciales son incorrectas.',
            ], 401);
        }

        if ($user->estado === 'bloqueado') {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta de SIGEFIV está bloqueada. Comunícate con el administrador.',
            ], 403);
        }

        $user->tokens()->delete();

        $token = $user->createToken('sigefiv-android')->plainTextToken;

        $rol = $user->getRoleNames()->first() ?? 'Sin Rol';

        $permisos = $user
            ->getAllPermissions()
            ->pluck('name')
            ->values();

        $user->ultimo_acceso = now();
        $user->ultima_ip = $request->ip();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'token' => $token,
            'token_type' => 'Bearer',
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estado' => $user->estado,
                'telefono' => $user->telefono,
                'dni' => $user->dni,
                'direccion' => $user->direccion,
                'foto' => $user->avatar,
                'rol' => $rol,
                'permisos' => $permisos,
                'bienvenida_vista' => (bool) $user->bienvenida_vista,
            ],
        ]);
    }

    public function google(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => [
                'required',
                'string',
            ],
        ]);

        $clientId = config('services.google.android_client_id') 
            ?: config('services.google.client_id');

        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'La autenticación de Google para Android no está configurada.',
            ], 500);
        }

        try {
            $client = new GoogleClient([
                'client_id' => $clientId,
            ]);

            $payload = $client->verifyIdToken($validated['id_token']);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'El token de Google no pudo ser validado: ' . $e->getMessage(),
            ], 401);
        }

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'El token de Google no es válido.',
            ], 401);
        }

        $googleId = $payload['sub'] ?? null;
        $email = $payload['email'] ?? null;
        $emailVerified = $payload['email_verified'] ?? false;

        if (!$googleId || !$email || !$emailVerified) {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta de Google no tiene una identidad válida o el correo no está verificado.',
            ], 401);
        }

        $nombre = $payload['name'] ?? 'Usuario Google';
        $foto = $payload['picture'] ?? null;

        try {
            $user = User::where('google_id', $googleId)->first();

            if (!$user) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                $user = User::create([
                    'name' => $nombre,
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar' => $foto,
                    'password' => Hash::make(bin2hex(random_bytes(32))),
                    'estado' => 'pendiente',
                    'recibir_notificaciones' => false,
                    'bienvenida_vista' => false,
                ]);

                $user->assignRole('Consulta');
                $user->refresh();
            } else {
                $user->update([
                    'google_id' => $googleId,
                    'avatar' => $foto,
                ]);
                $user->refresh();
            }

            if ($user->estado === 'bloqueado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta de SIGEFIV está bloqueada. Comunícate con el administrador.',
                ], 403);
            }

            $user->tokens()->delete();

            $token = $user->createToken('sigefiv-android')->plainTextToken;

            $rol = $user->getRoleNames()->first() ?? 'Consulta';

            $permisos = $user
                ->getAllPermissions()
                ->pluck('name')
                ->values();

            $user->ultimo_acceso = now();
            $user->ultima_ip = $request->ip();
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Inicio de sesión con Google correcto.',
                'token' => $token,
                'token_type' => 'Bearer',
                'usuario' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'estado' => $user->estado,
                    'telefono' => $user->telefono,
                    'dni' => $user->dni,
                    'direccion' => $user->direccion,
                    'foto' => $user->avatar,
                    'rol' => $rol,
                    'permisos' => $permisos,
                    'bienvenida_vista' => (bool) $user->bienvenida_vista,
                ],
            ]);

        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la sesión de usuario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'estado' => $user->estado,
                'telefono' => $user->telefono,
                'dni' => $user->dni,
                'direccion' => $user->direccion,
                'foto' => $user->avatar,
                'rol' => $user->getRoleNames()->first() ?? 'Sin Rol',
                'permisos' => $user
                    ->getAllPermissions()
                    ->pluck('name')
                    ->values(),
                'bienvenida_vista' => (bool) $user->bienvenida_vista,
                'metodo_acceso' => $user->google_id
                    ? 'Google'
                    : 'Correo y contraseña',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request
            ->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}