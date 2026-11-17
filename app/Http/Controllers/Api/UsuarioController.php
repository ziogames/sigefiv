<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUsuarioRequest;
use App\Models\Cargo;
use App\Models\FcmToken;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\FcmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $buscar = $request->input('buscar');

            $usuarios = User::with(['roles', 'cargo'])
                ->when($buscar, function ($query) use ($buscar) {
                    $query->where(function ($q) use ($buscar) {
                        $q->where('name', 'like', "%{$buscar}%")
                          ->orWhere('email', 'like', "%{$buscar}%");
                    });
                })
                ->orderBy('name')
                ->get();

            $resultado = $usuarios->map(fn (User $usuario) => $this->transformarUsuario($usuario))->values();

            return response()->json([
                'success' => true,
                'message' => 'Usuarios obtenidos correctamente.',
                'usuarios' => $resultado,
                'roles' => Role::orderBy('name')->get(['id', 'name']),
                'cargos' => Cargo::orderBy('nombre')->get(['id', 'nombre']),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los usuarios.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreUsuarioRequest $request): JsonResponse
    {
        try {
            $cargoId = $this->resolverCargoPorRol($request->role, $request->cargo_id);

            $usuario = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'cargo_id' => $cargoId,
                'estado' => 'activo',
                'recibir_notificaciones' => true,
            ]);

            $usuario->assignRole($request->role);
            $usuario->load(['roles', 'cargo']);

            BitacoraService::registrar(
                modulo: 'Usuarios',
                accion: 'Crear',
                descripcion: 'Se creó el usuario: ' . $usuario->name,
                userId: $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente.',
                'usuario' => $this->transformarUsuario($usuario),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'Datos inválidos.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'No se pudo crear el usuario.',
            ], 422);
        }
    }

    public function cambiarEstado(Request $request, User $usuario): JsonResponse
    {
        try {
            $request->validate([
                'estado' => [
                    'required',
                    'string',
                    'in:pendiente,activo,bloqueado,advertido',
                ],
            ]);

            $nuevoEstado = $request->input('estado');

            if ($request->user()->id === $usuario->id && $nuevoEstado === 'pendiente') {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes cambiar tu propio estado a pendiente.',
                ], 422);
            }

            if ($usuario->id === 1 && $usuario->estado === 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'El administrador principal no puede cambiar de estado.',
                ], 422);
            }

            if (
                $usuario->hasRole('Administrador') &&
                $usuario->estado === 'activo' &&
                $nuevoEstado === 'bloqueado'
            ) {
                $administradoresActivos = User::role('Administrador')
                    ->where('estado', 'activo')
                    ->count();

                if ($administradoresActivos <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede bloquear al último administrador activo.',
                    ], 422);
                }
            }

            $usuario->estado = $nuevoEstado;

            if ($nuevoEstado === 'activo') {
                $usuario->advertencias_count = 0;
                $usuario->mensaje_bloqueo = null;
                $usuario->fecha_bloqueo = null;
                $usuario->ultimo_mensaje_advertencia = null;
                $usuario->fecha_advertencia = null;
            }

            $usuario->save();

            if ($nuevoEstado === 'bloqueado') {
                $usuario->tokens()->delete();

                $this->enviarNotificacionExpulsion(
                    usuario: $usuario,
                    titulo: "Cuenta Bloqueada",
                    mensaje: "Tu cuenta ha sido bloqueada por la administración. Por seguridad, tu sesión ha finalizado."
                );
            }

            $usuario->load(['roles', 'cargo']);

            return response()->json([
                'success' => true,
                'message' => $nuevoEstado === 'activo'
                    ? "Usuario activado correctamente y su historial disciplinario fue restablecido."
                    : "Estado del usuario actualizado correctamente.",
                'usuario' => $this->transformarUsuario($usuario, true),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El estado seleccionado no es válido.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el estado del usuario.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cambiarRol(Request $request, User $usuario): JsonResponse
    {
        try {
            $request->validate([
                'rol' => [
                    'required',
                    'string',
                    'exists:roles,name',
                ],
                'cargo_id' => [
                    'nullable',
                    'integer',
                    'exists:cargos,id',
                ],
            ]);

            $nuevoRol = $request->input('rol');
            $cargoIdInput = $request->input('cargo_id');

            if ($request->user()->id === $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes cambiar tu propio rol.',
                ], 422);
            }

            if ($usuario->id === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'El administrador principal no puede cambiar de rol.',
                ], 422);
            }

            if ($usuario->hasRole('Administrador') && $nuevoRol !== 'Administrador') {
                $administradores = User::role('Administrador')->count();

                if ($administradores <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede quitar el rol al último administrador.',
                    ], 422);
                }
            }

            // Resolver el cargo correspondiente según el rol elegido
            $nuevoCargoId = $this->resolverCargoPorRol($nuevoRol, $cargoIdInput);

            $usuario->syncRoles([$nuevoRol]);
            $usuario->cargo_id = $nuevoCargoId;
            $usuario->save();

            $usuario->tokens()->delete();

            $this->enviarNotificacionExpulsion(
                usuario: $usuario,
                titulo: "Actualización de Privilegios",
                mensaje: "Tus privilegios o rol en la comunidad han sido actualizados por la administración. Por seguridad, tu sesión se cerrará para refrescar tu acceso."
            );

            $usuario->load(['roles', 'cargo']);

            BitacoraService::registrar(
                modulo: 'Usuarios',
                accion: 'Cambiar Rol/Cargo',
                descripcion: "Se actualizó a {$usuario->name} al rol {$nuevoRol} (Cargo: {$usuario->cargo?->nombre})",
                userId: $request->user()?->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Rol y cargo del usuario actualizados correctamente.',
                'usuario' => $this->transformarUsuario($usuario),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'El rol o cargo seleccionado no es válido.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo actualizar el rol del usuario.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function eliminar(Request $request, User $usuario): JsonResponse
    {
        try {
            if ($request->user()->id === $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario.',
                ], 422);
            }

            if ($usuario->id === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'El administrador principal no puede ser eliminado.',
                ], 422);
            }

            if ($usuario->hasRole('Administrador')) {
                $administradores = User::role('Administrador')->count();

                if ($administradores <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar al último administrador.',
                    ], 422);
                }
            }

            $usuario->tokens()->delete();
            $usuario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado correctamente.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el usuario.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Determina el cargo_id obligatorio según el rol asignado:
     * - Secretario: requiere elegir una secretaría válida (excluye 4=Economía y 10=Vecino).
     * - Tesorero: asigna automáticamente 4 (Secretaría de Economía).
     * - Consulta, Administrador, Auditor: asigna automáticamente 10 (Vecino).
     */
    private function resolverCargoPorRol(string $rol, mixed $cargoId): int
    {
        if ($rol === 'Secretario') {
            $id = (int) $cargoId;
            if ($id <= 0 || in_array($id, [4, 10], true)) {
                throw ValidationException::withMessages([
                    'cargo_id' => ['Debe seleccionar una secretaría válida para el rol Secretario.'],
                ]);
            }
            return $id;
        }

        if ($rol === 'Tesorero') {
            return 4; // Secretaría de Economía
        }

        return 10; // Vecino (para Consulta, Administrador y Auditor)
    }

    private function transformarUsuario(User $usuario, bool $incluirSanciones = false): array
    {
        $data = [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'email' => $usuario->email,
            'estado' => $usuario->estado,
            'cargo_id' => $usuario->cargo_id,
            'cargo' => $usuario->cargo ? [
                'id' => $usuario->cargo->id,
                'nombre' => $usuario->cargo->nombre,
            ] : null,
            'advertencias_count' => $usuario->advertencias_count ?? 0,
            'recibir_notificaciones' => $usuario->recibir_notificaciones,
            'telefono' => $usuario->telefono,
            'dni' => $usuario->dni,
            'direccion' => $usuario->direccion,
            'foto' => $usuario->foto,
            'avatar' => $usuario->avatar,
            'ultimo_acceso' => $usuario->ultimo_acceso,
            'bienvenida_vista' => $usuario->bienvenida_vista,
            'roles' => $usuario->roles->map(fn ($rol) => [
                'id' => $rol->id,
                'name' => $rol->name,
            ])->values(),
            'created_at' => $usuario->created_at,
            'updated_at' => $usuario->updated_at,
        ];

        if ($incluirSanciones) {
            $data['mensaje_bloqueo'] = $usuario->mensaje_bloqueo;
            $data['fecha_bloqueo'] = $usuario->fecha_bloqueo;
            $data['ultimo_mensaje_advertencia'] = $usuario->ultimo_mensaje_advertencia;
            $data['fecha_advertencia'] = $usuario->fecha_advertencia;
        }

        return $data;
    }

    private function enviarNotificacionExpulsion(User $usuario, string $titulo, string $mensaje): void
    {
        $fcmTokenRecord = FcmToken::where('user_id', $usuario->id)
            ->where('activo', true)
            ->latest()
            ->first();

        if ($fcmTokenRecord && ! empty($fcmTokenRecord->token)) {
            try {
                $fcmService = app(FcmService::class);
                $fcmService->enviarAlertaExpulsion(
                    token: $fcmTokenRecord->token,
                    titulo: $titulo,
                    mensaje: $mensaje
                );
            } catch (\Exception $e) {
                \Log::error("Error enviando FCM de expulsión: " . $e->getMessage());
            }
        }
    }
}