<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUsuarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario),
            ],

            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|exists:roles,name',
            
            // <-- Agregamos esta línea para validar el cargo de la directiva -->
            'cargo_id' => 'nullable|exists:cargos,id',
        ];
    }

    /**
     * Mensajes personalizados.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo válido.',
            'email.unique' => 'Ese correo ya existe.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            
            // Opcional: puedes agregar un mensaje personalizado para el cargo si gustas
            'cargo_id.exists' => 'El cargo seleccionado no es válido.',
        ];
    }
}