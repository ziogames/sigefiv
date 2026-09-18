<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PerfilController extends Controller
{
    /**
     * Mostrar el perfil del usuario autenticado.
     */
    public function index(): View
    {
        $usuario = auth()->user();

        return view(
            'perfil.index',
            compact('usuario')
        );
    }

    /**
     * Actualizar información personal.
     */
    public function update(Request $request): RedirectResponse
    {
        $usuario = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'seudonimo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:20',
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


        /*
        |--------------------------------------------------------------------------
        | Datos que todos los usuarios pueden modificar
        |--------------------------------------------------------------------------
        */

       $datos = [

    'name' => $request->name,

    'seudonimo' => $request->seudonimo,

    'telefono' => $request->telefono,

    'dni' => $request->dni,

    'direccion' => $request->direccion,

];

        /*
        |--------------------------------------------------------------------------
        | Correo electrónico
        |--------------------------------------------------------------------------
        |
        | Los usuarios vinculados con Google deben conservar el correo
        | utilizado por su cuenta de Google.
        |
        | Los usuarios tradicionales de SIGEFIV sí pueden modificarlo.
        |
        */

        if (!$usuario->google_id) {

            $request->validate([

                'email' => [
                    'required',
                    'email',
                    'max:255',
                    'unique:users,email,' . $usuario->id,
                ],

            ]);

            $datos['email'] = $request->email;
        }


        /*
        |--------------------------------------------------------------------------
        | Actualizar usuario
        |--------------------------------------------------------------------------
        */

        $usuario->update($datos);


        return back()->with(
            'success',
            'Información actualizada correctamente.'
        );
    }

    /**
     * Cambiar contraseña.
     *
     * Las cuentas vinculadas con Google no pueden
     * cambiar su contraseña desde SIGEFIV.
     */
    public function password(Request $request): RedirectResponse
    {
        $usuario = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Cuenta vinculada con Google
        |--------------------------------------------------------------------------
        */

        if ($usuario->google_id) {

            return back()->with(
                'error',
                'Tu cuenta utiliza Google para iniciar sesión. La contraseña se administra desde Google.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validación
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'password' => [
                'required',
                'confirmed',
                'min:8',
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Actualizar contraseña
        |--------------------------------------------------------------------------
        */

        $usuario->update([

            'password' => Hash::make(
                $request->password
            ),

        ]);


        return back()->with(
            'success',
            'Contraseña actualizada correctamente.'
        );
    }

    /**
     * Actualizar fotografía del usuario.
     */
    public function foto(Request $request): RedirectResponse
    {
        $request->validate([

            'foto' => [
                'required',
                'image',
                'max:2048',
            ],

        ]);

        $usuario = auth()->user();


        /*
        |--------------------------------------------------------------------------
        | Eliminar fotografía local anterior
        |--------------------------------------------------------------------------
        |
        | Si la fotografía anterior es una ruta local,
        | la eliminamos del almacenamiento.
        |
        | Si es una URL externa, como una fotografía de Google,
        | no intentamos eliminarla.
        |
        */

        if (
            $usuario->foto &&
            !filter_var(
                $usuario->foto,
                FILTER_VALIDATE_URL
            )
        ) {

            Storage::disk('public')->delete(
                $usuario->foto
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Guardar nueva fotografía
        |--------------------------------------------------------------------------
        */

        $ruta = $request
            ->file('foto')
            ->store(
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