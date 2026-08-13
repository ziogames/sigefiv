<?php

namespace App\Services;

use App\Models\Categoria;

class CategoriaService
{
    public static function guardar(array $datos): Categoria
    {
        // Generar el código automáticamente
        $datos['codigo'] = Categoria::siguienteCodigo($datos['tipo']);

        return Categoria::create($datos);
    }

    public static function actualizar(
        Categoria $categoria,
        array $datos
    ): Categoria {

        // Si el tipo cambia (Ingreso ↔ Egreso),
        // se genera un nuevo código.
        if ($categoria->tipo !== $datos['tipo']) {

            $datos['codigo'] = Categoria::siguienteCodigo($datos['tipo']);

        }

        $categoria->update($datos);

        return $categoria;
    }
}