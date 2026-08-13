<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Services\BitacoraService;
use App\Traits\BitacoraTrait;

class RolController extends Controller
{
      use BitacoraTrait;
    /**
     * Listado de Roles
     */
    public function index()
    {
        $buscar = request('buscar');

        $roles = Role::with([
                'permissions',
                'users'
            ])
            ->when($buscar, function ($query) use ($buscar) {

                $query->where(
                    'name',
                    'like',
                    "%{$buscar}%"
                );

            })
            ->orderBy('name')
            ->paginate(9)
            ->withQueryString();

        return view(
            'roles.index',
            compact(
                'roles',
                'buscar'
            )
        );
    }

    /**
     * Formulario crear Rol
     */
    public function create()
    {
        return view('roles.create');
    }

    /**
     * Guardar Rol
     */
   public function store(Request $request)
{
    $request->validate([

        'name' => 'required|unique:roles,name'

    ]);

    $role = Role::create([

        'name' => $request->name

    ]);

$this->registrarBitacora(

    'Roles',

    'Crear',

    'Se creó el rol: '.$role->name

);
    return redirect()
        ->route('roles.index')
        ->with(
            'success',
            'Rol creado correctamente.'
        );
}
    /**
     * Mostrar Rol
     */
    public function show(Role $role)
    {
        return view(
            'roles.show',
            compact('role')
        );
    }
        public function edit(Role $role)
{
    /*
    |--------------------------------------------------------------------------
    | El Administrador siempre debe tener TODOS los permisos
    |--------------------------------------------------------------------------
    */

    if ($role->name === 'Administrador') {

        $role->syncPermissions(
            Permission::pluck('name')->toArray()
        );

    }


    $grupos = Permission::orderBy('name')
        ->get()
        ->groupBy(function ($permission) {

            return explode(
                '.',
                $permission->name
            )[0];

        });


    return view(
        'roles.edit',
        [
            'role' => $role,
            'grupos' => $grupos,
        ]
    );
}
        /**
     * Actualizar Rol
     */
    public function update(Request $request, Role $role)
{
    $request->validate([

        'name' => 'required|unique:roles,name,' . $role->id,

    ]);

    $role->update([

        'name' => $request->name,

    ]);

    if ($role->name === 'Administrador') {

        $role->syncPermissions(
            Permission::pluck('name')->toArray()
        );

    } else {

        $role->syncPermissions(
            $request->permissions ?? []
        );

    }

   $this->registrarBitacora(

    'Roles',

    'Crear',

    'Se creó el rol: '.$role->name

);
    return redirect()
        ->route('roles.index')
        ->with(
            'success',
            'Rol actualizado correctamente.'
        );
}
        /**
     * Eliminar Rol
     */
    public function destroy(Role $role)
{
    // El rol Administrador nunca puede eliminarse
    if ($role->name === 'Administrador') {

        return back()->with(
            'error',
            'El rol Administrador no puede eliminarse.'
        );

    }

    // No eliminar roles con usuarios asignados
    if ($role->users()->exists()) {

        return back()->with(
            'error',
            'No puede eliminar un rol que tiene usuarios asignados.'
        );

    }

    // Registrar en la bitácora
    $this->registrarBitacora(

        'Roles',

        'Eliminar',

        'Se eliminó el rol: ' . $role->name

    );

    $role->delete();

    return redirect()
        ->route('roles.index')
        ->with(
            'success',
            'Rol eliminado correctamente.'
        );
}
}