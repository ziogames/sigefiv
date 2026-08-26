<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

                $query->where(function ($q) use ($buscar) {

                    $q->where('name', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");

                });

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

            'estado' => 'activo',

            'recibir_notificaciones' => true,

        ]);

        $usuario->assignRole(
            $request->role
        );

        $this->registrarBitacora(
            'Usuarios',
            'Crear',
            'Se creó el usuario: ' . $usuario->name
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
    public function update(
        UpdateUsuarioRequest $request,
        User $usuario
    ) {
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

            'name' => $request->name,

            'email' => $request->email,

        ]);

        if ($request->filled('password')) {

            $usuario->update([

                'password' => Hash::make(
                    $request->password
                ),

            ]);

        }

        // Actualizar rol
        $usuario->syncRoles([
            $request->role
        ]);

        $this->registrarBitacora(
            'Usuarios',
            'Editar',
            'Se actualizó el usuario: ' . $usuario->name
        );

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Usuario actualizado correctamente.'
            );
    }

    /**
     * Cambiar estado del usuario
     */
    public function cambiarEstado(
        Request $request,
        User $usuario
    ): RedirectResponse {
        $request->validate([
            'estado' => [
                'required',
                'in:pendiente,activo,bloqueado',
            ],
        ]);

        // El administrador principal no puede ser bloqueado
        if (
            $usuario->id === 1 &&
            $request->estado === 'bloqueado'
        ) {
            return back()
                ->with(
                    'error',
                    'El Administrador principal no puede ser bloqueado.'
                );
        }

        // No permitir bloquear al último administrador
        if (
            $usuario->hasRole('Administrador') &&
            $request->estado === 'bloqueado' &&
            User::role('Administrador')
                ->where('estado', 'activo')
                ->count() === 1
        ) {
            return back()
                ->with(
                    'error',
                    'Debe existir al menos un Administrador activo.'
                );
        }

        $estadoAnterior = $usuario->estado;

        $usuario->update([
            'estado' => $request->estado,
        ]);

        $descripciones = [
            'pendiente' => 'pendiente',
            'activo' => 'activo',
            'bloqueado' => 'bloqueado',
        ];

        $estadoNuevo = $descripciones[$request->estado];

        $this->registrarBitacora(
            'Usuarios',
            'Editar',
            'Se cambió el estado del usuario '
            . $usuario->name
            . ' de '
            . $estadoAnterior
            . ' a '
            . $estadoNuevo
        );

        return redirect()
            ->route('usuarios.index')
            ->with(
                'success',
                'Estado del usuario actualizado correctamente.'
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
            'Eliminar',
            'Se eliminó el usuario: ' . $usuario->name
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