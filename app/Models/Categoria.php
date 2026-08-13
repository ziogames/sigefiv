<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = [

        'codigo',

        'nombre',

        'tipo',

        'icono',

        'color',

        'activo',

        'orden',

    ];

    protected $casts = [

        'activo' => 'boolean',

    ];

    /**
     * Obtiene el siguiente código automáticamente.
     *
     * ING001
     * ING002
     * EGR001
     * EGR002
     */
    public static function siguienteCodigo(string $tipo): string
    {
        $prefijo = $tipo === 'Ingreso'
            ? 'ING'
            : 'EGR';

        $ultimo = self::where('tipo', $tipo)
            ->where('codigo', 'like', $prefijo.'%')
            ->orderByDesc('codigo')
            ->first();

        if (!$ultimo) {

            return $prefijo.'001';

        }

        $numero = (int) substr($ultimo->codigo, 3);

        return $prefijo.str_pad(
            $numero + 1,
            3,
            '0',
            STR_PAD_LEFT
        );
    }
}