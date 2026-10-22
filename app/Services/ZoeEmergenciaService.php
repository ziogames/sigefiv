<?php
namespace App\Services;

use App\Models\Emergencia;

class ZoeEmergenciaService
{
public function obtenerNumerosEmergencia(?string $filtro = null): string
{
    $query = Emergencia::where('activo', true);

    if (!empty($filtro)) {
        // Remover acentos y pasar a minúsculas
        $filtroLimpio = mb_strtolower($filtro, 'UTF-8');
        $filtroLimpio = strtr($filtroLimpio, [
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u'
        ]);

        $stopWords = ['cual', 'es', 'el', 'la', 'de', 'los', 'las', 'un', 'una', 'numero', 'dame', 'pasa', 'necesito', 'por', 'favor', 'del'];
        $palabras = array_filter(
            preg_split('/\s+/', $filtroLimpio),
            fn($p) => strlen($p) >= 3 && !in_array($p, $stopWords)
        );

        if (!empty($palabras)) {
            $query->where(function($q) use ($palabras) {
                foreach ($palabras as $p) {
                    $q->orWhereRaw("LOWER(TRANSLATE(entidad, 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU')) LIKE ?", ["%{$p}%"])
                      ->orWhereRaw("LOWER(TRANSLATE(descripcion, 'áéíóúÁÉÍÓÚ', 'aeiouAEIOU')) LIKE ?", ["%{$p}%"]);
                }
            });
        }
    }

    $emergencias = $query->get();

    // Si la búsqueda puntual no arrojó resultados, entrega el directorio completo
    if ($emergencias->isEmpty()) {
        $emergencias = Emergencia::where('activo', true)->get();
    }

    if ($emergencias->isEmpty()) {
        return "No encontré números de auxilio registrados en el sistema.";
    }

    $textoRespuesta = "🚨 Aquí tienes los números oficiales de ayuda:\n\n";
    foreach ($emergencias as $item) {
        $textoRespuesta .= "• **{$item->entidad}**: {$item->numero}";
        if (!empty($item->descripcion)) {
            $textoRespuesta .= " — _{$item->descripcion}_";
        }
        $textoRespuesta .= "\n";
    }

    return $textoRespuesta;
}
}