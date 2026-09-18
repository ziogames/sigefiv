<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        $token = $user
            ->createToken('sigefiv-android')
            ->plainTextToken;

        $user->ultimo_acceso = now();
        $user->ultima_ip = $request->ip();
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'token' => $token,
            'token_type' => 'Bearer',
            'usuario' => $this->datosUsuario($user),
        ]);
    }

    public function google(Request $request): JsonResponse
    {
        Log::info(
            'SIGEFIV FIREBASE: petición de login recibida'
        );

        $validated = $request->validate([
            'id_token' => [
                'required',
                'string',
            ],
        ]);

        $idToken = $validated['id_token'];

        Log::info(
            'SIGEFIV FIREBASE: Firebase ID Token recibido',
            [
                'presente' => !empty($idToken),
                'longitud' => strlen($idToken),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDAR FIREBASE ID TOKEN
        |--------------------------------------------------------------------------
        */

        try {
            $credentials = config(
                'firebase.projects.app.credentials'
            );

            if (!$credentials) {
                Log::error(
                    'SIGEFIV FIREBASE: credenciales no configuradas'
                );

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Las credenciales de Firebase no están configuradas.',
                ], 500);
            }

            Log::info(
                'SIGEFIV FIREBASE: verificando Firebase ID Token'
            );

            /*
            |--------------------------------------------------------------------------
            | VERIFICADOR FIREBASE
            |--------------------------------------------------------------------------
            |
            | Se utiliza directamente IdTokenVerifier para poder establecer
            | una tolerancia de reloj de 60 segundos.
            |
            */

            $verifier = IdTokenVerifier::createWithProjectId(
                'sigefiv-b3dc6'
            );

            $verifiedIdToken = $verifier->verifyIdTokenWithLeeway(
                $idToken,
                60
            );

            /*
            |--------------------------------------------------------------------------
            | OBTENER PAYLOAD
            |--------------------------------------------------------------------------
            |
            | En firebase-tokens 5.x payload() devuelve un array.
            |
            */

            $claims = $verifiedIdToken->payload();

            /*
            |--------------------------------------------------------------------------
            | IDENTIDAD FIREBASE
            |--------------------------------------------------------------------------
            */

            $firebaseUid = $claims['sub'] ?? null;

            $email = $claims['email'] ?? null;

            $emailVerified =
                $claims['email_verified'] ?? false;

            $nombre =
                $claims['name'] ?? 'Usuario Google';

            $fotoGoogle =
                $claims['picture'] ?? null;

            Log::info(
                'SIGEFIV FIREBASE: token validado correctamente',
                [
                    'uid_presente' =>
                        !empty($firebaseUid),

                    'email_presente' =>
                        !empty($email),

                    'email_verified' =>
                        $emailVerified,

                    'nombre_presente' =>
                        !empty($nombre),

                    'foto_presente' =>
                        !empty($fotoGoogle),
                ]
            );

        } catch (\Throwable $e) {

            Log::error(
                'SIGEFIV FIREBASE: ERROR VALIDANDO TOKEN',
                [
                    'tipo' =>
                        get_class($e),

                    'mensaje' =>
                        $e->getMessage(),
                ]
            );

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'El token de Firebase no pudo ser validado.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDAR IDENTIDAD
        |--------------------------------------------------------------------------
        */

        if (
            !$firebaseUid ||
            !$email ||
            !$emailVerified
        ) {

            Log::warning(
                'SIGEFIV FIREBASE: identidad inválida',
                [
                    'uid_presente' =>
                        !empty($firebaseUid),

                    'email_presente' =>
                        !empty($email),

                    'email_verified' =>
                        $emailVerified,
                ]
            );

            return response()->json([
                'success' => false,
                'message' =>
                    'La cuenta de Google no tiene una identidad válida o el correo no está verificado.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | PROCESAR USUARIO
        |--------------------------------------------------------------------------
        */

        try {

            /*
             * Primero buscamos por Firebase UID.
             */
            $user = User::where(
                'google_id',
                $firebaseUid
            )->first();

            /*
             * Si no existe, buscamos por correo.
             */
            if (!$user) {

                $user = User::where(
                    'email',
                    $email
                )->first();
            }

            /*
             * Crear usuario nuevo.
             */
            if (!$user) {

                $user = User::create([
                    'name' =>
                        $nombre,

                    'email' =>
                        $email,

                    'google_id' =>
                        $firebaseUid,

                    'foto' =>
                        $fotoGoogle,

                    'password' =>
                        Hash::make(
                            bin2hex(
                                random_bytes(32)
                            )
                        ),

                    'estado' =>
                        'pendiente',

                    'recibir_notificaciones' =>
                        false,

                    'bienvenida_vista' =>
                        false,
                ]);

                $user->assignRole(
                    'Consulta'
                );

                $user->refresh();

                Log::info(
                    'SIGEFIV FIREBASE: usuario creado',
                    [
                        'user_id' =>
                            $user->id,

                        'email' =>
                            $user->email,
                    ]
                );

            } else {

                /*
                 * Vincular usuario existente con Firebase.
                 */
                $datosFirebase = [
                    'google_id' =>
                        $firebaseUid,
                ];

                /*
                 * Si no tiene foto, utilizar la de Google.
                 */
                if (
                    empty($user->foto) &&
                    !empty($fotoGoogle)
                ) {

                    $datosFirebase['foto'] =
                        $fotoGoogle;
                }

                $user->update(
                    $datosFirebase
                );

                $user->refresh();

                Log::info(
                    'SIGEFIV FIREBASE: usuario existente vinculado',
                    [
                        'user_id' =>
                            $user->id,

                        'email' =>
                            $user->email,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | CUENTA BLOQUEADA
            |--------------------------------------------------------------------------
            */

            if (
                $user->estado === 'bloqueado'
            ) {

                return response()->json([
                    'success' => false,
                    'message' =>
                        'Tu cuenta de SIGEFIV está bloqueada. Comunícate con el administrador.',
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | TOKEN SIGEFIV
            |--------------------------------------------------------------------------
            */

            $user->tokens()->delete();

            $token = $user
                ->createToken(
                    'sigefiv-android'
                )
                ->plainTextToken;

            $user->ultimo_acceso =
                now();

            $user->ultima_ip =
                $request->ip();

            $user->save();

            Log::info(
                'SIGEFIV FIREBASE: LOGIN COMPLETADO',
                [
                    'user_id' =>
                        $user->id,

                    'email' =>
                        $user->email,
                ]
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'Inicio de sesión con Google correcto.',
                'token' =>
                    $token,
                'token_type' =>
                    'Bearer',
                'usuario' =>
                    $this->datosUsuario($user),
            ]);

        } catch (\Throwable $e) {

            Log::error(
                'SIGEFIV FIREBASE: ERROR PROCESANDO USUARIO',
                [
                    'tipo' =>
                        get_class($e),

                    'mensaje' =>
                        $e->getMessage(),
                ]
            );

            report($e);

            return response()->json([
                'success' => false,
                'message' =>
                    'Error al procesar la sesión de usuario: '
                    . $e->getMessage(),
            ], 500);
        }
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'usuario' =>
                $this->datosUsuario($user),
        ]);
    }

    public function actualizarPerfil(
        Request $request
    ): JsonResponse {

        $user = $request->user();

        $validated = $request->validate([
            'seudonimo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:50',
            ],

            'dni' => [
                'nullable',
                'string',
                'max:20',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $user->update([
            'seudonimo' =>
                $validated['seudonimo'] ?? null,

            'telefono' =>
                $validated['telefono'] ?? null,

            'dni' =>
                $validated['dni'] ?? null,

            'direccion' =>
                $validated['direccion'] ?? null,
        ]);

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' =>
                'Perfil actualizado correctamente.',
            'usuario' =>
                $this->datosUsuario($user),
        ]);
    }

    public function actualizarFoto(
        Request $request
    ): JsonResponse {

        $request->validate([
            'foto' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $user = $request->user();

        if (
            $user->foto &&
            !filter_var(
                $user->foto,
                FILTER_VALIDATE_URL
            )
        ) {

            Storage::disk('public')->delete(
                $user->foto
            );
        }

        $ruta = $request
            ->file('foto')
            ->store(
                'usuarios',
                'public'
            );

        $user->foto =
            $ruta;

        $user->save();

        $user->refresh();

        return response()->json([
            'success' => true,
            'message' =>
                'Foto de perfil actualizada correctamente.',
            'usuario' =>
                $this->datosUsuario($user),
        ]);
    }

    public function logout(
        Request $request
    ): JsonResponse {

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

    private function datosUsuario(
        User $user
    ): array {

        return [
            'id' =>
                $user->id,

            'name' =>
                $user->name,

            'seudonimo' =>
                $user->seudonimo,

            'email' =>
                $user->email,

            'estado' =>
                $user->estado,

            'telefono' =>
                $user->telefono,

            'dni' =>
                $user->dni,

            'direccion' =>
                $user->direccion,

            'foto' =>
                $user->avatar,

            'rol' =>
                $user
                    ->getRoleNames()
                    ->first()
                ?? 'Sin Rol',

            'permisos' =>
                $user
                    ->getAllPermissions()
                    ->pluck('name')
                    ->values(),

            'bienvenida_vista' =>
                (bool)
                $user->bienvenida_vista,

            'metodo_acceso' =>
                $user->google_id
                    ? 'Google'
                    : 'Correo y contraseña',
        ];
    }
}