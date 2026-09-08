<?php

namespace App\Services\ConsultaInteligente;

use App\Models\Movimiento;

class ConsultaInteligenteConceptoService
{
    public function detectarConceptos(
        string $texto,
        string $operacion = 'show',
        ?array $fecha = null
    ): array {

        /*
        |--------------------------------------------------------------------------
        | CONCEPTOS DEL PERÍODO
        |--------------------------------------------------------------------------
        |
        | Cuando existe mes y año, trabajamos primero con los conceptos que
        | realmente aparecen en ese período.
        |
        */

        $consultaConceptos =
            Movimiento::query()
                ->whereNotNull('concepto')
                ->where('concepto', '!=', '');

        if (
            is_array($fecha) &&
            !empty($fecha['mes']) &&
            !empty($fecha['anio'])
        ) {
            $consultaConceptos
                ->whereMonth(
                    'fecha',
                    (int) $fecha['mes']
                )
                ->whereYear(
                    'fecha',
                    (int) $fecha['anio']
                );
        }

        $conceptosDisponibles =
            $consultaConceptos
                ->select('concepto')
                ->distinct()
                ->pluck('concepto');

        if ($conceptosDisponibles->isEmpty()) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALIZAR
        |--------------------------------------------------------------------------
        */

        $normalizar = function (string $valor): string {

            $valor = mb_strtolower(
                trim($valor),
                'UTF-8'
            );

            return strtr(
                $valor,
                [
                    'á' => 'a',
                    'é' => 'e',
                    'í' => 'i',
                    'ó' => 'o',
                    'ú' => 'u',
                    'ü' => 'u',
                    'ñ' => 'n',
                ]
            );
        };

        $textoNormalizado =
            $normalizar($texto);

        /*
        |--------------------------------------------------------------------------
        | OBTENER TÉRMINOS SOLICITADOS
        |--------------------------------------------------------------------------
        */

        $textoBusqueda =
            preg_replace(
                '/\b(?:a|al|dame|darme|puedes|puede|suma|lista|listado|mostrar|muéstrame|muestrame|detalle|detallame|detállame|enséñame|ensename|cuanto|cuánto|cuáles|cuales|fueron|fue|pagamos|pago|pagos|gastamos|gasto|gastos|ingreso|ingresos|egreso|egresos|servicio|servicios|por|de|del|la|el|los|las|en|un|una|y|o|que|qué|durante|mes|meses|año|ano|enero|febrero|marzo|abril|mayo|junio|julio|agosto|setiembre|septiembre|octubre|noviembre|diciembre)\b/iu',
                ' ',
                $textoNormalizado
            );

        $palabras =
            preg_split(
                '/[^a-z0-9áéíóúüñ]+/u',
                $textoBusqueda ?? '',
                -1,
                PREG_SPLIT_NO_EMPTY
            );

        /*
        |--------------------------------------------------------------------------
        | PALABRAS SIGNIFICATIVAS
        |--------------------------------------------------------------------------
        */

        $palabrasSignificativas = [];

        foreach ($palabras as $palabra) {

            if (
                preg_match('/^\d{4}$/', $palabra)
            ) {
                continue;
            }

            if (
                preg_match('/^\d{1,2}$/', $palabra)
            ) {
                continue;
            }

            if (
                mb_strlen($palabra) < 2
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | PALABRAS QUE DESCRIBEN LA ESTRUCTURA DE LA CONSULTA
            |--------------------------------------------------------------------------
            |
            | No representan un concepto real.
            |
            */

            $palabrasEstructurales = [
                'mayor',
                'mayores',
                'maximo',
                'máximo',
                'maximos',
                'máximos',
                'maxima',
                'máxima',
                'maximas',
                'máximas',

                'menor',
                'menores',
                'minimo',
                'mínimo',
                'minimos',
                'mínimos',
                'minima',
                'mínima',
                'minimas',
                'mínimas',

                'nuestro',
                'nuestra',
                'nuestros',
                'nuestras',

                'tuvimos',
                'tuvieron',
                'tuvimos',
                'tenemos',
                'tenia',
                'tenía',
                'hubo',
                'hubieron',

                'fueron',
                'fue',

                'cual',
                'cuál',
                'cuales',
                'cuáles',

                'dinero',
                'cantidad',
                'cantidades',

                'resultado',
                'resultados',

                'total',
                'totales',

                'cuanto',
                'cuánto',
            ];

            if (
                in_array(
                    $palabra,
                    $palabrasEstructurales,
                    true
                )
            ) {
                continue;
            }

            $palabrasSignificativas[] =
                $palabra;
        }

        $palabrasSignificativas =
            array_values(
                array_unique(
                    $palabrasSignificativas
                )
            );

        /*
        |--------------------------------------------------------------------------
        | SI NO QUEDA NINGÚN TÉRMINO DE CONCEPTO
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        |
        | "¿Cuáles fueron nuestros mayores ingresos?"
        |
        | Después de eliminar las palabras estructurales no queda ningún
        | concepto real.
        |
        */

        if (empty($palabrasSignificativas)) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | BUSCAR CONCEPTOS
        |--------------------------------------------------------------------------
        */

        $resultados = [];

        foreach ($conceptosDisponibles as $conceptoDisponible) {

            $conceptoNormalizado =
                $normalizar(
                    (string) $conceptoDisponible
                );

            if ($conceptoNormalizado === '') {
                continue;
            }

            $palabrasConcepto =
                preg_split(
                    '/[^a-z0-9áéíóúüñ]+/u',
                    $conceptoNormalizado,
                    -1,
                    PREG_SPLIT_NO_EMPTY
                );

            if (empty($palabrasConcepto)) {
                continue;
            }

            $coincidenciasExactas = [];
            $coincidenciasParciales = [];

            foreach ($palabrasSignificativas as $palabraConsulta) {

                foreach ($palabrasConcepto as $palabraConcepto) {

                    /*
                    |--------------------------------------------------------------------------
                    | COINCIDENCIA EXACTA
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $palabraConsulta ===
                        $palabraConcepto
                    ) {
                        $coincidenciasExactas[] =
                            $palabraConsulta;

                        break;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | COINCIDENCIA PARCIAL
                    |--------------------------------------------------------------------------
                    |
                    | Solo permitimos coincidencias parciales cuando la palabra
                    | consultada tiene al menos 5 caracteres.
                    |
                    */

                    if (
                        mb_strlen($palabraConsulta) >= 5 &&
                        (
                            str_contains(
                                $palabraConcepto,
                                $palabraConsulta
                            ) ||
                            str_contains(
                                $palabraConsulta,
                                $palabraConcepto
                            )
                        )
                    ) {
                        $coincidenciasParciales[] =
                            $palabraConsulta;

                        break;
                    }
                }
            }

            $coincidenciasExactas =
                array_values(
                    array_unique(
                        $coincidenciasExactas
                    )
                );

            $coincidenciasParciales =
                array_values(
                    array_unique(
                        $coincidenciasParciales
                    )
                );

            if (
                empty($coincidenciasExactas) &&
                empty($coincidenciasParciales)
            ) {
                continue;
            }

            $puntaje =
                count($coincidenciasExactas) * 1000 +
                count($coincidenciasParciales) * 100 +
                mb_strlen($conceptoNormalizado);

            $resultados[] = [
                'concepto' =>
                    $conceptoDisponible,

                'coincidencias_exactas' =>
                    $coincidenciasExactas,

                'coincidencias_parciales' =>
                    $coincidenciasParciales,

                'puntaje' =>
                    $puntaje,
            ];
        }

        if (empty($resultados)) {
            return [];
        }

        /*
        |--------------------------------------------------------------------------
        | ORDENAR
        |--------------------------------------------------------------------------
        */

        usort(
            $resultados,
            function ($a, $b) {

                return $b['puntaje']
                    <=>
                    $a['puntaje'];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | SELECCIÓN FINAL
        |--------------------------------------------------------------------------
        */

        $seleccionados = [];

        foreach ($resultados as $resultado) {

            $concepto =
                (string) $resultado['concepto'];

            $clave =
                $normalizar($concepto);

            if (
                isset($seleccionados[$clave])
            ) {
                continue;
            }

            $seleccionados[$clave] =
                $concepto;

            if (
                count($seleccionados) >= 10
            ) {
                break;
            }
        }

        return array_values(
            $seleccionados
        );
    }


    public function detectarConcepto(
        string $texto,
        string $operacion = 'show',
        ?array $fecha = null
    ): ?string {

        /*
        |--------------------------------------------------------------------------
        | NORMALIZAR TEXTO
        |--------------------------------------------------------------------------
        */

        $normalizar = function (string $valor): string {

            $valor =
                mb_strtolower(
                    trim($valor),
                    'UTF-8'
                );

            $valor =
                strtr(
                    $valor,
                    [
                        'á' => 'a',
                        'é' => 'e',
                        'í' => 'i',
                        'ó' => 'o',
                        'ú' => 'u',
                        'ü' => 'u',
                        'ñ' => 'n',
                    ]
                );

            $valor =
                preg_replace(
                    '/[^a-z0-9\s]+/u',
                    ' ',
                    $valor
                );

            return trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $valor
                )
            );
        };

        $textoNormalizado =
            $normalizar($texto);

        /*
        |--------------------------------------------------------------------------
        | OPERACIONES QUE NO DEBEN BUSCAR CONCEPTO
        |--------------------------------------------------------------------------
        |
        | Estas operaciones comparan períodos completos o buscan máximos/mínimos.
        |
        */

        $operacionesGeneralesSinConcepto = [
            'max',
            'min',
            'max_mes',
            'min_mes',
            'max_mes_ingreso',
            'min_mes_ingreso',
            'max_mes_egreso',
            'min_mes_egreso',
        ];

        if (
            in_array(
                $operacion,
                $operacionesGeneralesSinConcepto,
                true
            )
        ) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTAS COMPARATIVAS
        |--------------------------------------------------------------------------
        |
        | Aunque el nombre de la operación todavía no haya sido normalizado,
        | estas frases nunca deben convertirse en un concepto.
        |
        */

        $patronesComparativos = [
            '/\bmayor(?:es)?\b/iu',
            '/\bmenor(?:es)?\b/iu',
            '/\bmaximo(?:s)?\b/iu',
            '/\bminimo(?:s)?\b/iu',
            '/\bmáximo(?:s)?\b/iu',
            '/\bmínimo(?:s)?\b/iu',
        ];

        foreach ($patronesComparativos as $patron) {

            if (
                preg_match(
                    $patron,
                    $textoNormalizado
                )
            ) {
                return null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CONCEPTOS DEL PERÍODO
        |--------------------------------------------------------------------------
        */

        $consultaConceptos =
            Movimiento::query()
                ->whereNotNull('concepto')
                ->where('concepto', '!=', '');

        if (
            is_array($fecha) &&
            !empty($fecha['mes']) &&
            !empty($fecha['anio'])
        ) {

            $consultaConceptos
                ->whereMonth(
                    'fecha',
                    (int) $fecha['mes']
                )
                ->whereYear(
                    'fecha',
                    (int) $fecha['anio']
                );
        }

        $conceptos =
            $consultaConceptos
                ->select('concepto')
                ->distinct()
                ->pluck('concepto');

        /*
        |--------------------------------------------------------------------------
        | RESPALDO
        |--------------------------------------------------------------------------
        */

        if ($conceptos->isEmpty()) {

            $conceptos =
                Movimiento::query()
                    ->whereNotNull('concepto')
                    ->where('concepto', '!=', '')
                    ->select('concepto')
                    ->distinct()
                    ->pluck('concepto');
        }

        /*
        |--------------------------------------------------------------------------
        | PALABRAS IGNORADAS
        |--------------------------------------------------------------------------
        */

        $palabrasIgnoradas = [

            'dame',
            'darme',

            'lista',
            'listado',

            'mostrar',
            'muéstrame',
            'muestrame',

            'detalle',
            'detallame',
            'detállame',

            'enséñame',
            'ensename',

            'muestra',
            'muestreme',

            'quiero',
            'dime',

            'cual',
            'cuál',
            'que',
            'qué',

            'el',
            'la',
            'los',
            'las',

            'un',
            'una',
            'unos',
            'unas',

            'de',
            'del',
            'al',
            'a',

            'por',
            'para',
            'con',
            'en',

            'me',

            'pago',
            'pagos',
            'pagar',

            'servicio',
            'servicios',

            'movimiento',
            'movimientos',

            'ingreso',
            'ingresos',

            'egreso',
            'egresos',

            'gasto',
            'gastos',

            'suma',
            'total',
            'totales',

            'durante',

            'mes',
            'meses',

            'ano',
            'año',

            'enero',
            'febrero',
            'marzo',
            'abril',
            'mayo',
            'junio',
            'julio',
            'agosto',
            'septiembre',
            'setiembre',
            'octubre',
            'noviembre',
            'diciembre',

            'desde',
            'hasta',
            'entre',

            'este',
            'esta',
            'ese',
            'esa',

            /*
            |--------------------------------------------------------------------------
            | PALABRAS ESTRUCTURALES
            |--------------------------------------------------------------------------
            */

            'mayor',
            'mayores',

            'menor',
            'menores',

            'maximo',
            'máximo',
            'maximos',
            'máximos',

            'minimo',
            'mínimo',
            'minimos',
            'mínimos',

            'nuestro',
            'nuestra',
            'nuestros',
            'nuestras',

            'tuvimos',
            'tuvieron',
            'tenemos',
            'tenia',
            'tenía',

            'hubo',
            'hubieron',

            'fueron',
            'fue',

            'dinero',
            'cantidad',
            'cantidades',

            'resultado',
            'resultados',
        ];

        /*
        |--------------------------------------------------------------------------
        | PALABRAS DE LA CONSULTA
        |--------------------------------------------------------------------------
        */

        $palabrasConsulta =
            array_values(
                array_filter(
                    preg_split(
                        '/\s+/u',
                        $textoNormalizado
                    ),
                    function ($palabra) use (
                        $palabrasIgnoradas
                    ) {

                        return
                            mb_strlen($palabra) >= 3 &&
                            !in_array(
                                $palabra,
                                $palabrasIgnoradas,
                                true
                            ) &&
                            !preg_match(
                                '/^20\d{2}$/',
                                $palabra
                            );
                    }
                )
            );

        /*
        |--------------------------------------------------------------------------
        | SI NO EXISTEN PALABRAS DE CONCEPTO
        |--------------------------------------------------------------------------
        */

        if (empty($palabrasConsulta)) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | BUSCAR MEJOR COINCIDENCIA
        |--------------------------------------------------------------------------
        */

        $mejorCoincidencia = null;
        $mejorPuntaje = 0;
        $mejorLongitud = 0;

        foreach ($conceptos as $concepto) {

            $conceptoOriginal =
                trim($concepto);

            if ($conceptoOriginal === '') {
                continue;
            }

            $conceptoNormalizado =
                $normalizar(
                    $conceptoOriginal
                );

            if ($conceptoNormalizado === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | 1. COINCIDENCIA DIRECTA
            |--------------------------------------------------------------------------
            */

            if (
                preg_match(
                    '/(?<![a-z0-9])' .
                    preg_quote(
                        $conceptoNormalizado,
                        '/'
                    ) .
                    '(?![a-z0-9])/u',
                    $textoNormalizado
                )
            ) {

                $puntaje =
                    100 +
                    mb_strlen(
                        $conceptoNormalizado
                    );

            } else {

                /*
                |--------------------------------------------------------------------------
                | 2. COINCIDENCIA POR PALABRAS
                |--------------------------------------------------------------------------
                */

                $palabrasConcepto =
                    array_values(
                        array_filter(
                            preg_split(
                                '/\s+/u',
                                $conceptoNormalizado
                            ),
                            function ($palabra) use (
                                $palabrasIgnoradas
                            ) {

                                return
                                    mb_strlen($palabra) >= 3 &&
                                    !in_array(
                                        $palabra,
                                        $palabrasIgnoradas,
                                        true
                                    );
                            }
                        )
                    );

                if (
                    empty($palabrasConcepto) ||
                    empty($palabrasConsulta)
                ) {
                    continue;
                }

                $coincidencias = 0;

                foreach (
                    $palabrasConcepto as $palabraConcepto
                ) {

                    foreach (
                        $palabrasConsulta as $palabraConsulta
                    ) {

                        /*
                        |--------------------------------------------------------------------------
                        | COINCIDENCIA EXACTA
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $palabraConcepto ===
                            $palabraConsulta
                        ) {
                            $coincidencias += 1;
                            break;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | COINCIDENCIA PARCIAL
                        |--------------------------------------------------------------------------
                        |
                        | Solo permitimos coincidencias parciales cuando ambas
                        | palabras tienen al menos 6 caracteres.
                        |
                        */

                        if (
                            mb_strlen($palabraConcepto) >= 6 &&
                            mb_strlen($palabraConsulta) >= 6 &&
                            (
                                str_contains(
                                    $palabraConcepto,
                                    $palabraConsulta
                                ) ||
                                str_contains(
                                    $palabraConsulta,
                                    $palabraConcepto
                                )
                            )
                        ) {
                            $coincidencias += 1;
                            break;
                        }
                    }
                }

                if ($coincidencias === 0) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | PUNTAJE
                |--------------------------------------------------------------------------
                */

                $cobertura =
                    $coincidencias /
                    count($palabrasConcepto);

                $puntaje =
                    ($coincidencias * 20) +
                    ($cobertura * 30);
            }

            /*
            |--------------------------------------------------------------------------
            | GUARDAR MEJOR COINCIDENCIA
            |--------------------------------------------------------------------------
            */

            if (
                $puntaje > $mejorPuntaje ||
                (
                    $puntaje === $mejorPuntaje &&
                    mb_strlen(
                        $conceptoOriginal
                    ) > $mejorLongitud
                )
            ) {

                $mejorPuntaje =
                    $puntaje;

                $mejorLongitud =
                    mb_strlen(
                        $conceptoOriginal
                    );

                $mejorCoincidencia =
                    $conceptoOriginal;
            }
        }

        return $mejorCoincidencia;
    }
}