<?php

namespace App\Services;

use App\Models\SigiEvento;

class SigiIntervencionService
{
    /**
     * Evalúa si SIGI debe intervenir ante un evento.
     *
     * La decisión se basa en:
     *
     * - prioridad del evento
     * - estado actual
     * - intención del último mensaje
     * - información acumulada
     *
     * SIGI no responde simplemente porque existan
     * muchos reportes.
     */
    public function evaluar(
        SigiEvento $evento,
        ?array $analisis = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Emergencias
        |--------------------------------------------------------------------------
        */

        if ($evento->esAltaPrioridad()) {
            return [
                'debe_intervenir' => true,
                'tipo' => 'emergencia',
                'motivo' =>
                    'El evento tiene prioridad alta.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Evento resuelto
        |--------------------------------------------------------------------------
        */

        if ($evento->estaResuelto()) {
            return [
                'debe_intervenir' => false,
                'tipo' => 'resuelto',
                'motivo' =>
                    'El evento ya fue resuelto.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Evento cerrado
        |--------------------------------------------------------------------------
        */

        if ($evento->estaCerrado()) {
            return [
                'debe_intervenir' => false,
                'tipo' => 'cerrado',
                'motivo' =>
                    'El evento está cerrado.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Pregunta directa
        |--------------------------------------------------------------------------
        */

        if ($this->esPreguntaDirecta($analisis)) {
            return [
                'debe_intervenir' => true,
                'tipo' => 'respuesta_contextual',
                'motivo' =>
                    'El vecino realizó una pregunta directa relacionada con el evento.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Reportes múltiples
        |--------------------------------------------------------------------------
        */

        if ($evento->tieneMultiplesReportes(3)) {
            return [
                'debe_intervenir' => false,
                'tipo' => 'acumulando',
                'motivo' =>
                    'Se acumularon múltiples reportes; SIGI continuará observando el evento.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Problemas mixtos
        |--------------------------------------------------------------------------
        */

        if ($evento->tieneProblemasMixtos()) {
            return [
                'debe_intervenir' => false,
                'tipo' => 'acumulando',
                'motivo' =>
                    'Existen diferentes tipos de reportes; SIGI continuará acumulando información.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Por defecto
        |--------------------------------------------------------------------------
        */

        return [
            'debe_intervenir' => false,
            'tipo' => 'silencio',
            'motivo' =>
                'No existe una razón suficiente para que SIGI intervenga.',
        ];
    }

    /**
     * Determina si el análisis corresponde a una pregunta directa.
     */
    protected function esPreguntaDirecta(
        ?array $analisis
    ): bool {
        if (!$analisis) {
            return false;
        }

        return (
            ($analisis['intencion'] ?? null)
            === 'pregunta'
        );
    }

    /**
     * Genera un resumen estructurado del evento.
     */
    public function construirResumen(
        SigiEvento $evento
    ): array {
        $resumen =
            $evento->resumenReportes();

        return [
            'evento_id' =>
                $evento->id,

            'categoria' =>
                $evento->categoria,

            'estado' =>
                $evento->estado,

            'prioridad' =>
                $evento->prioridad,

            'total' =>
                $resumen['total'],

            'sin_servicio' =>
                $resumen['sin_servicio'],

            'baja_presion' =>
                $resumen['baja_presion'],

            'servicio_intermitente' =>
                $resumen['servicio_intermitente'],

            'restablecido' =>
                $resumen['restablecido'],

            'alerta' =>
                $resumen['alerta'],

            'informacion' =>
                $resumen['informacion'],

            'usuarios_afectados' =>
                $resumen['usuarios_afectados'],

            'usuarios_restablecidos' =>
                $resumen['usuarios_restablecidos'],

            'tipo_predominante' =>
                $evento->tipoPredominante(),

            'multiples_reportes' =>
                $evento->tieneMultiplesReportes(),

            'problemas_mixtos' =>
                $evento->tieneProblemasMixtos(),
        ];
    }

    /**
     * Construye una respuesta inteligente basada en:
     *
     * - la pregunta del vecino
     * - el estado del evento
     * - los reportes acumulados
     */
    public function construirRespuesta(
        SigiEvento $evento,
        ?array $analisis = null
    ): array {
        $resumen =
            $this->construirResumen($evento);

        $textoPregunta =
            $analisis['texto'] ?? '';

        /*
        |--------------------------------------------------------------------------
        | Pregunta específica
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Consultas generales de Cálidda
        |--------------------------------------------------------------------------
        */

        if (
            $this->preguntaGasGeneral(
                $textoPregunta
            )
        ) {
            return [
                'tipo' => 'consulta_gas',
                'texto' =>
                    $this->respuestaGasGeneral(),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Emergencia de gas
        |--------------------------------------------------------------------------
        */

        if (
            $this->preguntaEmergenciaGas(
                $textoPregunta
            )
        ) {
            return [
                'tipo' => 'emergencia_gas',
                'texto' =>
                    $this->respuestaEmergenciaGas(),
            ];
        }

        if (
            $this->preguntaCuantosSinServicio(
                $textoPregunta
            )
        ) {
            return [
                'tipo' => 'consulta_sin_servicio',
                'texto' =>
                    $this->respuestaCantidadSinServicio(
                        $evento,
                        $resumen
                    ),
            ];
        }

        if (
            $this->preguntaBajaPresion(
                $textoPregunta
            )
        ) {
            return [
                'tipo' => 'consulta_baja_presion',
                'texto' =>
                    $this->respuestaBajaPresion(
                        $evento,
                        $resumen
                    ),
            ];
        }

        if (
            $this->preguntaCantidadReportes(
                $textoPregunta
            )
        ) {
            return [
                'tipo' => 'consulta_reportes',
                'texto' =>
                    $this->respuestaCantidadReportes(
                        $evento,
                        $resumen
                    ),
            ];
        }

        if (
            $this->preguntaYaVolvioParaTodos(
                $textoPregunta
            )
        ) {
            return [
                'tipo' => 'consulta_restablecimiento_total',
                'texto' =>
                    $this->respuestaRestablecimientoTotal(
                        $evento,
                        $resumen
                    ),
            ];
        }

        if (
            $this->preguntaSiContinua(
                $textoPregunta
            )
        ) {
            return [
                'tipo' => 'consulta_estado',
                'texto' =>
                    $this->respuestaEstadoActual(
                        $evento,
                        $resumen
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Emergencia
        |--------------------------------------------------------------------------
        */

        if ($evento->esAltaPrioridad()) {
            return [
                'tipo' => 'emergencia',
                'texto' =>
                    $this->respuestaEmergencia(
                        $evento,
                        $resumen,
                        $analisis
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Evento resuelto
        |--------------------------------------------------------------------------
        */

        if ($evento->estaResuelto()) {
            return [
                'tipo' => 'resuelto',
                'texto' =>
                    $this->respuestaResuelto(
                        $evento,
                        $resumen
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Estado parcial
        |--------------------------------------------------------------------------
        */

        if ($evento->estaParcial()) {
            return [
                'tipo' => 'parcial',
                'texto' =>
                    $this->respuestaParcial(
                        $evento,
                        $resumen
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Evento abierto
        |--------------------------------------------------------------------------
        */

        return [
            'tipo' => 'abierto',
            'texto' =>
                $this->respuestaAbierto(
                    $evento,
                    $resumen
                ),
        ];
    }

    /**
     * Detecta consultas generales relacionadas con Cálidda.
     */
    protected function preguntaGasGeneral(
        string $texto
    ): bool {
        $texto = $this->normalizar($texto);

        return (
            $this->contieneAlguno($texto, [
                'numero de calidda',
                'numero calidda',
                'telefono de calidda',
                'telefono calidda',
                'numero del gas',
                'telefono del gas',
                'numero de gas',
                'telefono de gas',
                'como llamo a calidda',
                'como llamar a calidda',
                'como contacto a calidda',
                'contacto de calidda',
                'calidda atencion al cliente',
                'atencion al cliente calidda',
                'consultas calidda',
                'consulta calidda',
                'recibo de calidda',
                'recibos de calidda',
            ])
        );
    }

    /**
     * Detecta una emergencia relacionada con gas.
     */
    protected function preguntaEmergenciaGas(
        string $texto
    ): bool {
        $texto = $this->normalizar($texto);

        return (
            $this->contieneAlguno($texto, [
                'fuga de gas',
                'fuga gas',
                'hay fuga de gas',
                'olor a gas',
                'olor fuerte a gas',
                'huele a gas',
                'escape de gas',
                'se escapa el gas',
                'gas se esta escapando',
            ])
        );
    }

    /**
     * Respuesta para consultas generales de Cálidda.
     */
    protected function respuestaGasGeneral(): string {
        return
            '🔥 Cálidda: para consultas generales, atención al cliente ' .
            'o información sobre recibos, puedes llamar a Aló Cálidda al 614-9000. ' .
            '🚨 Para emergencias relacionadas con gas, llama al 1808.';
    }

    /**
     * Respuesta para emergencias de gas.
     */
    protected function respuestaEmergenciaGas(): string {
        return
            '🚨 Vecinos, se ha reportado una posible fuga de gas. ' .
            'No enciendan fósforos, interruptores ni aparatos eléctricos. ' .
            'Aléjense de la zona si hay olor intenso a gas.' .
            "\n\n" .
            '🔥 Cálidda Emergencias: 1808' .
            "\n" .
            '🚒 Bomberos: 116';
    }

    /**
     * Detecta preguntas sobre cantidad de vecinos
     * sin servicio.
     */
    protected function preguntaCuantosSinServicio(
        string $texto
    ): bool {
        $texto = $this->normalizar($texto);

        return (
            $this->contieneAlguno($texto, [
                'cuantos vecinos siguen sin agua',
                'cuantos siguen sin agua',
                'cuantos no tienen agua',
                'cuantos vecinos no tienen agua',
                'cuantos estan sin agua',
                'cuantos vecinos estan sin agua',
                'cuantos tienen agua',
            ])
        );
    }

    /**
     * Detecta preguntas sobre baja presión.
     */
    protected function preguntaBajaPresion(
        string $texto
    ): bool {
        $texto = $this->normalizar($texto);

        return (
            $this->contieneAlguno($texto, [
                'cuantos tienen baja presion',
                'alguien tiene baja presion',
                'hay baja presion',
                'quienes tienen baja presion',
                'cuantos tienen poca presion',
                'alguien tiene poca presion',
            ])
        );
    }

    /**
     * Detecta preguntas sobre cantidad de reportes.
     */
    protected function preguntaCantidadReportes(
        string $texto
    ): bool {
        $texto = $this->normalizar($texto);

        return (
            $this->contieneAlguno($texto, [
                'cuantos reportes hay',
                'cuantos reportes tenemos',
                'cuantos han reportado',
                'cuantas personas han reportado',
                'cuantos vecinos reportaron',
                'cuantos vecinos han reportado',
            ])
        );
    }

    /**
     * Detecta preguntas para saber si el servicio
     * ya se restableció para todos.
     */
    protected function preguntaYaVolvioParaTodos(
        string $texto
    ): bool {
        $texto = $this->normalizar($texto);

        return (
            $this->contieneAlguno($texto, [
                'ya volvio para todos',
                'ya volvio el agua para todos',
                'ya se soluciono para todos',
                'ya se soluciono el problema para todos',
                'todos tienen agua',
                'ya tienen agua todos',
                'ya tienen agua',
                'volvio para todos',
                'se soluciono para todos',
            ])
        );
    }

    /**
     * Responde específicamente si el servicio
     * ya volvió para todos los vecinos.
     */
    protected function respuestaRestablecimientoTotal(
        SigiEvento $evento,
        array $resumen
    ): string {
        $afectados =
            $resumen['usuarios_afectados'];

        $restablecidos =
            $resumen['usuarios_restablecidos'];

        $categoria =
            $this->nombreCategoria(
                $evento->categoria
            );

        if ($afectados > 0) {
            return sprintf(
                '💧 No, todavía no ha vuelto para todos. Actualmente hay %d vecino%s que reporta%s problemas con el servicio de %s.',
                $afectados,
                $afectados === 1 ? '' : 's',
                $afectados === 1 ? '' : 'n',
                $categoria
            );
        }

        if ($restablecidos > 0) {
            return sprintf(
                '✅ Sí. Según los últimos reportes registrados, el servicio de %s ya se encuentra restablecido para todos los vecinos que habían reportado el problema.',
                $categoria
            );
        }

        return sprintf(
            'ℹ️ SIGI todavía no cuenta con suficientes reportes para confirmar si el servicio de %s volvió para todos.',
            $categoria
        );
    }

    /**
     * Detecta preguntas sobre continuidad del problema.
     */
    protected function preguntaSiContinua(
        string $texto
    ): bool {
        $texto = $this->normalizar($texto);

        return (
            $this->contieneAlguno($texto, [
                'todavia hay vecinos sin agua',
                'todavia hay vecinos que no tienen agua',
                'sigue el problema',
                'todavia sigue',
                'continua el problema',
                'continua sin agua',
                'todavia no viene el agua',
                'sigue sin venir el agua',
                'ya se soluciono',
                'ya se solucionó',
                'ya se soluciono el problema',
                'ya se solucionó el problema',
                'ya volvio para todos',
                'ya volvió para todos',
            ])
        );
    }

    /**
     * Normaliza texto para las preguntas específicas.
     */
    protected function normalizar(
        string $texto
    ): string {
        $texto = mb_strtolower(
            trim($texto),
            'UTF-8'
        );

        $texto = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $texto
        );

        $texto = preg_replace(
            '/\s+/',
            ' ',
            $texto
        );

        return trim($texto);
    }

    /**
     * Comprueba si contiene alguno de los términos.
     */
    protected function contieneAlguno(
        string $texto,
        array $terminos
    ): bool {
        foreach ($terminos as $termino) {
            if (
                str_contains(
                    $texto,
                    $termino
                )
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Respuesta: cantidad de vecinos sin servicio.
     */
    protected function respuestaCantidadSinServicio(
        SigiEvento $evento,
        array $resumen
    ): string {
        $cantidad =
            $resumen['usuarios_afectados'];

        if ($cantidad === 0) {
            return sprintf(
                '💧 Según los últimos reportes registrados, actualmente no hay vecinos identificados como afectados por el servicio de %s.',
                $this->nombreCategoria(
                    $evento->categoria
                )
            );
        }

        return sprintf(
            '💧 Según los últimos reportes registrados, actualmente hay %d vecino%s que todavía reporta%s falta de %s.',
            $cantidad,
            $cantidad === 1 ? '' : 's',
            $cantidad === 1 ? '' : 'n',
            $this->nombreCategoria(
                $evento->categoria
            )
        );
    }

    /**
     * Respuesta: baja presión.
     *
     * Utilizamos usuarios únicos en lugar de contar
     * todos los reportes históricos.
     */
    protected function respuestaBajaPresion(
        SigiEvento $evento,
        array $resumen
    ): string {
        $cantidad =
            $evento->cantidadUsuariosPorTipo(
                'baja_presion'
            );

        if ($cantidad === 0) {
            return sprintf(
                '💧 Hasta el momento no se han registrado vecinos con baja presión en el servicio de %s.',
                $this->nombreCategoria(
                    $evento->categoria
                )
            );
        }

        return sprintf(
            '💧 Actualmente hay %d vecino%s que reporta%s baja presión en el servicio de %s.',
            $cantidad,
            $cantidad === 1 ? '' : 's',
            $cantidad === 1 ? '' : 'n',
            $this->nombreCategoria(
                $evento->categoria
            )
        );
    }

    /**
     * Respuesta: cantidad de reportes.
     */
    protected function respuestaCantidadReportes(
        SigiEvento $evento,
        array $resumen
    ): string {
        return sprintf(
            '📊 SIGI tiene registrados %d reportes relacionados con el problema de %s.',
            $resumen['total'],
            $this->nombreCategoria(
                $evento->categoria
            )
        );
    }

    /**
     * Respuesta: estado actual.
     */
    protected function respuestaEstadoActual(
        SigiEvento $evento,
        array $resumen
    ): string {
        $afectados =
            $resumen['usuarios_afectados'];

        $restablecidos =
            $resumen['usuarios_restablecidos'];

        if (
            $afectados > 0 &&
            $restablecidos > 0
        ) {
            return sprintf(
                '💧 El problema de %s continúa de forma parcial: %d vecino%s reporta%s que todavía %s problemas y %d vecino%s ya informó%s que cuenta%s nuevamente con el servicio.',
                $this->nombreCategoria(
                    $evento->categoria
                ),
                $afectados,
                $afectados === 1 ? '' : 's',
                $afectados === 1 ? '' : 'n',
                $afectados === 1
                    ? 'tiene'
                    : 'tienen',
                $restablecidos,
                $restablecidos === 1 ? '' : 's',
                $restablecidos === 1 ? '' : '',
                $restablecidos === 1 ? '' : '',
                $restablecidos === 1 ? '' : ''
            );
        }

        if ($afectados > 0) {
            return sprintf(
                '💧 Según los últimos reportes, el problema de %s continúa. Hay %d vecino%s que todavía reporta%s problemas.',
                $this->nombreCategoria(
                    $evento->categoria
                ),
                $afectados,
                $afectados === 1 ? '' : 's',
                $afectados === 1 ? '' : 'n'
            );
        }

        if ($restablecidos > 0) {
            return sprintf(
                '✅ Se ha registrado que %d vecino%s informó%s que el servicio de %s ya fue restablecido.',
                $restablecidos,
                $restablecidos === 1 ? '' : 's',
                $restablecidos === 1 ? '' : 'aron',
                $this->nombreCategoria(
                    $evento->categoria
                )
            );
        }

        return sprintf(
            'ℹ️ SIGI continúa recopilando información sobre el problema de %s.',
            $this->nombreCategoria(
                $evento->categoria
            )
        );
    }

    /**
     * Respuesta para una emergencia.
     */
    protected function respuestaEmergencia(
        SigiEvento $evento,
        array $resumen,
        ?array $analisis = null
    ): string {
        $texto =
            $this->normalizar(
                $analisis['texto'] ?? ''
            );

        /*
        |--------------------------------------------------------------------------
        | Identificación específica de la emergencia
        |--------------------------------------------------------------------------
        |
        | El evento puede tener la categoría genérica "emergencia".
        | Para la respuesta al vecino usamos también el texto del
        | mensaje para explicar qué tipo de emergencia se detectó.
        |
        */

        $tipo = 'emergencia';

        if (
            $this->contieneAlguno($texto, [
                'incendio',
                'hay fuego',
                'se quema',
                'fuego en',
                'humo',
            ])
        ) {
            $tipo = 'incendio';
        } elseif (
            $this->contieneAlguno($texto, [
                'robo',
                'estan robando',
                'están robando',
                'asalto',
                'asaltando',
                'ladrones',
                'ladron',
                'ladronas',
                'pandilleros',
                'pandilla',
                'delincuentes',
                'delincuencia',
                'inseguridad',
            ])
        ) {
            $tipo = 'seguridad';
        } elseif (
            $this->contieneAlguno($texto, [
                'accidente',
                'persona tirada',
                'persona herida',
                'herido',
                'herida',
                'atropello',
                'atropellado',
                'ambulancia',
            ])
        ) {
            $tipo = 'accidente';
        }

        /*
        |--------------------------------------------------------------------------
        | Contactos institucionales
        |--------------------------------------------------------------------------
        |
        | 105  = Policía
        | 116  = Bomberos
        | Villa El Salvador:
        | - Serenazgo: (01) 510-0200 / 959257663
        | - Bomberos VES: (01) 287-7423
        | - Comisaría VES: (01) 287-3804
        |
        */

        $contactos = '';

        if ($tipo === 'incendio') {
            $contactos =
                "\n\n🚒 Bomberos: 116\n" .
                "📞 Bomberos de Villa El Salvador: (01) 287-7423\n" .
                "🚓 Policía: 105\n" .
                "🛡️ Serenazgo de Villa El Salvador: (01) 510-0200 / 959257663";

            $mensaje =
                '🔥 Vecinos, se ha reportado un incendio. ' .
                'Mantengan la calma, aléjense de la zona de peligro ' .
                'y eviten exponerse al fuego.';
        } elseif ($tipo === 'seguridad') {
            $contactos =
                "\n\n🚓 Policía: 105\n" .
                "🛡️ Serenazgo de Villa El Salvador: (01) 510-0200 / 959257663\n" .
                "📞 Comisaría de Villa El Salvador: (01) 287-3804";

            $mensaje =
                '🚨 Vecinos, se ha reportado una situación de inseguridad. ' .
                'Mantengan la calma, eviten enfrentamientos y aléjense de la zona de peligro.';
        } elseif ($tipo === 'accidente') {
            $contactos =
                "\n\n🚑 SAMU: 106\n" .
                "🚓 Policía: 105\n" .
                "🛡️ Serenazgo de Villa El Salvador: (01) 510-0200 / 959257663";

            $mensaje =
                '🚑 Vecinos, se ha reportado un accidente con una persona que necesita atención. ' .
                'Eviten mover a la persona, salvo que exista un peligro inmediato, ' .
                'y soliciten ayuda de emergencia.';
        } else {
            $contactos =
                "\n\n🚒 Bomberos: 116\n" .
                "🚓 Policía: 105\n" .
                "🛡️ Serenazgo de Villa El Salvador: (01) 510-0200 / 959257663";

            $mensaje =
                '🚨 Vecinos, se ha identificado una situación de emergencia. ' .
                'Se recomienda mantener la calma y tomar las medidas de seguridad correspondientes.';
        }

        return $mensaje . $contactos;
    }

    /**
     * Respuesta para un evento resuelto.
     */
    protected function respuestaResuelto(
        SigiEvento $evento,
        array $resumen
    ): string {
        $usuarios =
            $resumen['usuarios_restablecidos'];

        if ($usuarios > 0) {
            return sprintf(
                '✅ Vecinos, se ha registrado que el servicio de %s fue restablecido. %d vecino%s informó que ya cuenta con el servicio.',
                $this->nombreCategoria(
                    $evento->categoria
                ),
                $usuarios,
                $usuarios === 1 ? '' : 's'
            );
        }

        return sprintf(
            '✅ Vecinos, el evento relacionado con %s figura como resuelto.',
            $this->nombreCategoria(
                $evento->categoria
            )
        );
    }

    /**
     * Respuesta para un evento parcialmente resuelto.
     */
    protected function respuestaParcial(
        SigiEvento $evento,
        array $resumen
    ): string {
        $afectados =
            $resumen['usuarios_afectados'];

        $restablecidos =
            $resumen['usuarios_restablecidos'];

        if (
            $afectados > 0 &&
            $restablecidos > 0
        ) {
            return sprintf(
                '💧 Vecinos, el servicio de %s se ha restablecido para algunos vecinos, pero todavía hay %d vecino%s que reporta%s problemas.',
                $this->nombreCategoria(
                    $evento->categoria
                ),
                $afectados,
                $afectados === 1 ? '' : 's',
                $afectados === 1 ? '' : 'n'
            );
        }

        return sprintf(
            'ℹ️ Vecinos, el evento de %s se encuentra en estado parcial.',
            $this->nombreCategoria(
                $evento->categoria
            )
        );
    }

    /**
     * Respuesta para un evento todavía abierto.
     */
    protected function respuestaAbierto(
        SigiEvento $evento,
        array $resumen
    ): string {
        $afectados =
            $resumen['usuarios_afectados'];

        if ($afectados > 0) {
            return sprintf(
                '💧 Vecinos, según los últimos reportes registrados, todavía hay %d vecino%s afectado%s por el problema de %s.',
                $afectados,
                $afectados === 1 ? '' : 's',
                $afectados === 1 ? '' : 's',
                $this->nombreCategoria(
                    $evento->categoria
                )
            );
        }

        return sprintf(
            'ℹ️ Vecinos, SIGI continúa recopilando información sobre el problema de %s.',
            $this->nombreCategoria(
                $evento->categoria
            )
        );
    }

    /**
     * Convierte la categoría interna en un nombre legible.
     */
    protected function nombreCategoria(
        ?string $categoria
    ): string {
        return match ($categoria) {
            'agua' => 'agua',
            'luz' => 'energía eléctrica',
            'seguridad' => 'seguridad',
            'gas' => 'gas natural',
            'emergencia' => 'la emergencia',
            default => $categoria ?: 'servicio',
        };
    }

    /**
     * Determina si el evento tiene suficientes reportes.
     */
    public function tieneSuficientesReportes(
        SigiEvento $evento,
        int $minimo = 3
    ): bool {
        return $evento->tieneMultiplesReportes(
            $minimo
        );
    }

    /**
     * Determina si SIGI debe mantenerse en silencio.
     */
    public function debeGuardarSilencio(
        SigiEvento $evento,
        ?array $analisis = null
    ): bool {
        $decision =
            $this->evaluar(
                $evento,
                $analisis
            );

        return !$decision['debe_intervenir'];
    }

    /**
     * Registra una intervención.
     */
    public function registrarIntervencion(
        SigiEvento $evento
    ): void {
        $evento->ultima_intervencion_at =
            now();

        $evento->save();
    }
}