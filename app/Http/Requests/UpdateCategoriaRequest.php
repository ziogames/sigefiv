<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('categoria')->id;

        return [

            'nombre' => 'required|max:100|unique:categorias,nombre,' . $id,

            'tipo' => 'required|in:Ingreso,Egreso',

            'icono' => 'required',

            'color' => 'required',

            'activo' => 'required|boolean',

            'orden' => 'nullable|integer|min:0',

        ];
    }
}