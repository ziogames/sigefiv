<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PerfilController extends Controller
{
    public function index()
    {
        $usuario = auth()->user();

        return view(
            'perfil.index',
            compact('usuario')
        );
    }

    public function update(Request $request)
    {
        $request->validate([

            'name' => 'required|max:255',

            'email' => 'required|email|unique:users,email,' . auth()->id(),

            'telefono' => 'nullable|max:20',

            'dni' => 'nullable|max:20',

            'direccion' => 'nullable|max:255',

        ]);

        auth()->user()->update([

            'name' => $request->name,

            'email' => $request->email,

            'telefono' => $request->telefono,

            'dni' => $request->dni,

            'direccion' => $request->direccion,

        ]);

        return back()->with(
            'success',
            'Información actualizada correctamente.'
        );
    }

    public function password(Request $request)
    {
        $request->validate([

            'password' => 'required|confirmed|min:8',

        ]);

        auth()->user()->update([

            'password' => Hash::make($request->password)

        ]);

        return back()->with(
            'success',
            'Contraseña actualizada correctamente.'
        );
    }

    public function foto(Request $request)
    {
        $request->validate([

            'foto' => 'required|image|max:2048',

        ]);

        $usuario = auth()->user();

        if ($usuario->foto) {

            Storage::disk('public')->delete($usuario->foto);

        }

        $ruta = $request->file('foto')->store(
            'usuarios',
            'public'
        );

        $usuario->update([

            'foto' => $ruta,

        ]);

        return back()->with(
            'success',
            'Fotografía actualizada correctamente.'
        );
    }
}