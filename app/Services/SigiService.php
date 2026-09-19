<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Str;

class SigiService
{
    /**
     * Analiza un mensaje del Chat Vecinal.
     *
     * Puede recibir un mensaje anterior para permitir
     * análisis contextual.
     */
    public function analizarMensaje(
        ChatMessage $mensaje,
        ?ChatMessage $mensajeAnterior = null
    ): array {
        $texto = trim($mensaje->mensaje);

        if ($texto === '') {
            return $this->resultadoIgnorado();
        }

        $textoNormalizado = $this->normalizarTexto($texto);

        /*
        |--------------------------------------------------------------------------
        | SALUDO / CONVERSACIÓN SOCIAL
        |--------------------------------------------------------------------------
        |
        | Los saludos deben quedar fuera del análisis de preguntas.
        | Esto evita que expresiones como "hola vecino como esta"
        | sean interpretadas como una pregunta contextual por la
        | palabra "como".
        |--------------------------------------------------------------------------
        */

        if ($this->esSaludo($textoNormalizado)) {
            return [
                'analizado' => true,
                'mensaje_id' => $mensaje->id,
                'categoria' => 'general',
                'intencion' => 'saludo',
                'prioridad' => 'baja',
                'debe_intervenir' => false,
                'contextual' => false,
                'texto' => $texto,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | CONVERSACIÓN SOCIAL / CHARLA DEL GRUPO
        |--------------------------------------------------------------------------
        |
        | Frases como "bien y tu", "como que no sabes" o
        | "ya fue al mercado" son conversación entre vecinos.
        | No deben activar el detector de preguntas ni heredar
        | el contexto de seguridad/agua/luz de mensajes anteriores.
        |
        */

        if ($this->esConversacionSocial($textoNormalizado)) {
            return [
                'analizado' => true,
                'mensaje_id' => $mensaje->id,
                'categoria' => 'general',
                'intencion' => 'comentario',
                'prioridad' => 'baja',
                'debe_intervenir' => false,
                'contextual' => false,
                'texto' => $texto,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Análisis directo
        |--------------------------------------------------------------------------
        */

        $categoria = $this->detectarCategoria(
            $textoNormalizado
        );

        $intencion = $this->detectarIntencion(
            $textoNormalizado,
            $categoria
        );

        /*
        |--------------------------------------------------------------------------
        | Contexto para preguntas generales
        |--------------------------------------------------------------------------
        |
        | Preguntas como:
        |
        |   "¿Ya volvió para todos?"
        |   "¿Ya se solucionó?"
        |   "¿Todos tienen agua?"
        |
        | pueden no mencionar directamente la categoría.
        |
        | Si existe un mensaje anterior relacionado con un servicio,
        | heredamos esa categoría para no confundir el evento actual
        | con otro servicio de la misma conversación.
        */

        $contextual = false;

        if (
            $categoria === 'general' &&
            $intencion === 'pregunta'
        ) {
            $contextoPregunta = null;

            /*
            |--------------------------------------------------------------------------
            | Una pregunta general hereda directamente la categoría
            | del mensaje anterior.
            |--------------------------------------------------------------------------
            |
            | No usamos analizarContexto() aquí porque ese método descarta
            | comentarios. Para una pregunta como "¿Ya volvió para todos?",
            | necesitamos conservar la categoría aunque el mensaje anterior
            | sea un comentario como "todos tiene agua".
            |
            */

            if ($mensajeAnterior) {
                $textoAnterior =
                    trim($mensajeAnterior->mensaje);

                if ($textoAnterior !== '') {
                    $textoAnterior =
                        $this->normalizarTexto(
                            $textoAnterior
                        );

                    $categoriaAnterior =
                        $this->detectarCategoria(
                            $textoAnterior
                        );

                    if (
                        $categoriaAnterior !== 'general'
                    ) {
                        $contextoPregunta = [
                            'categoria' =>
                                $categoriaAnterior,
                            'intencion' =>
                                'pregunta',
                        ];
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Si el mensaje anterior no identifica la categoría,
            | buscamos en los mensajes recientes de la conversación.
            |--------------------------------------------------------------------------
            */

            if (
                $contextoPregunta === null &&
                $mensaje->conversation_id
            ) {
                $mensajesRecientes =
                    ChatMessage::query()
                        ->where(
                            'conversation_id',
                            $mensaje->conversation_id
                        )
                        ->where(
                            'id',
                            '<',
                            $mensaje->id ?? PHP_INT_MAX
                        )
                        ->where(
                            'tipo',
                            '!=',
                            'sigi'
                        )
                        ->orderByDesc('id')
                        ->limit(15)
                        ->get();

                $conteosCategorias = [];

                foreach ($mensajesRecientes as $mensajeReciente) {
                    $textoReciente =
                        $this->normalizarTexto(
                            trim($mensajeReciente->mensaje)
                        );

                    if ($textoReciente === '') {
                        continue;
                    }

                    $categoriaReciente =
                        $this->detectarCategoria(
                            $textoReciente
                        );

                    if (
                        $categoriaReciente === 'general'
                    ) {
                        continue;
                    }

                    $conteosCategorias[$categoriaReciente] =
                        ($conteosCategorias[$categoriaReciente] ?? 0) + 1;
                }

                if (!empty($conteosCategorias)) {
                    arsort($conteosCategorias);

                    $contextoPregunta = [
                        'categoria' =>
                            array_key_first(
                                $conteosCategorias
                            ),
                        'intencion' => 'pregunta',
                    ];
                }
            }

            if ($contextoPregunta !== null) {
                $categoria =
                    $contextoPregunta['categoria'];

                $contextual = true;
            }
        }

        if (
            $this->esRespuestaContextual(
                $textoNormalizado
            )
        ) {
            $contexto = $this->analizarContexto(
                $mensajeAnterior
            );

            if ($contexto !== null) {
                $categoria = $contexto['categoria'];
                $intencion = $contexto['intencion'];
                $contextual = true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Prioridad
        |--------------------------------------------------------------------------
        */

        $prioridad = $this->determinarPrioridad(
            $categoria,
            $intencion
        );

        /*
        |--------------------------------------------------------------------------
        | Decisión
        |--------------------------------------------------------------------------
        */

        $debeIntervenir = $this->debeIntervenir(
            $categoria,
            $intencion,
            $prioridad
        );

        return [
            'analizado' => true,

            'mensaje_id' => $mensaje->id,

            'categoria' => $categoria,

            'intencion' => $intencion,

            'prioridad' => $prioridad,

            'debe_intervenir' => $debeIntervenir,

            'contextual' => $contextual,

            'texto' => $texto,
        ];
    }

    /**
     * Normaliza el texto.
     */
    protected function normalizarTexto(
        string $texto
    ): string {
        $texto = Str::lower($texto);

        $texto = Str::ascii($texto);

        $texto = preg_replace(
            '/\s+/',
            ' ',
            $texto
        );

        return trim($texto);
    }

    /**
     * Detecta frases de conversación cotidiana que no requieren
     * intervención de SIGI.
     */
    protected function esConversacionSocial(
        string $texto
    ): bool {
        $frasesExactas = [
            'bien y tu',
            'bien y tú',
            'bien gracias',
            'bien gracias y tu',
            'bien gracias y tú',
            'todo bien',
            'todo tranquilo',
            'como que no sabes',
            'como que no sabe',
            'cómo que no sabes',
            'cómo que no sabe',
            'ya fue al mercado',
            'fui al mercado',
            'ya fui al mercado',
            'no se la verdad',
            'no sé la verdad',
            'no se',
            'no sé',
        ];

        return in_array(
            $texto,
            $frasesExactas,
            true
        );
    }

    /**
     * Detecta saludos y expresiones sociales sencillas.
     */
    protected function esSaludo(
        string $texto
    ): bool {
        return
            preg_match(
                '/^(?:hola|hey|buenas)(?: vecino| vecina| vecinos| vecinas| amigo| amiga| amigos| amigas)?(?: sigi)?$/u',
                $texto
            ) === 1
            ||
            preg_match(
                '/^(?:hola|hey|buenas)(?: vecino| vecina| vecinos| vecinas| amigo| amiga| amigos| amigas)?(?: sigi)? (?:como esta|como estas|como estan|como esta ud|como esta usted|que tal)$/u',
                $texto
            ) === 1
            ||
            preg_match(
                '/^(?:como esta|cómo está|como estas|cómo estás|como estan|cómo están)(?: ud| usted)?$/u',
                $texto
            ) === 1
            ||
            preg_match(
                '/^(?:que tal|qué tal)(?: ud| usted)?$/u',
                $texto
            ) === 1
            ||
            preg_match(
                '/^(?:buenos dias|buenos días|buenas tardes|buenas noches)(?: vecino| vecina| vecinos| vecinas| amigo| amiga| amigos| amigas)?(?: sigi)?$/u',
                $texto
            ) === 1;
    }

    /**
     * Detecta respuestas que dependen del contexto.
     */
    protected function esRespuestaContextual(
        string $texto
    ): bool {
        return $this->contieneAlguno($texto, [
            'yo tampoco',
            'yo tampoco tengo',
            'tampoco tengo',
            'a mi tambien',
            'a mi también',
            'tambien',
            'también',
            'igual',
            'aca tambien',
            'aca tampoco',
            'aqui tambien',
            'aqui tampoco',
            'aquí también',
            'aquí tampoco',
        ]);
    }

    /**
     * Analiza el mensaje anterior.
     */
    protected function analizarContexto(
        ?ChatMessage $mensajeAnterior
    ): ?array {
        if (!$mensajeAnterior) {
            return null;
        }

        $textoAnterior = trim(
            $mensajeAnterior->mensaje
        );

        if ($textoAnterior === '') {
            return null;
        }

        $textoAnterior = $this->normalizarTexto(
            $textoAnterior
        );

        $categoria = $this->detectarCategoria(
            $textoAnterior
        );

        $intencion = $this->detectarIntencion(
            $textoAnterior,
            $categoria
        );

        if (
            $categoria === 'general' ||
            $intencion === 'comentario'
        ) {
            return null;
        }

        if (
            $this->esRespuestaContextual(
                $textoAnterior
            )
        ) {
            $intencion = 'reporte_problema';
        }

        return [
            'categoria' => $categoria,
            'intencion' => $intencion,
        ];
    }

    /**
     * Detecta la categoría principal.
     */
    protected function detectarCategoria(
        string $texto
    ): string {
        /*
        |--------------------------------------------------------------------------
        | EMERGENCIA
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'incendio',
            'incendios',
            'fuego',
            'se quema',
            'casa se quema',
            'humo',
            'explosion',
            'exploto',
            'accidente',
            'accidentes',
            'persona herida',
            'persona tirada',
            'herido',
            'herida',
            'atropellado',
            'atropellada',
            'ambulancia',
            'emergencia medica',
            'emergencia médica',
        ])) {
            return 'emergencia';
        }

        /*
        |--------------------------------------------------------------------------
        | SEGURIDAD
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'robo',
            'robaron',
            'robar',
            'robando',
            'estas robando',
            'estan robando',
            'asaltando',
            'asalto',
            'asaltaron',
            'ladron',
            'ladrones',
            'delincuente',
            'delincuentes',
            'inseguridad',
            'peligro',
            'delincuencia',
            'violencia',
            'peleando',
            'pelea',
            'pandillero',
            'pandilleros',
            'pandilla',
            'amenaza',
            'amenazando',
        ])) {
            return 'seguridad';
        }

        /*
        |--------------------------------------------------------------------------
        | AGUA
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'agua',
            'no tengo agua',
            'no hay agua',
            'sin agua',
            'se fue el agua',
            'cortaron el agua',
            'corte de agua',
            'no sale agua',
            'no me sale agua',
            'baja presion',
            'poca presion',
            'presion del agua',
            'presion baja',
            'fuga de agua',
            'fuga de agua por',
            'tuberia rota',
            'tubería rota',
            'inundacion',
            'inundación',
        ])) {
            return 'agua';
        }

        /*
        |--------------------------------------------------------------------------
        | GAS NATURAL / CÁLIDDA
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'gas',
            'calidda',
            'cálidda',
            'fuga de gas',
            'olor a gas',
            'olor fuerte a gas',
            'tuberia de gas',
            'tubería de gas',
            'recibo de gas',
            'recibos de gas',
            'servicio de gas',
        ])) {
            return 'gas';
        }

        /*
        |--------------------------------------------------------------------------
        | LUZ
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'luz',
            'electricidad',
            'energia electrica',
            'no tengo luz',
            'no hay luz',
            'sin luz',
            'se fue la luz',
            'cortaron la luz',
            'corte de luz',
            'apagon',
            'poste',
            'cable electrico',
            'cable de luz',
        ])) {
            return 'luz';
        }

        /*
        |--------------------------------------------------------------------------
        | LIMPIEZA
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'basura',
            'basuras',
            'basurero',
            'residuos',
            'desperdicios',
            'recojo de basura',
            'recojo de residuos',
        ])) {
            return 'limpieza';
        }

        /*
        |--------------------------------------------------------------------------
        | PARQUE
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'parque',
            'area verde',
            'areas verdes',
            'jardin',
            'jardines',
            'grass',
            'riego',
            'regar',
        ])) {
            return 'parque';
        }

        /*
        |--------------------------------------------------------------------------
        | SERVICIOS
        |--------------------------------------------------------------------------
        */

        if ($this->contieneAlguno($texto, [
            'serenazgo',
            'policia',
            'policia nacional',
            'bomberos',
            'municipalidad',
            'sedapal',
            'calidda',
            'cálidda',
        ])) {
            return 'servicios';
        }

        return 'general';
    }

    /**
     * Detecta la intención.
     *
     * El orden es importante.
     */
    protected function detectarIntencion(
        string $texto,
        string $categoria
    ): string {
        /*
        |--------------------------------------------------------------------------
        | 1. ALERTA
        |--------------------------------------------------------------------------
        */

        if (
            $this->esAlerta(
                $texto,
                $categoria
            )
        ) {
            return 'alerta';
        }

        /*
        |--------------------------------------------------------------------------
        | 2. PREGUNTA
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | Las preguntas se evalúan ANTES de los reportes
        | resueltos.
        |
        | Esto evita confundir:
        |
        | "Ya volvió el agua"
        |
        | con:
        |
        | "¿Ya volvió el agua?"
        |
        | También permite reconocer:
        |
        | "Alguien sabe si ya volvió el agua"
        | "Saben si ya volvió"
        |
        */

        if (
            $this->esPregunta(
                $texto
            )
        ) {
            return 'pregunta';
        }

        /*
        |--------------------------------------------------------------------------
        | 3. REPORTE RESUELTO
        |--------------------------------------------------------------------------
        */

        if (
            $this->esReporteResuelto(
                $texto
            )
        ) {
            return 'reporte_resuelto';
        }

        /*
        |--------------------------------------------------------------------------
        | 4. REPORTE DE PROBLEMA
        |--------------------------------------------------------------------------
        */

        if (
            $this->esReporteProblema(
                $texto
            )
        ) {
            return 'reporte_problema';
        }

        return 'comentario';
    }

    /**
     * Detecta alertas.
     */
    protected function esAlerta(
        string $texto,
        string $categoria
    ): bool {
        if ($categoria === 'emergencia') {
            return true;
        }

        if (
            $categoria === 'gas' &&
            $this->contieneAlguno($texto, [
                'fuga de gas',
                'olor a gas',
                'olor fuerte a gas',
                'gas se esta escapando',
                'gas se está escapando',
                'escape de gas',
            ])
        ) {
            return true;
        }

        if (
            $categoria === 'seguridad' &&
            $this->contieneAlguno($texto, [
                'estan robando',
                'están robando',
                'estas robando',
                'estás robando',
                'estan asaltando',
                'están asaltando',
                'asaltando',
                'acaban de robar',
                'acaban de asaltar',
                'hay un ladron',
                'hay delincuentes',
                'hay pandilleros',
                'llegaron los pandilleros',
                'hay peligro',
                'auxilio',
                'ayuda',
                'urgente',
                'emergencia',
            ])
        ) {
            return true;
        }

        return $this->contieneAlguno($texto, [
            'auxilio',
            'ayuda',
            'urgente',
            'emergencia',
            'socorro',
            'cuidado',
        ]);
    }

    /**
 * Detecta preguntas.
 *
 * Evita considerar cualquier palabra interrogativa
 * aislada como una pregunta.
 */
protected function esPregunta(
    string $texto
): bool {

    /*
    |--------------------------------------------------------------------------
    | Signo de interrogación
    |--------------------------------------------------------------------------
    */

    if (str_contains($texto, '?')) {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Frases interrogativas claras
    |--------------------------------------------------------------------------
    */

    return $this->contieneAlguno($texto, [
        'alguien sabe',
        'saben si',
        'saben cuando',
        'saben donde',
        'saben por que',
        'saben porque',
        'alguien conoce',
        'alguien sabe cuando',
        'alguien sabe donde',
        'alguien sabe porque',

        'hay agua',
        'hay luz',
        'tienen agua',
        'tienen luz',

        'donde esta',
        'donde estan',
        'cuando llega',
        'cuando vuelve',
        'por que no hay',
        'porque no hay',
    ]);
}

    /**
     * Detecta reportes de problemas.
     */
    protected function esReporteProblema(
        string $texto
    ): bool {
        return $this->contieneAlguno($texto, [
            'no tengo',
            'no hay',
            'sin agua',
            'sin luz',
            'se fue',
            'se corto',
            'se cortó',
            'cortaron',
            'no sale',
            'no funciona',
            'baja presion',
            'baja presión',
            'poca presion',
            'poca presión',
            'presion baja',
            'presión baja',
            'problema',
            'fallando',
            'falla',
        ]);
    }

    /**
     * Detecta reportes de solución.
     */
    protected function esReporteResuelto(
        string $texto
    ): bool {
        return $this->contieneAlguno($texto, [
            'ya volvio',
            'ya volvió',
            'ya regreso',
            'ya regresó',
            'ya hay',
            'ya tengo',
            'regreso',
            'regresó',
            'volvio',
            'volvió',
            'se soluciono',
            'se solucionó',
            'ya funciona',
            'ya funciona normal',
            'ya esta normal',
            'todos tienen agua',
            'todos tienen luz',
            'todos tiene agua',
            'todos tiene luz',
            'ya está normal',
            'ya se arreglo',
            'ya se arregló',
            'ya se soluciono',
            'ya se solucionó',
        ]);
    }

    /**
     * Determina prioridad.
     */
    protected function determinarPrioridad(
        string $categoria,
        string $intencion
    ): string {
        if (
            $categoria === 'emergencia' ||
            $categoria === 'gas' &&
            $intencion !== 'pregunta' ||
            $intencion === 'alerta'
        ) {
            return 'alta';
        }

        if (
            in_array(
                $categoria,
                [
                    'seguridad',
                    'agua',
                    'luz',
                    'gas',
                    'servicios',
                ],
                true
            ) &&
            in_array(
                $intencion,
                [
                    'reporte_problema',
                    'reporte_resuelto',
                    'pregunta',
                ],
                true
            )
        ) {
            return 'media';
        }

        return 'baja';
    }

    /**
     * Decide si SIGI debe intervenir.
     */
    protected function debeIntervenir(
        string $categoria,
        string $intencion,
        string $prioridad
    ): bool {
        if ($prioridad === 'alta') {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Las preguntas sí pueden recibir respuesta.
        |--------------------------------------------------------------------------
        */

        if ($intencion === 'pregunta') {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Los reportes normales permanecen en silencio.
        |--------------------------------------------------------------------------
        */

        return false;
    }

    /**
     * Comprueba términos.
     */
    protected function contieneAlguno(
        string $texto,
        array $terminos
    ): bool {
        foreach ($terminos as $termino) {
            $terminoNormalizado = trim(
                Str::ascii(
                    Str::lower($termino)
                )
            );

            if ($terminoNormalizado === '') {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Palabras completas / frases completas
            |--------------------------------------------------------------------------
            |
            | Antes utilizábamos str_contains(), lo que provocaba falsos
            | positivos cuando un término estaba contenido dentro de otra
            | palabra.
            |
            | Ejemplo:
            |
            |   "agregar"
            |
            | contiene "regar", por lo que el mensaje era clasificado
            | incorrectamente como "parque".
            |
            | Con límites de palabra:
            |
            |   "regar"   -> coincide
            |   "agregar" -> no coincide
            |
            | Para términos compuestos, como "area verde" o "fuga de agua",
            | los límites también permiten detectar la frase completa sin
            | exigir coincidencias parciales.
            |--------------------------------------------------------------------------
            */

            $patron = '/(?<![a-z0-9])' .
                preg_quote($terminoNormalizado, '/') .
                '(?![a-z0-9])/i';

            if (preg_match($patron, $texto) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resultado para mensaje vacío.
     */
    protected function resultadoIgnorado(): array
    {
        return [
            'analizado' => false,

            'mensaje_id' => null,

            'categoria' => 'general',

            'intencion' => 'ninguna',

            'prioridad' => 'baja',

            'debe_intervenir' => false,

            'contextual' => false,

            'texto' => '',
        ];
    }
}