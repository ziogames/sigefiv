<?php

namespace App\Services\ConsultaInteligente;

class ConsultaInteligenteOperacionService
{
    public function detectarOperacion(string $texto): string
    {
        /*
        |--------------------------------------------------------------------------
        | SALUDOS
        |--------------------------------------------------------------------------
        */

        $textoLimpio =
            trim(
                preg_replace(
                    '/[¿?¡!.,;:]+/u',
                    ' ',
                    $texto
                )
            );

        $textoLimpio =
            trim(
                preg_replace(
                    '/\s+/u',
                    ' ',
                    $textoLimpio
                )
            );

        /*
        |--------------------------------------------------------------------------
        | SALUDOS NATURALES
        |--------------------------------------------------------------------------
        */

        $esSaludoNatural =
            preg_match(
                '/^(?:hola|hey|buenas)(?:\s+(?:vecino|vecina|vecinos|vecinas|amigo|amiga|amigos|amigas))?(?:\s+(?:sigi|asistente))?\s+(?:como estas|cómo estas|cómo estás|como esta|cómo está|que tal|qué tal)$/u',
                $textoLimpio
            ) ||
            preg_match(
                '/^(?:hola|hey|buenas)(?:\s+(?:vecino|vecina|vecinos|vecinas|amigo|amiga|amigos|amigas))?(?:\s+(?:sigi|asistente))?$/u',
                $textoLimpio
            ) ||
            preg_match(
                '/^(?:buenos dias|buenos días|buenas tardes|buenas noches)(?:\s+(?:vecino|vecina|vecinos|vecinas|amigo|amiga|amigos|amigas))?(?:\s+(?:sigi|asistente))?$/u',
                $textoLimpio
            );

        if ($esSaludoNatural) {
            return 'greeting';
        }

        /*
        |--------------------------------------------------------------------------
        | CONVERSACIÓN GENERAL
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^(?:cómo estás|como estas|qué tal|que tal|qué tal sigi|que tal sigi|quiero conversar contigo|quiero conversar|solo quiero conversar|hablemos un rato|podemos conversar)$/u',
                $textoLimpio
            )
        ) {
            return 'conversation';
        }

        /*
        |--------------------------------------------------------------------------
        | AYUDA / CONSULTA CONVERSACIONAL
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/\b(tengo una duda|tengo una pregunta|puedes ayudarme|me puedes ayudar|necesito ayuda|quiero saber cómo|quiero saber como|explícame|explicame|me puedes explicar|puedes explicarme|cómo funciona|como funciona|quisiera saber cómo|quisiera saber como)\b/u',
                $texto
            )
        ) {
            return 'conversation';
        }

        /*
        |--------------------------------------------------------------------------
        | MAYORES INGRESOS POR MES
        |--------------------------------------------------------------------------
        */

        if (
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'mayor') &&
                str_contains($texto, 'ingreso')
            ) ||
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'más') &&
                str_contains($texto, 'ingreso')
            ) ||
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'mas') &&
                str_contains($texto, 'ingreso')
            ) ||
            str_contains($texto, 'mayor ingreso') ||
            str_contains($texto, 'mayores ingresos') ||
            str_contains($texto, 'más ingresos') ||
            str_contains($texto, 'mas ingresos') ||
            str_contains($texto, 'mes con mayor ingreso') ||
            str_contains($texto, 'mes con mayores ingresos') ||
            str_contains($texto, 'mes que mayor ingreso') ||
            str_contains($texto, 'mes que mayores ingresos')
        ) {
            return 'max_mes_ingreso';
        }

        /*
        |--------------------------------------------------------------------------
        | MENORES INGRESOS POR MES
        |--------------------------------------------------------------------------
        */

        if (
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'menor') &&
                str_contains($texto, 'ingreso')
            ) ||
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'menos') &&
                str_contains($texto, 'ingreso')
            ) ||
            str_contains($texto, 'menor ingreso') ||
            str_contains($texto, 'menores ingresos') ||
            str_contains($texto, 'menos ingresos')
        ) {
            return 'min_mes_ingreso';
        }

        /*
        |--------------------------------------------------------------------------
        | MAYORES EGRESOS POR MES
        |--------------------------------------------------------------------------
        */

        if (
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'mayor') &&
                (
                    str_contains($texto, 'egreso') ||
                    str_contains($texto, 'gasto')
                )
            ) ||
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'más') &&
                (
                    str_contains($texto, 'egreso') ||
                    str_contains($texto, 'gasto')
                )
            ) ||
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'mas') &&
                (
                    str_contains($texto, 'egreso') ||
                    str_contains($texto, 'gasto')
                )
            ) ||
            str_contains($texto, 'mayores egresos') ||
            str_contains($texto, 'mayores gastos') ||
            str_contains($texto, 'más egresos') ||
            str_contains($texto, 'mas egresos') ||
            str_contains($texto, 'más gastos') ||
            str_contains($texto, 'mas gastos')
        ) {
            return 'max_mes';
        }

        /*
        |--------------------------------------------------------------------------
        | MENORES EGRESOS POR MES
        |--------------------------------------------------------------------------
        */

        if (
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'menor') &&
                (
                    str_contains($texto, 'egreso') ||
                    str_contains($texto, 'gasto')
                )
            ) ||
            (
                str_contains($texto, 'mes') &&
                str_contains($texto, 'menos') &&
                (
                    str_contains($texto, 'egreso') ||
                    str_contains($texto, 'gasto')
                )
            ) ||
            str_contains($texto, 'menores egresos') ||
            str_contains($texto, 'menores gastos') ||
            str_contains($texto, 'menos egresos') ||
            str_contains($texto, 'menos gastos')
        ) {
            return 'min_mes';
        }

        /*
        |--------------------------------------------------------------------------
        | MESES EN LOS QUE APARECE UN CONCEPTO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'meses') &&
            (
                str_contains($texto, 'cuales') ||
                str_contains($texto, 'cuáles') ||
                str_contains($texto, 'que') ||
                str_contains($texto, 'qué') ||
                str_contains($texto, 'en los que') ||
                str_contains($texto, 'en los cuales')
            ) &&
            (
                str_contains($texto, 'pago') ||
                str_contains($texto, 'pagos') ||
                str_contains($texto, 'aparece') ||
                str_contains($texto, 'apareció') ||
                str_contains($texto, 'hubo')
            )
        ) {
            return 'meses_concepto';
        }

        /*
        |--------------------------------------------------------------------------
        | LISTA / DETALLE DE INGRESOS Y EGRESOS
        |--------------------------------------------------------------------------
        */

        $esLista =
            str_contains($texto, 'lista') ||
            str_contains($texto, 'listado') ||
            str_contains($texto, 'detalle') ||
            str_contains($texto, 'detallame') ||
            str_contains($texto, 'detállame') ||
            str_contains($texto, 'muéstrame') ||
            str_contains($texto, 'muestrame') ||
            str_contains($texto, 'mostrar') ||
            str_contains($texto, 'enséñame') ||
            str_contains($texto, 'ensename') ||
            str_contains($texto, 'dame el detalle') ||
            str_contains($texto, 'qué ingresos tuvimos') ||
            str_contains($texto, 'que ingresos tuvimos') ||
            str_contains($texto, 'qué egresos tuvimos') ||
            str_contains($texto, 'que egresos tuvimos') ||
            str_contains($texto, 'qué gastos tuvimos') ||
            str_contains($texto, 'que gastos tuvimos') ||
            str_contains($texto, 'qué ingresos hubo') ||
            str_contains($texto, 'que ingresos hubo') ||
            str_contains($texto, 'qué egresos hubo') ||
            str_contains($texto, 'que egresos hubo') ||
            str_contains($texto, 'qué gastos hubo') ||
            str_contains($texto, 'que gastos hubo');

        $esIngreso =
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresamos') ||
            str_contains($texto, 'recaud');

        $esEgreso =
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastamos');

        /*
        |--------------------------------------------------------------------------
        | PAGOS COMO SOLICITUD DE LISTA
        |--------------------------------------------------------------------------
        */

        $esPago =
            str_contains($texto, 'pago') ||
            str_contains($texto, 'pagos');

        if (
            $esLista &&
            ($esIngreso || $esEgreso || $esPago)
        ) {
            return 'show';
        }

        /*
        |--------------------------------------------------------------------------
        | CANTIDAD
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        | Este bloque debe estar ANTES de SUMA.
        |
        | "¿Cuántos ingresos tuvimos?"
        | "¿Cuántos egresos hubo en junio?"
        | "¿Cuántos movimientos tenemos?"
        |
        | significan CONTAR registros.
        |
        */

        if (
            str_contains($texto, 'cuántos') ||
            str_contains($texto, 'cuantos') ||
            str_contains($texto, 'cantidad') ||
            str_contains($texto, 'número de') ||
            str_contains($texto, 'numero de') ||
            str_contains($texto, 'cuántas') ||
            str_contains($texto, 'cuantas')
        ) {
            return 'count';
        }

        /*
        |--------------------------------------------------------------------------
        | SUMA
        |--------------------------------------------------------------------------
        |
        | "cuánto" pregunta por dinero/monto.
        |
        */

        if (
            str_contains($texto, 'suma') ||
            str_contains($texto, 'sumar') ||
            str_contains($texto, 'total') ||
            str_contains($texto, 'cuánto') ||
            str_contains($texto, 'cuanto') ||
            str_contains($texto, 'recaudó') ||
            str_contains($texto, 'recaudo') ||
            str_contains($texto, 'recaudaron') ||
            str_contains($texto, 'ingresó') ||
            str_contains($texto, 'ingreso total')
        ) {
            return 'sum';
        }

        /*
        |--------------------------------------------------------------------------
        | PROMEDIO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'promedio') ||
            str_contains($texto, 'media')
        ) {
            return 'avg';
        }

        /*
        |--------------------------------------------------------------------------
        | CLIMA
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'clima') ||
            str_contains($texto, 'tiempo') ||
            str_contains($texto, 'temperatura') ||
            str_contains($texto, 'temperaturas') ||
            str_contains($texto, 'llover') ||
            str_contains($texto, 'lluvia') ||
            str_contains($texto, 'lluvias') ||
            str_contains($texto, 'pronóstico') ||
            str_contains($texto, 'pronostico') ||
            str_contains($texto, 'cómo estará') ||
            str_contains($texto, 'como estara') ||
            str_contains($texto, 'cómo está el tiempo') ||
            str_contains($texto, 'como esta el tiempo')
        ) {
            return 'weather';
        }

        /*
        |--------------------------------------------------------------------------
        | CHISTES
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'chiste') ||
            str_contains($texto, 'chistes') ||
            str_contains($texto, 'cuéntame algo gracioso') ||
            str_contains($texto, 'cuentame algo gracioso') ||
            str_contains($texto, 'hazme reír') ||
            str_contains($texto, 'hazme reir') ||
            str_contains($texto, 'quiero reírme') ||
            str_contains($texto, 'quiero reirme')
        ) {
            return 'joke';
        }

        /*
        |--------------------------------------------------------------------------
        | MAYOR GENERICO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'mayor') ||
            str_contains($texto, 'máximo') ||
            str_contains($texto, 'maximo') ||
            str_contains($texto, 'más grande')
        ) {
            return 'max';
        }

        /*
        |--------------------------------------------------------------------------
        | MENOR GENERICO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'menor') ||
            str_contains($texto, 'mínimo') ||
            str_contains($texto, 'minimo') ||
            str_contains($texto, 'más pequeño')
        ) {
            return 'min';
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTAS DE PERIODOS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'periodo actual') ||
            str_contains($texto, 'período actual') ||
            str_contains($texto, 'periodo vigente') ||
            str_contains($texto, 'período vigente') ||
            str_contains($texto, 'periodo abierto') ||
            str_contains($texto, 'período abierto') ||
            str_contains($texto, 'periodo de ahora') ||
            str_contains($texto, 'período de ahora') ||
            str_contains($texto, 'en que periodo estamos') ||
            str_contains($texto, 'en qué periodo estamos') ||
            str_contains($texto, 'en que período estamos') ||
            str_contains($texto, 'en qué período estamos')
        ) {
            return 'show';
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTA FINANCIERA SIMPLE
        |--------------------------------------------------------------------------
        */

        $terminosFinancieros = [
            'ingreso',
            'ingresos',
            'ingresamos',
            'recaudación',
            'recaudacion',
            'recaudamos',

            'egreso',
            'egresos',
            'egresamos',

            'gasto',
            'gastos',
            'gastamos',

            'pago',
            'pagos',

            'saldo',
            'caja',

            'movimiento',
            'movimientos',
        ];

        foreach ($terminosFinancieros as $termino) {

            if (
                str_contains(
                    $texto,
                    $termino
                )
            ) {
                return 'sum';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CONVERSACIÓN GENERAL / OLLAMA
        |--------------------------------------------------------------------------
        */

        return 'conversation';
    }
}