<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    /**
     * Listado de Roles para Android
     */
    public function index(Request $request)
    {
        try {
            $buscar = $request->buscar;

            $roles = Role::withCount('permissions')
                ->when($buscar, function ($query) use ($buscar) {
                    $query->where('name', 'like', "%{$buscar}%");
                })
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($rol) {
                    $cantidadUsuarios = DB::table('model_has_roles')
                        ->where('role_id', $rol->id)
                        ->count();

                    return [
                        'id' => (int) $rol->id,
                        'name' => (string) $rol->name,
                        'users_count' => $cantidadUsuarios,
                        'permissions_count' => $rol->permissions_count ?? 0,
                    ];
                });

            return response()->json([
                'success' => true,
                'roles' => $roles,
                'message' => 'Roles obtenidos correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'roles' => [],
                'message' => 'Error al obtener roles: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Detalle del rol para edición (con permisos agrupados)
     */
    public function show($id)
    {
        try {
            $rol = Role::findOrFail($id);

            // Obtener todos los permisos del sistema
            $todosLosPermisos = Permission::orderBy('name')->get();

            // Obtener nombres de los permisos asignados a este rol
            $permisosRol = $rol->permissions()->pluck('name')->toArray();

            // Agrupar permisos por su prefijo antes del punto
            $permisosAgrupados = [];
            foreach ($todosLosPermisos as $permiso) {
                $partes = explode('.', $permiso->name);
                $categoria = count($partes) > 1 ? ucfirst($partes[0]) : 'General';

                $permisosAgrupados[$categoria][] = [
                    'id' => (int) $permiso->id,
                    'name' => (string) $permiso->name,
                    'asignado' => in_array($permiso->name, $permisosRol, true),
                ];
            }

            $cantidadUsuarios = DB::table('model_has_roles')
                ->where('role_id', $rol->id)
                ->count();

            return response()->json([
                'success' => true,
                'rol' => [
                    'id' => (int) $rol->id,
                    'name' => (string) $rol->name,
                    'users_count' => $cantidadUsuarios,
                    'permissions_count' => count($permisosRol),
                    'permisos_agrupados' => $permisosAgrupados,
                ],
                'message' => 'Rol obtenido correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'rol' => null,
                'message' => 'Error al obtener detalle del rol: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar nombre y permisos del rol
     */
    public function update(Request $request, $id)
    {
        try {
            $rol = Role::findOrFail($id);

            $request->validate([
                'name' => 'required|string|max:255',
                'permissions' => 'nullable|array'
            ]);

            $rol->name = $request->name;
            $rol->save();

            if ($request->has('permissions')) {
                $rol->syncPermissions($request->permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar rol: ' . $e->getMessage()
            ], 500);
        }
    }
}