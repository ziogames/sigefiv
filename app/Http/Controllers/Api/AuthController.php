<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cargo;
use Kreait\Firebase\Factory;
use Kreait\Firebase\JWT\IdTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

            $verifier = IdTokenVerifier::createWithProjectId(
                'sigefiv-b3dc6'
            );

            $verifiedIdToken = $verifier->verifyIdTokenWithLeeway(
                $idToken,
                60
            );

            $claims = $verifiedIdToken->payload();

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

            // Obtenemos el ID del cargo "Vecino" por defecto
            $cargoVecinoId = Cargo::where('nombre', 'Vecino')->value('id');

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
                    'cargo_id' =>
                        $cargoVecinoId,
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

                // Si el usuario existente no tiene cargo asignado, le ponemos "Vecino"
                if (is_null($user->cargo_id) && $cargoVecinoId) {
                    $datosFirebase['cargo_id'] = $cargoVecinoId;
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

    public function facebook(Request $request): JsonResponse
    {
        Log::info('SIGEFIV FACEBOOK: petición de login recibida');

        $validated = $request->validate([
            'access_token' => ['nullable', 'string', 'required_without:id_token'],
            'id_token'     => ['nullable', 'string', 'required_without:access_token'],
        ]);

        $facebookId = null;
        $email = null;
        $nombre = 'Usuario Facebook';
        $fotoFacebook = null;

        try {
            // OPCIÓN A: Si Android envía el Firebase ID Token (Firebase Auth + Facebook)
            if (!empty($validated['id_token'])) {
                $verifier = IdTokenVerifier::createWithProjectId('sigefiv-b3dc6');
                $verifiedIdToken = $verifier->verifyIdTokenWithLeeway($validated['id_token'], 60);
                $claims = $verifiedIdToken->payload();

                $facebookId = $claims['sub'] ?? null;
                $email = $claims['email'] ?? null;
                $nombre = $claims['name'] ?? 'Usuario Facebook';
                $fotoFacebook = $claims['picture'] ?? null;
            }
            // OPCIÓN B: Si Android envía directamente el Access Token del SDK de Facebook
            else {
                $response = Http::timeout(10)->get('https://graph.facebook.com/v21.0/me', [
                    'fields'       => 'id,name,email,picture.type(large)',
                    'access_token' => $validated['access_token'],
                ]);

                if ($response->failed()) {
                    Log::warning('SIGEFIV FACEBOOK: token rechazado por Graph API', [
                        'status' => $response->status(),
                        'body'   => $response->json(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'El token de Facebook no es válido o ha expirado.',
                    ], 401);
                }

                $fbData = $response->json();
                $facebookId = $fbData['id'] ?? null;
                $email = $fbData['email'] ?? null;
                $nombre = $fbData['name'] ?? 'Usuario Facebook';
                $fotoFacebook = $fbData['picture']['data']['url'] ?? null;
            }
        } catch (\Throwable $e) {
            Log::error('SIGEFIV FACEBOOK: ERROR VALIDANDO TOKEN', [
                'tipo'    => get_class($e),
                'mensaje' => $e->getMessage(),
            ]);
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo verificar la sesión de Facebook.',
            ], 401);
        }

        if (!$facebookId) {
            return response()->json([
                'success' => false,
                'message' => 'Facebook no devolvió un identificador de usuario válido.',
            ], 401);
        }

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta de Facebook no tiene un correo electrónico asociado. Por favor inicia sesión con Google o con tu correo.',
            ], 422);
        }

        try {
            $cargoVecinoId = Cargo::where('nombre', 'Vecino')->value('id');
            $tieneColumnaFb = Schema::hasColumn('users', 'facebook_id');

            // Descargar y guardar localmente el avatar de Facebook
            $rutaAvatarLocal = $this->guardarAvatarExterno(
                $fotoFacebook,
                $validated['access_token'] ?? null
            );

            $user = null;
            if ($tieneColumnaFb) {
                $user = User::where('facebook_id', $facebookId)->first();
            }

            if (!$user) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                $datosCrear = [
                    'name'                   => $nombre,
                    'email'                  => $email,
                    'foto'                   => $rutaAvatarLocal ?? $fotoFacebook,
                    'cargo_id'               => $cargoVecinoId,
                    'password'               => Hash::make(bin2hex(random_bytes(32))),
                    'estado'                 => 'pendiente',
                    'recibir_notificaciones' => false,
                    'bienvenida_vista'       => false,
                ];

                if ($tieneColumnaFb) {
                    $datosCrear['facebook_id'] = $facebookId;
                }

                $user = User::create($datosCrear);
                $user->assignRole('Consulta');
                $user->refresh();

                Log::info('SIGEFIV FACEBOOK: usuario creado', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ]);
            } else {
                $datosActualizar = [];

                if ($tieneColumnaFb && empty($user->facebook_id)) {
                    $datosActualizar['facebook_id'] = $facebookId;
                }

                // Si no tiene foto o aún apunta a una URL externa, reemplazamos con la descarga local
                if ((empty($user->foto) || filter_var($user->foto, FILTER_VALIDATE_URL)) && !empty($rutaAvatarLocal)) {
                    $datosActualizar['foto'] = $rutaAvatarLocal;
                } elseif (empty($user->foto) && !empty($fotoFacebook)) {
                    $datosActualizar['foto'] = $fotoFacebook;
                }

                if (is_null($user->cargo_id) && $cargoVecinoId) {
                    $datosActualizar['cargo_id'] = $cargoVecinoId;
                }

                if (!empty($datosActualizar)) {
                    $user->update($datosActualizar);
                    $user->refresh();
                }

                Log::info('SIGEFIV FACEBOOK: usuario existente vinculado', [
                    'user_id' => $user->id,
                    'email'   => $user->email,
                ]);
            }

            if ($user->estado === 'bloqueado') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta de SIGEFIV está bloqueada. Comunícate con el administrador.',
                ], 403);
            }

            $user->tokens()->delete();
            $token = $user->createToken('sigefiv-android')->plainTextToken;

            $user->ultimo_acceso = now();
            $user->ultima_ip = $request->ip();
            $user->save();

            Log::info('SIGEFIV FACEBOOK: LOGIN COMPLETADO', [
                'user_id' => $user->id,
                'email'   => $user->email,
            ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Inicio de sesión con Facebook correcto.',
                'token'      => $token,
                'token_type' => 'Bearer',
                'usuario'    => $this->datosUsuario($user),
            ]);

        } catch (\Throwable $e) {
            Log::error('SIGEFIV FACEBOOK: ERROR PROCESANDO USUARIO', [
                'tipo'    => get_class($e),
                'mensaje' => $e->getMessage(),
            ]);
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

    public function actualizarPassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',      // Al menos una letra mayúscula
                'regex:/[@$!%*#?&]/', // Al menos un carácter especial
            ],
            'new_password_confirmation' => ['required', 'same:new_password'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente.',
        ], 200);
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

        $nombrePreferido = !empty($user->seudonimo) 
            ? $user->seudonimo 
            : explode(' ', trim($user->name))[0];

        return [
            'id' =>
                $user->id,
            'name' =>
                $user->name,
            'seudonimo' =>
                $user->seudonimo,
            'nombre_usuario' =>
                $nombrePreferido,
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
                    : (!empty($user->facebook_id) ? 'Facebook' : 'Correo y contraseña'),
        ];
    }
    private function guardarAvatarExterno(?string $urlFoto, ?string $accessToken = null): ?string
{
    if (empty($urlFoto)) {
        return null;
    }

    try {
        // Si es URL de Graph API y tenemos access_token, pedimos la foto en alta calidad sin redirección rota
        if (str_contains($urlFoto, 'graph.facebook.com') && !empty($accessToken) && !str_contains($urlFoto, 'access_token=')) {
            $separador = str_contains($urlFoto, '?') ? '&' : '?';
            $urlFoto .= $separador . 'access_token=' . urlencode($accessToken);
        }

        $respuesta = Http::timeout(10)->withOptions(['allow_redirects' => true])->get($urlFoto);

        if ($respuesta->successful() && str_starts_with($respuesta->header('Content-Type', ''), 'image/')) {
            $extension = match ($respuesta->header('Content-Type')) {
                'image/png'  => 'png',
                'image/webp' => 'webp',
                default      => 'jpg',
            };

            $nombreArchivo = 'usuarios/fb_' . uniqid() . '.' . $extension;
            Storage::disk('public')->put($nombreArchivo, $respuesta->body());

            return $nombreArchivo;
        }
    } catch (\Throwable $e) {
        Log::warning('SIGEFIV FACEBOOK: no se pudo descargar avatar', ['error' => $e->getMessage()]);
    }

    return null;
}
}