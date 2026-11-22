<?php

namespace App\Services\Sigi;

use App\Services\SigiAiService;
use Illuminate\Support\Facades\Log;

class SigiEstatutosService
{
    private SigiAiService $sigiAi;

    public function __construct(SigiAiService $sigiAi)
    {
        $this->sigiAi = $sigiAi;
    }

    /**
     * Determina si una consulta corresponde a los estatutos.
     */
    public function esConsultaEstatutos(string $consulta): bool
    {
        $texto = $this->normalizarTextoEstatutos($consulta);

        if ($texto === '') {
            return false;
        }

        if (preg_match('/\bestatuto(?:s)?\b/u', $texto)) {
            return true;
        }

        if (preg_match('/\barticulo\s*(?:n[°ºo.]?\s*)?\d{1,2}\b/u', $texto)) {
            return true;
        }

        $frases = [
            'derechos de los miembros',
            'derecho de los miembros',
            'obligaciones de los miembros',
            'obligacion de los miembros',
            'funciones de la junta',
            'funciones junta directiva',
            'facultades de la junta',
            'atribuciones de la junta',
            'junta directiva',
            'junta directiva central',
            'asamblea general',
            'miembro pleno',
            'miembros plenos',
            'eleccion de la junta',
            'elecciones de la junta',
            'cargos de la junta',
            'cuotas institucionales',
            'domicilio legal',
            'finalidad del grupo',
            'patrimonio del grupo',
            'sanciones del grupo',
            'sanciones de los miembros',
        ];

        foreach ($frases as $frase) {
            if (str_contains($texto, $this->normalizarTextoEstatutos($frase))) {
                return true;
            }
        }

        $combinaciones = [
            ['derecho', 'miembro'],
            ['derechos', 'miembro'],
            ['obligacion', 'miembro'],
            ['obligaciones', 'miembro'],
            ['funcion', 'junta'],
            ['funciones', 'junta'],
            ['facultad', 'junta'],
            ['facultades', 'junta'],
            ['atribucion', 'junta'],
            ['atribuciones', 'junta'],
            ['eleccion', 'junta'],
            ['elecciones', 'junta'],
            ['votacion', 'junta'],
            ['sancion', 'miembro'],
            ['sanciones', 'miembro'],
            ['requisito', 'miembro'],
            ['requisitos', 'miembro'],
            ['cuota', 'miembro'],
            ['cotizacion', 'miembro'],
        ];

        foreach ($combinaciones as [$a, $b]) {
            if (str_contains($texto, $a) && str_contains($texto, $b)) {
                return true;
            }
        }

        $palabrasDirectas = [
            'asamblea',
            'estatuto',
            'elecciones',
            'eleccion',
            'junta directiva',
            'miembro pleno',
            'sanciones',
            'sancion',
            'domicilio legal',
            'patrimonio',
            'disolucion',
            'liquidacion',
        ];

        foreach ($palabrasDirectas as $palabra) {
            if (str_contains($texto, $this->normalizarTextoEstatutos($palabra))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Consulta los estatutos oficiales.
     */
    public function consultar(string $consulta): array
    {
        try {
            $ruta = public_path('documentos/estatutos-grupo-21.txt');

            if (!is_file($ruta) || !is_readable($ruta)) {
                return [
                    'success' => false,
                    'tipo' => 'texto',
                    'resultado' => null,
                    'mensaje' =>
                        '🤖 El documento de estatutos está disponible en PDF, '
                        . 'pero todavía no tengo cargada su versión de consulta. '
                        . 'Puedes abrirlo desde el botón "Ver estatutos".',
                ];
            }

            $contenido = file_get_contents($ruta);

            if (!is_string($contenido) || trim($contenido) === '') {
                return [
                    'success' => false,
                    'tipo' => 'texto',
                    'resultado' => null,
                    'mensaje' =>
                        '🤖 No pude leer el contenido de los estatutos en este momento.',
                ];
            }

            // Detectar si solicitan un artículo concreto
            $numeroArticulo = null;

            if (
                preg_match(
                    '/\barticulo\s*(?:n[°ºo.]?\s*)?(\d{1,2})\b/u',
                    $this->normalizarTextoEstatutos($consulta),
                    $match
                )
            ) {
                $numeroArticulo = (int) $match[1];
            }

            // Separar los artículos del documento
            $articulos = [];

            preg_match_all(
                '/Artículo\s+(\d{1,2})°?\s*[.–-]\s*(.*?)(?=\n\s*(?:Artículo\s+\d{1,2}°?\s*[.–-]|T[ÍI]TULO\s+[IVXLCDM]+\b|LA\s+ASAMBLEA\s+GENERAL\s+DE\s+POBLADORES\b)|\z)/isu',
                $contenido,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $matchArticulo) {
                $numero = (int) $matchArticulo[1];
                $articulos[$numero] = trim($matchArticulo[0]);
            }

            // Si existe una petición directa de artículo, devolver el texto oficial directamente
            if ($numeroArticulo !== null) {
                if (!isset($articulos[$numeroArticulo])) {
                    return [
                        'success' => false,
                        'tipo' => 'texto',
                        'resultado' => [
                            'documento' => 'Estatutos del Grupo Residencial 21 – 2° Sector',
                            'articulos_consultados' => [],
                        ],
                        'mensaje' =>
                            '🤖 No encontré el artículo '
                            . $numeroArticulo
                            . ' en el documento oficial de estatutos.',
                    ];
                }

                $textoArticulo = $this->limpiarTextoEstatuto($articulos[$numeroArticulo]);

                return [
                    'success' => true,
                    'tipo' => 'texto',
                    'resultado' => [
                        'documento' => 'Estatutos del Grupo Residencial 21 – 2° Sector',
                        'articulo' => $numeroArticulo,
                        'articulos_consultados' => [$numeroArticulo],
                    ],
                    'mensaje' =>
                        '🤖 Artículo '
                        . $numeroArticulo
                        . ' — Estatutos del Grupo Residencial 21'
                        . "\n\n"
                        . $textoArticulo
                        . "\n\n"
                        . '📄 Ver estatutos completos: /documentos/estatutos-grupo-21.pdf',
                ];
            }

            // Preparar la consulta para búsqueda temática
            $consultaNormalizada = $this->normalizarTextoEstatutos($consulta);

            $palabrasClave = preg_split(
                '/\s+/u',
                $consultaNormalizada,
                -1,
                PREG_SPLIT_NO_EMPTY
            );

            $palabrasIgnoradas = [
                'que', 'qué', 'como', 'cómo', 'cual', 'cuál', 'cuales', 'cuáles',
                'quien', 'quién', 'donde', 'dónde', 'cuando', 'cuándo', 'por',
                'para', 'con', 'del', 'los', 'las', 'una', 'uno', 'unos', 'unas',
                'tiene', 'tienen', 'hay', 'puede', 'pueden', 'dice', 'decir',
                'sobre', 'dentro', 'grupo', 'residencial', 'sector', 'villa', 'salvador',
            ];

            $palabrasClave = array_values(
                array_filter(
                    $palabrasClave,
                    fn ($palabra) => mb_strlen($palabra, 'UTF-8') >= 4 && !in_array($palabra, $palabrasIgnoradas, true)
                )
            );

            $conceptos = [
                'miembros' => ['miembro', 'miembros', 'pobladores', 'poblador', 'integrantes', 'admision', 'retiro'],
                'junta' => ['junta', 'directiva', 'dirigir', 'direccion', 'presidente', 'vicepresidente', 'secretario', 'tesorero', 'fiscal', 'vocal'],
                'secretarias' => [
                    'secretario general', 'medio ambiente', 'ambiente', 'ecologia',
                    'areas verdes', 'ornato', 'economia', 'actas', 'organizacion',
                    'deporte', 'cultura', 'seguridad', 'asistencia social',
                ],
                'asamblea' => ['asamblea', 'asambleas', 'pobladores', 'convocatoria', 'convocar', 'acuerdos', 'sesion', 'sesiones'],
                'elecciones' => ['eleccion', 'elecciones', 'elegir', 'elegido', 'votacion', 'votaciones', 'candidato', 'candidatos', 'cargo', 'cargos', 'periodo'],
                'sanciones' => ['sancion', 'sanciones', 'falta', 'faltas', 'incumplimiento', 'amonestacion', 'suspension', 'retiro'],
                'finanzas' => ['cuota', 'cuotas', 'cotizacion', 'cotizaciones', 'ingresos', 'egresos', 'presupuesto', 'balance', 'patrimonio', 'bienes', 'recursos', 'prestamos', 'cuentas', 'bancarias', 'auditoria', 'auditorias'],
                'administracion' => ['administrar', 'administracion', 'administrativo', 'contratos', 'personal', 'asesores', 'bienes', 'recursos', 'plan', 'presupuesto', 'memoria'],
                'finalidad' => ['finalidad', 'objetivo', 'objetivos', 'proposito', 'propositos', 'desarrollo', 'promocion'],
                'constitucion' => ['constitucion', 'constituido', 'integrantes', 'manzanas', 'territorio', 'domicilio'],
                'reforma' => ['reforma', 'reformas', 'modificacion', 'modificaciones', 'cambio', 'cambios'],
                'disolucion' => ['disolucion', 'disolver', 'liquidacion', 'liquidar'],
            ];

            $conceptosConsulta = [];

            foreach ($conceptos as $concepto => $variantes) {
                foreach ($variantes as $variante) {
                    if (str_contains($consultaNormalizada, $this->normalizarTextoEstatutos($variante))) {
                        $conceptosConsulta[$concepto] = true;
                        break;
                    }
                }
            }

            $frasesImportantes = [
                'junta directiva', 'junta directiva central', 'asamblea general',
                'miembro pleno', 'miembros plenos', 'derechos de los miembros',
                'derecho de los miembros', 'obligaciones de los miembros',
                'obligacion de los miembros', 'funciones de la junta',
                'facultades de la junta', 'atribuciones de la junta',
                'eleccion de la junta', 'elecciones de la junta',
                'cargos de la junta', 'plan de trabajo', 'memoria anual',
                'balance general', 'cuotas institucionales', 'domicilio legal',
                'finalidad del grupo', 'patrimonio del grupo',
            ];

            $candidatos = [];

           // 1. Aumentar precisión para cargos con "(a)" como "Secretario(a) General"
            $consultaSinParentesis = str_replace(['(a)', '(os)', '(as)'], '', $consultaNormalizada);

            foreach ($articulos as $numero => $articulo) {
                // Normalizamos quitando "(a)" para que "secretario(a) general" coincida con "secretario general"
                $textoArticulo = str_replace(['(a)', '(os)', '(as)'], '', $this->normalizarTextoEstatutos($articulo));
                $puntaje = 0;
                $coincidencias = [];

                foreach ($frasesImportantes as $frase) {
                    $fraseNormalizada = $this->normalizarTextoEstatutos($frase);
                    if (str_contains($consultaSinParentesis, $fraseNormalizada) && str_contains($textoArticulo, $fraseNormalizada)) {
                        $puntaje += 12;
                        $coincidencias[] = $frase;
                    }
                }

                foreach ($conceptosConsulta as $concepto => $_activo) {
                    foreach ($conceptos[$concepto] as $variante) {
                        $varianteNormalizada = $this->normalizarTextoEstatutos($variante);
                        if (str_contains($consultaSinParentesis, $varianteNormalizada) && str_contains($textoArticulo, $varianteNormalizada)) {
                            $puntaje += ($concepto === 'secretarias') ? 14 : 5;
                            $coincidencias[] = $variante;
                        }
                    }
                }

                foreach ($palabrasClave as $palabra) {
                    if (str_contains($textoArticulo, $palabra)) {
                        $puntaje += 2;
                        $coincidencias[] = $palabra;
                    }
                }

                $coincidencias = array_values(array_unique($coincidencias));

                if ($puntaje > 0) {
                    $candidatos[] = [
                        'numero' => $numero,
                        'puntaje' => $puntaje,
                        'coincidencias' => $coincidencias,
                        'texto' => $articulo,
                    ];
                }
            }

            usort(
                $candidatos,
                fn ($a, $b) => $a['puntaje'] === $b['puntaje'] ? ($a['numero'] <=> $b['numero']) : ($b['puntaje'] <=> $a['puntaje'])
            );

            // Con Groq podemos enviar hasta 6 artículos relevantes sin problema
            $candidatos = array_slice($candidatos, 0, 6);

            if (empty($candidatos)) {
                return [
                    'success' => false,
                    'tipo' => 'texto',
                    'resultado' => [
                        'documento' => 'Estatutos del Grupo Residencial 21 – 2° Sector',
                        'articulos_consultados' => [],
                    ],
                    'mensaje' =>
                        '🤖 No encontré en los estatutos un artículo que permita responder con seguridad a esa pregunta. '
                        . 'Puedes preguntarme por un número de artículo o indicarme el tema que deseas consultar.',
                ];
            }

            $mejorPuntaje = (int) ($candidatos[0]['puntaje'] ?? 0);

            if ($mejorPuntaje < 4) {
                return [
                    'success' => false,
                    'tipo' => 'texto',
                    'resultado' => [
                        'documento' => 'Estatutos del Grupo Residencial 21 – 2° Sector',
                        'articulos_consultados' => [],
                    ],
                    'mensaje' =>
                        '🤖 No encontré suficiente coincidencia en los estatutos para responder con seguridad. '
                        . 'Prueba formulando la pregunta con otras palabras o indicando el artículo.',
                ];
            }

            $fuentes = '';

            foreach ($candidatos as $candidato) {
                $fuentes .=
                    "\n\n--- ARTÍCULO " . $candidato['numero'] . " ---\n"
                    . $this->limpiarTextoEstatuto($candidato['texto']);
            }

            $fuentes = mb_substr($fuentes, 0, 12000, 'UTF-8');

            $respuesta = $this->sigiAi->responder(
                'Responde la consulta del vecino utilizando ÚNICAMENTE los artículos de los Estatutos oficiales incluidos abajo. '
                . 'Habla de forma natural como ZOE (nunca digas "en los artículos que me has entregado" ni "según la fuente proporcionada"). '
                . 'No inventes datos ni uses conocimiento externo. '
                . 'Si los artículos no contienen la respuesta exacta, indica amablemente qué artículos revisaste y sugiere revisar el PDF completo. '
                . 'Menciona los números de los artículos utilizados y resume los puntos o incisos relevantes de forma clara. '
                . 'PREGUNTA DEL VECINO: ' . $consulta
                . "\n\nESTATUTOS DEL GRUPO RESIDENCIAL 21 – 2° SECTOR – VILLA EL SALVADOR"
                . $fuentes,
                'Eres ZOE, asistente virtual de SIGEFIV, y respondes consultas sobre los Estatutos oficiales del Grupo Residencial 21 – 2° Sector – Villa El Salvador.'
            );

            // Respaldo: si SigiAiService devuelve null, entregar directamente el artículo más relevante
            if ($respuesta === null) {
                Log::warning('SigiEstatutosService: SigiAiService devolvió null, usando respaldo directo', [
                    'consulta' => $consulta,
                    'articulos' => array_column($candidatos, 'numero'),
                ]);

                $principal = $candidatos[0];
                $textoRespaldo = $this->limpiarTextoEstatuto($principal['texto']);

                return [
                    'success' => true,
                    'tipo' => 'texto',
                    'resultado' => [
                        'documento' => 'Estatutos del Grupo Residencial 21 – 2° Sector',
                        'articulos_consultados' => [$principal['numero']],
                    ],
                    'mensaje' =>
                        '🤖 Según los estatutos oficiales, esto es lo que establece el artículo más relacionado:'
                        . "\n\n"
                        . $textoRespaldo
                        . "\n\n"
                        . '📄 Ver estatutos completos: /documentos/estatutos-grupo-21.pdf',
                ];
            }

            return [
                'success' => true,
                'tipo' => 'texto',
                'resultado' => [
                    'documento' => 'Estatutos del Grupo Residencial 21 – 2° Sector',
                    'articulos_consultados' => array_column($candidatos, 'numero'),
                ],
                'mensaje' =>
                    '🤖 ' . $respuesta
                    . "\n\n"
                    . '📄 Ver estatutos completos: /documentos/estatutos-grupo-21.pdf',
            ];
        } catch (\Throwable $e) {
            Log::error('SigiEstatutosService error inesperado: ' . $e->getMessage());

            return [
                'success' => false,
                'tipo' => 'texto',
                'resultado' => null,
                'mensaje' =>
                    '🤖 Ocurrió un problema al consultar los estatutos. Puedes intentarlo nuevamente.',
            ];
        }
    }

    private function normalizarTextoEstatutos(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');

        // Eliminar sufijos de género entre paréntesis: secretario(a) -> secretario
        $texto = preg_replace('/\((?:a|o|as|os)\)/u', '', $texto);

        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i',
            'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        $texto = preg_replace('/[^\p{L}\p{N}\s°º.-]/u', ' ', $texto);
        $texto = preg_replace('/\s+/u', ' ', $texto);

        return trim($texto);
    }

    private function limpiarTextoEstatuto(string $texto): string
    {
        $texto = preg_replace(
            '/\n\s*LA\s+ASAMBLEA\s+GENERAL\s+DE\s+POBLADORES\s*\n\s*\d{1,3}\s*(?=\n|\z)/iu',
            "\n",
            $texto
        );

        $texto = preg_replace('/(?m)^\s*\d{1,3}\s*$/u', '', $texto);
        $texto = preg_replace('/\s+/u', ' ', trim($texto));

        return trim($texto);
    }
}