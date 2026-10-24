<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    /**
     * Listado de roles para Android.
     */
    public function index(Request $request)
    {
        try {
            $buscar = $request->input('buscar');

            $roles = Role::withCount('permissions')
                ->when($buscar, function ($query) use ($buscar) {
                    $query->where('name', 'like', "%{$buscar}%");
                })
                ->orderBy('id')
                ->get()
                ->map(function ($rol) {
                    return [
                        'id' => (int) $rol->id,
                        'name' => (string) $rol->name,
                        'users_count' => DB::table('model_has_roles')
                            ->where('role_id', $rol->id)
                            ->count(),
                        'permissions_count' => (int) $rol->permissions_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'roles' => $roles,
                'message' => 'Roles obtenidos correctamente',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'roles' => [],
                'message' => 'Error al obtener roles',
            ], 500);
        }
    }

    /**
     * Permisos disponibles para el formulario Nuevo Rol.
     * Todos comienzan sin asignar.
     */
    public function permisos()
    {
        try {
            $permisosAgrupados = [];

            $permisos = Permission::where('guard_name', 'web')
                ->orderBy('name')
                ->get();

            foreach ($permisos as $permiso) {
                $partes = explode('.', $permiso->name);
                $categoria = count($partes) > 1
                    ? ucfirst($partes[0])
                    : 'General';

                $permisosAgrupados[$categoria][] = [
                    'id' => (int) $permiso->id,
                    'name' => (string) $permiso->name,
                    'asignado' => false,
                ];
            }

            return response()->json([
                'success' => true,
                'permisos_agrupados' => $permisosAgrupados,
                'message' => 'Permisos obtenidos correctamente',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'permisos_agrupados' => [],
                'message' => 'Error al obtener permisos',
            ], 500);
        }
    }

    /**
     * Detalle de un rol con todos los permisos agrupados.
     */
    public function show($id)
    {
        try {
            $rol = Role::findOrFail($id);

            $todosLosPermisos = Permission::where('guard_name', 'web')
                ->orderBy('name')
                ->get();

            $permisosRol = $rol->permissions()
                ->pluck('name')
                ->toArray();

            $permisosAgrupados = [];

            foreach ($todosLosPermisos as $permiso) {
                $partes = explode('.', $permiso->name);
                $categoria = count($partes) > 1
                    ? ucfirst($partes[0])
                    : 'General';

                $permisosAgrupados[$categoria][] = [
                    'id' => (int) $permiso->id,
                    'name' => (string) $permiso->name,
                    'asignado' => in_array(
                        $permiso->name,
                        $permisosRol,
                        true
                    ),
                ];
            }

            return response()->json([
                'success' => true,
                'rol' => [
                    'id' => (int) $rol->id,
                    'name' => (string) $rol->name,
                    'users_count' => DB::table('model_has_roles')
                        ->where('role_id', $rol->id)
                        ->count(),
                    'permissions_count' => count($permisosRol),
                    'permisos_agrupados' => $permisosAgrupados,
                ],
                'message' => 'Rol obtenido correctamente',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'rol' => null,
                'message' => 'Error al obtener detalle del rol',
            ], 500);
        }
    }

    /**
     * Crear un rol y asignarle los permisos seleccionados.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web'),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('permissions', 'name')
                    ->where('guard_name', 'web'),
            ],
        ]);

        try {
            $rol = DB::transaction(function () use ($datos) {
                $nuevoRol = Role::create([
                    'name' => trim($datos['name']),
                    'guard_name' => 'web',
                ]);

                $nuevoRol->syncPermissions(
                    $datos['permissions'] ?? []
                );

                return $nuevoRol;
            });

            app(PermissionRegistrar::class)
                ->forgetCachedPermissions();

            BitacoraService::registrar(
                'Roles',
                'Crear',
                'Se creó el rol: ' . $rol->name
            );

            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente',
                'id' => $rol->id,
            ], 201);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el rol',
            ], 500);
        }
    }

    /**
     * Actualizar nombre y permisos de un rol.
     */
    public function update(Request $request, $id)
    {
        $rol = Role::findOrFail($id);

        $esAdministrador = mb_strtolower($rol->name) === 'administrador';

        $reglas = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($rol->id),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'required',
                'string',
                'distinct',
                Rule::exists('permissions', 'name')
                    ->where('guard_name', 'web'),
            ],
        ];

        $datos = $request->validate($reglas);

        if ($esAdministrador && trim($datos['name']) !== $rol->name) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar el nombre del rol Administrador',
            ], 422);
        }

        try {
            DB::transaction(function () use ($rol, $datos, $esAdministrador) {
                if (!$esAdministrador) {
                    $rol->name = trim($datos['name']);
                    $rol->save();

                    if (array_key_exists('permissions', $datos)) {
                        $rol->syncPermissions($datos['permissions'] ?? []);
                    }
                } else {
                    // Administrador conserva todos los permisos existentes.
                    $rol->syncPermissions(
                        Permission::where('guard_name', 'web')
                            ->pluck('name')
                            ->toArray()
                    );
                }
            });

            app(PermissionRegistrar::class)
                ->forgetCachedPermissions();

            BitacoraService::registrar(
                'Roles',
                'Actualizar',
                'Se actualizó el rol: ' . $rol->name
            );

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el rol',
            ], 500);
        }
    }

    /**
     * Eliminar un rol sin usuarios asignados.
     */
    public function destroy($id)
    {
        try {
            $rol = Role::findOrFail($id);

            if (mb_strtolower($rol->name) === 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'El rol Administrador no puede eliminarse',
                ], 422);
            }

            $tieneUsuarios = DB::table('model_has_roles')
                ->where('role_id', $rol->id)
                ->exists();

            if ($tieneUsuarios) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un rol que tiene usuarios asignados',
                ], 422);
            }

            $nombreRol = $rol->name;

            DB::transaction(function () use ($rol) {
                $rol->syncPermissions([]);
                $rol->delete();
            });

            app(PermissionRegistrar::class)
                ->forgetCachedPermissions();

            BitacoraService::registrar(
                'Roles',
                'Eliminar',
                'Se eliminó el rol: ' . $nombreRol
            );

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado correctamente',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el rol',
            ], 500);
        }
    }
}
