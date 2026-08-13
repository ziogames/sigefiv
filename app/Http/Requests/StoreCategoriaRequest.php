<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'nombre' => 'required|max:100|unique:categorias,nombre',

            'tipo' => 'required|in:Ingreso,Egreso',

            'icono' => 'required',

            'color' => 'required',

            'activo' => 'required|boolean',

            'orden' => 'nullable|integer|min:0',

        ];
    }
}