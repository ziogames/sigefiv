<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use App\Models\Cargo; // <-- 1. Importamos el modelo Cargo
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Services\BitacoraService;
use App\Services\ZoeModeracionService; 
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

        $usuarios = User::with(['roles', 'cargo']) // <-- Opcional: cargamos la relación cargo para mostrarla si gustas
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
        $cargos = Cargo::orderBy('nombre')->get(); // <-- 2. Obtenemos los cargos de la directiva

        return view(
            'usuarios.create',
            compact('roles', 'cargos') // <-- Los pasamos a la vista
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
            'password' => Hash::make($request->password),
            'cargo_id' => $request->cargo_id, // <-- 3. Guardamos el cargo seleccionado (puede ser null)
            'estado' => 'activo',
            'recibir_notificaciones' => true,
        ]);

        $usuario->assignRole($request->role);

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
        $cargos = Cargo::orderBy('nombre')->get(); // <-- 4. Obtenemos los cargos para la vista de edición

        return view(
            'usuarios.edit',
            [
                'usuario' => $usuario,
                'roles' => $roles,
                'cargos' => $cargos, // <-- Los pasamos a la vista
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
            'cargo_id' => $request->cargo_id, // <-- 5. Actualizamos el cargo de la directiva
        ]);

        if ($request->filled('password')) {
            $usuario->update([
                'password' => Hash::make($request->password),
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

    // ... (los demás métodos como cambiarEstado y destroy se quedan igual)
}