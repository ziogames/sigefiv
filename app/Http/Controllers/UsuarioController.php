<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Services\BitacoraService;

use App\Traits\BitacoraTrait;
class UsuarioController extends Controller

{
    use BitacoraTrait;
    /**
     * Listado de usuarios
     */
    public function index()
    {
        $buscar = request('buscar');

        $usuarios = User::with('roles')
            ->when($buscar, function ($query) use ($buscar) {

                $query->where('name', 'like', "%{$buscar}%")
                    ->orWhere('email', 'like', "%{$buscar}%");

            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view(
            'usuarios.index',
            compact(
                'usuarios',
                'buscar'
            )
        );
    }

    /**
     * Formulario crear usuario
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view(
            'usuarios.create',
            compact('roles')
        );
    }

    /**
     * Guardar usuario
     */
    public function store(StoreUsuarioRequest $request)
    {
        $usuario = User::create([

            'name' => $request->name,

            'email' => $request->email,

            'password' => Hash::make(
                $request->password
            ),
            

        ]);
                        $this->registrarBitacora(
                    'Usuarios',
                    'Crear',
                    'Se creó el usuario: '.$usuario->name
                );

        $usuario->assignRole(
            $request->role
        );

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario creado correctamente.'
            );
    }

        /**
     * Mostrar usuario
     */
    public function show(User $usuario)
    {
        return view(
            'usuarios.show',
            compact('usuario')
        );
    }

    /**
     * Formulario editar usuario
     */
    public function edit(User $usuario)
    {
        $roles = Role::orderBy('name')->get();

        return view(
            'usuarios.edit',
            [
                'usuario' => $usuario,
                'roles' => $roles,
            ]
        );
    }

        /**
     * Actualizar usuario
     */
    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        // El administrador principal nunca puede perder su rol
        if (
            $usuario->id === 1 &&
            $request->role !== 'Administrador'
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'El Administrador principal no puede perder el rol Administrador.'
                );
        }

        $usuario->update([

            'name'  => $request->name,

            'email' => $request->email,

        ]);

$this->registrarBitacora(
    'Usuarios',
    'Crear',
    'Se creó el usuario: '.$usuario->name
);
        if ($request->filled('password')) {

            $usuario->update([

                'password' => Hash::make($request->password),

            ]);

        }

        // Actualizar rol
        $usuario->syncRoles([
            $request->role
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario actualizado correctamente.'
            );
    }
        /**
     * Eliminar usuario
     */
    public function destroy(User $usuario)
    {
        // No permitir eliminar al administrador principal
        if ($usuario->id === 1) {

            return back()->with(
                'error',
                'El Administrador principal no puede eliminarse.'
            );

        }

        // No permitir eliminar el último administrador
        if (
            $usuario->hasRole('Administrador') &&
            User::role('Administrador')->count() === 1
        ) {

            return back()->with(
                'error',
                'Debe existir al menos un usuario con el rol Administrador.'
            );

        }
            $this->registrarBitacora(
    'Usuarios',
    'Crear',
    'Se creó el usuario: '.$usuario->name
);
    
        $usuario->delete();

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario eliminado correctamente.'
            );
    }
}