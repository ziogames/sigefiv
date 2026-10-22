<?php
namespace App\Services;

use App\Models\User; // Ajusta según tu modelo y relación con cargos

class ZoeDirectivaService
{
public function obtenerInformacionDirectiva(): string
{
    $directivos = User::has('cargo')
        ->with('cargo')
        ->whereHas('cargo', function ($query) {
            $query->where('nombre', 'not ilike', '%residente%')
                  ->where('descripcion', 'not ilike', '%solo lectura%');
        })
        ->get();

    if ($directivos->isEmpty()) {
        return "Actualmente no tengo registrada la conformación de la junta directiva en el sistema.";
    }

    $textoRespuesta = "Esta es la conformación oficial de la Junta Directiva del Grupo Residencial 21:\n\n";
    
    foreach ($directivos as $miembro) {
        $nombre = $miembro->name;
        $cargo = $miembro->cargo->nombre ?? 'Miembro de la Directiva';
        $descripcion = $miembro->cargo->descripcion ?? '';
        
        // Agregamos un emoji decorativo y un salto de línea doble (\n\n) al final de cada ítem
        $textoRespuesta .= "🔹 **{$cargo}**\n👤 {$nombre}";
        if (!empty($descripcion)) {
            $textoRespuesta .= "\n💬 _{$descripcion}_";
        }
        $textoRespuesta .= "\n\n"; // <- Esto separa cada miembro con un espacio en blanco
    }

    // Quitamos los espacios sobrantes del final
    return trim($textoRespuesta);
}
}