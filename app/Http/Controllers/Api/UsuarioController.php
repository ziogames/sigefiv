<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UsuarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $buscar = $request->input('buscar');

            $usuarios = User::with('roles')
                ->when($buscar, function ($query) use ($buscar) {
                    $query->where(function ($q) use ($buscar) {
                        $q->where('name', 'like', "%{$buscar}%")
                          ->orWhere('email', 'like', "%{$buscar}%");
                    });
                })
                ->orderBy('name')
                ->get();

            $resultado = $usuarios->map(function (User $usuario) {
                return [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'estado' => $usuario->estado,
                    'recibir_notificaciones' => $usuario->recibir_notificaciones,
                    'telefono' => $usuario->telefono,
                    'dni' => $usuario->dni,
                    'direccion' => $usuario->direccion,
                    'foto' => $usuario->foto,
                    'avatar' => $usuario->avatar,
                    'ultimo_acceso' => $usuario->ultimo_acceso,
                    'bienvenida_vista' => $usuario->bienvenida_vista,
                    'roles' => $usuario->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'name' => $rol->name,
                        ];
                    })->values(),
                    'created_at' => $usuario->created_at,
                    'updated_at' => $usuario->updated_at,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Usuarios obtenidos correctamente.',
                'usuarios' => $resultado,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener los usuarios.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cambiarEstado(Request $request, User $usuario): JsonResponse
    {
        try {
            $request->validate([
                'estado' => [
                    'required',
                    'string',
                    'in:pendiente,activo,bloqueado',
                ],
            ]);

            $nuevoEstado = $request->input('estado');

            // El administrador no puede cambiar su propio estado a pendiente.
            if (
                $request->user()->id === $usuario->id &&
                $nuevoEstado === 'pendiente'
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes cambiar tu propio estado a pendiente.',
                ], 422);
            }

            // El administrador principal no puede cambiar de estado.
            if ($usuario->id === 1 && $usuario->estado === 'activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'El administrador principal no puede cambiar de estado.',
                ], 422);
            }

            // No permitir bloquear al último administrador activo.
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

            $usuario->update([
                'estado' => $nuevoEstado,
            ]);

            $usuario->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'Estado del usuario actualizado correctamente.',
                'usuario' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'estado' => $usuario->estado,
                    'recibir_notificaciones' => $usuario->recibir_notificaciones,
                    'telefono' => $usuario->telefono,
                    'dni' => $usuario->dni,
                    'direccion' => $usuario->direccion,
                    'foto' => $usuario->foto,
                    'avatar' => $usuario->avatar,
                    'ultimo_acceso' => $usuario->ultimo_acceso,
                    'bienvenida_vista' => $usuario->bienvenida_vista,
                    'roles' => $usuario->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'name' => $rol->name,
                        ];
                    })->values(),
                    'created_at' => $usuario->created_at,
                    'updated_at' => $usuario->updated_at,
                ],
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
                    'in:Administrador,Tesorero,Secretario,Consulta',
                ],
            ]);

            $nuevoRol = $request->input('rol');

            // No permitir cambiar el propio rol.
            if ($request->user()->id === $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes cambiar tu propio rol.',
                ], 422);
            }

            // El administrador principal no puede cambiar de rol.
            if ($usuario->id === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'El administrador principal no puede cambiar de rol.',
                ], 422);
            }

            $esAdministrador = $usuario->hasRole('Administrador');

            // No permitir quitar el rol de Administrador al último administrador.
            if (
                $esAdministrador &&
                $nuevoRol !== 'Administrador'
            ) {
                $administradores = User::role('Administrador')->count();

                if ($administradores <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede quitar el rol al último administrador.',
                    ], 422);
                }
            }

            $usuario->syncRoles([$nuevoRol]);

            $usuario->load('roles');

            return response()->json([
                'success' => true,
                'message' => 'Rol del usuario actualizado correctamente.',
                'usuario' => [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'email' => $usuario->email,
                    'estado' => $usuario->estado,
                    'recibir_notificaciones' => $usuario->recibir_notificaciones,
                    'telefono' => $usuario->telefono,
                    'dni' => $usuario->dni,
                    'direccion' => $usuario->direccion,
                    'foto' => $usuario->foto,
                    'avatar' => $usuario->avatar,
                    'ultimo_acceso' => $usuario->ultimo_acceso,
                    'bienvenida_vista' => $usuario->bienvenida_vista,
                    'roles' => $usuario->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'name' => $rol->name,
                        ];
                    })->values(),
                    'created_at' => $usuario->created_at,
                    'updated_at' => $usuario->updated_at,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'El rol seleccionado no es válido.',
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
            // No permitir eliminarse a sí mismo.
            if ($request->user()->id === $usuario->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario.',
                ], 422);
            }

            // El administrador principal no puede eliminarse.
            if ($usuario->id === 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'El administrador principal no puede ser eliminado.',
                ], 422);
            }

            // No permitir eliminar al último administrador.
            if ($usuario->hasRole('Administrador')) {
                $administradores = User::role('Administrador')->count();

                if ($administradores <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se puede eliminar al último administrador.',
                    ], 422);
                }
            }

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
}