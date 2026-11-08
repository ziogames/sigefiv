<?php

namespace App\Services\Zoe;

use Carbon\Carbon;

class ZoeContextDetectorService
{
    protected array $mesesMap = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
        'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
        'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12
    ];

    protected array $stopwords = [
        'hola', 'zoe', 'buenos', 'dias', 'tardes', 'noches', 'por', 'favor', 'porfa',
        'busco', 'buscar', 'buscando', 'necesito', 'recomiendame', 'recomiendas', 'hay',
        'donde', 'puedo', 'comprar', 'compro', 'venden', 'vende', 'servicio', 'contacto',
        'telefono', 'taller', 'tienda', 'bodega', 'delivery', 'vecino', 'vecina', 'grupo',
        'residencial', 'quiero', 'saber', 'el', 'la', 'los', 'las', 'un', 'una', 'de', 'en'
    ];

    public function analizar(string $mensaje, array $contextoPrevio = []): array
    {
        $mensajeTrim = trim($mensaje);
        $texto = mb_strtolower($mensajeTrim, 'UTF-8');
        $textoSinTildes = $this->quitarTildes($texto);

        $ahoraLima = Carbon::now('America/Lima');
        $mesActual = (int) $ahoraLima->month;
        $anioActual = (int) $ahoraLima->year;

        // 1. Detección de Año
        $anio = null;
        if (preg_match('/\b(20\d{2})\b/', $texto, $coincidenciaAnio)) {
            $anio = (int) $coincidenciaAnio[1];
        }

        // 2. Detección de Mes
        $mes = null;
        foreach ($this->mesesMap as $nombreMes => $num) {
            if (preg_match("/\b{$nombreMes}\b/i", $texto)) {
                $mes = $num;
                break;
            }
        }

        $pideMesActual = (bool) preg_match('/\b(este\s+mes|mes\s+actual|mes\s+en\s+curso)\b/i', $textoSinTildes);
        $pideMesPasado = (bool) preg_match('/\b(mes\s+pasado|mes\s+anterior)\b/i', $textoSinTildes);

        if ($mes !== null) {
            $anio = $anio ?? $contextoPrevio['ultimo_anio'] ?? $anioActual;
        } elseif ($pideMesPasado) {
            $mes = ($mesActual === 1) ? 12 : $mesActual - 1;
            $anio = $anio ?? (($mesActual === 1) ? $anioActual - 1 : $anioActual);
        } elseif ($pideMesActual) {
            $mes = $mesActual;
            $anio = $anio ?? $anioActual;
        } else {
            // Heredar del contexto previo si no especificó fecha
            $mes = $contextoPrevio['ultimo_mes'] ?? $mesActual;
            $anio = $anio ?? $contextoPrevio['ultimo_anio'] ?? $anioActual;
        }

        // 3. Detección de Categoría de Emprendimiento
        $categoria = null;
        if (preg_match('/\b(comida|restaurante|menu|almuerzo|cena|postre|torta|hamburguesa|chifa|polleria)\b/i', $textoSinTildes)) {
            $categoria = 'Comida';
        } elseif (preg_match('/\b(servicio|gasfitero|gasfiteria|tuberia|electricista|cerrajero|mantenimiento|reparacion)\b/i', $textoSinTildes)) {
            $categoria = 'Servicios';
        } elseif (preg_match('/\b(belleza|peluqueria|barberia|corte|manicure|pedicure|peinado)\b/i', $textoSinTildes)) {
            $categoria = 'Belleza';
        } elseif (preg_match('/\b(bodega|tienda|abarrotes|bazar|libreria|calzado)\b/i', $textoSinTildes)) {
            $categoria = 'Comercio';
        }

        // 4. Clasificación de Dominio e Intención para el Router de n8n
        $dominio = 'general';
        $intencion = 'casual';

        $esEmergencia = (bool) preg_match('/\b(emergencia|serenazgo|policia|bomberos|ambulancia|samu|auxilio|robo|accidente|incendio)\b/i', $textoSinTildes);
        $esAsamblea = (bool) preg_match('/\b(asamblea|reunion|convocatoria|acta|estatuto|articulo|orden del dia)\b/i', $textoSinTildes);
        $esEmprendimiento = (bool) preg_match('/\b(donde venden|quien vende|quien arregla|recomiendame|busco|emprendimiento|negocio)\b/i', $textoSinTildes) || ($categoria !== null);
        $esFinanzas = (bool) preg_match('/\b(ingreso|ingresos|egreso|egresos|gasto|gastos|saldo|caja|balance|resumen|movimientos|fondos|cuota)\b/i', $textoSinTildes);

        if ($esEmergencia) {
            $dominio = 'emergencias';
            $intencion = 'emergencia';
        } elseif ($esAsamblea) {
            $dominio = 'asamblea';
            $intencion = preg_match('/\b(estatuto|articulo)\b/i', $textoSinTildes) ? 'estatutos' : 'asamblea_programada';
        } elseif ($esFinanzas) {
            $dominio = 'finanzas';
            if (preg_match('/\b(saldo|caja|fondos)\b/i', $textoSinTildes)) {
                $intencion = 'saldo_caja';
            } elseif (preg_match('/\b(resumen|balance|cierre)\b/i', $textoSinTildes)) {
                $intencion = 'resumen_periodo';
            } elseif (preg_match('/\b(ingreso|ingresos)\b/i', $textoSinTildes)) {
                $intencion = 'ingresos';
            } elseif (preg_match('/\b(egreso|egresos|gasto|gastos)\b/i', $textoSinTildes)) {
                $intencion = 'egresos';
            } else {
                $intencion = 'movimientos';
            }
        } elseif ($esEmprendimiento) {
            $dominio = 'emprendimientos';
            $intencion = 'buscar_emprendimiento';
        }

        // 5. Palabras de búsqueda limpias para el directorio
        $palabras = array_filter(
            explode(' ', preg_replace('/[^a-z0-9\s]/', '', $textoSinTildes)),
            fn($p) => mb_strlen($p) >= 3 && !in_array($p, $this->stopwords, true)
        );

        return [
            'dominio' => $dominio,
            'intencion' => $intencion,
            'mes' => $mes,
            'anio' => $anio,
            'categoria' => $categoria,
            'palabras_busqueda' => implode(' ', array_values($palabras)),
            'fecha_actual' => $ahoraLima->locale('es')->translatedFormat('d \d\e F \d\e Y'),
            'hora_actual' => $ahoraLima->format('h:i A'),
        ];
    }

    protected function quitarTildes(string $cadena): string
    {
        $originales = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'];
        $reemplazos = ['a', 'e', 'i', 'o', 'u', 'a', 'e', 'i', 'o', 'u', 'n', 'n'];
        return str_replace($originales, $reemplazos, $cadena);
    }
}