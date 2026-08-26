<?php

namespace App\Services;

use App\Models\Categoria;
use App\Models\Movimiento;

class ConsultaInteligenteService
{
    /**
     * Interpreta una consulta escrita en lenguaje natural.
     */
    public function interpretar(string $consulta): array
    {
        $texto = mb_strtolower(
            trim($consulta)
        );

        $tabla = $this->detectarTabla($texto);

        $operacion = $this->detectarOperacion($texto);

        /*
        |--------------------------------------------------------------------------
        | CONSULTAS DE USUARIOS Y ROLES
        |--------------------------------------------------------------------------
        |
        | Las consultas administrativas sobre usuarios y roles deben
        | ejecutarse dentro de SIGEFIV y no enviarse a Ollama.
        |
        | detectarOperacion() necesita conservar "conversation" para
        | preguntas realmente conversacionales como:
        |
        | "explícame cómo funcionan los roles"
        |
        | pero expresiones como:
        |
        | "muéstrame los usuarios del sistema"
        | "dame los roles"
        | "qué usuarios existen"
        |
        | son consultas directas de datos.
        |
        */

        if (
            in_array(
                $tabla,
                [
                    'usuarios',
                    'roles',
                ],
                true
            ) &&
            $operacion === 'conversation'
        ) {

            $esConsultaDirecta =
                str_contains($texto, 'muéstrame') ||
                str_contains($texto, 'muestrame') ||
                str_contains($texto, 'mostrar') ||
                str_contains($texto, 'dame') ||
                str_contains($texto, 'lista') ||
                str_contains($texto, 'listado') ||
                str_contains($texto, 'quiénes') ||
                str_contains($texto, 'quienes') ||
                str_contains($texto, 'qué usuarios') ||
                str_contains($texto, 'que usuarios') ||
                str_contains($texto, 'qué roles') ||
                str_contains($texto, 'que roles') ||
                str_contains($texto, 'usuarios del sistema') ||
                str_contains($texto, 'roles del sistema') ||
                str_contains($texto, 'usuarios existen') ||
                str_contains($texto, 'roles existen');

            if ($esConsultaDirecta) {

                $operacion = 'show';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTAS DE USUARIOS POR ROL
        |--------------------------------------------------------------------------
        |
        | Estas consultas deben tener prioridad sobre la detección genérica
        | de "roles".
        |
        | Ejemplos:
        |
        | "¿Cuántos administradores hay?"
        | "¿Cuántos tesoreros hay?"
        | "¿Cuántos secretarios hay?"
        | "Muéstrame los usuarios que tienen el rol Consulta"
        | "¿Quién es el administrador?"
        |
        | En estos casos la tabla correcta es "usuarios".
        |
        */

        if (
            $this->esConsultaUsuariosPorRol($texto)
        ) {

            $tabla = 'usuarios';

            /*
             * Si la pregunta solicita una cantidad, usamos count.
             *
             * Esto debe hacerse después de detectar la intención de
             * usuarios por rol porque "cuántos administradores" contiene
             * también palabras que podrían llevarnos a roles.
             */

            if (
                $this->esConsultaCantidad($texto)
            ) {

                $operacion = 'count';

            } elseif (
                $operacion === 'conversation' ||
                $this->esConsultaListaUsuarios($texto)
            ) {

                $operacion = 'show';
            }
        }


        return [

            'consulta_original' =>
                $consulta,

            'tabla' =>
                $tabla,

            'operacion' =>
                $operacion,

            'fecha' =>
                $this->detectarFecha($texto),

            'categoria' =>
                $this->detectarCategoria($texto),

            'concepto' =>
                $this->detectarConcepto(
                    $texto,
                    $operacion
                ),

            'texto' =>
                $texto,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR TABLA
    |--------------------------------------------------------------------------
    */

    private function detectarTabla(string $texto): ?string
    {
        /*
        |--------------------------------------------------------------------------
        | MOVIMIENTOS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'alquiler') ||
            str_contains($texto, 'alquileres') ||
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresos') ||
            str_contains($texto, 'ingresamos') ||
            str_contains($texto, 'ingresó') ||
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'egresos') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastos') ||
            str_contains($texto, 'gastamos') ||
            str_contains($texto, 'gastó') ||
            str_contains($texto, 'movimiento') ||
            str_contains($texto, 'movimientos') ||
            str_contains($texto, 'recaud') ||
            str_contains($texto, 'gast') ||
            str_contains($texto, 'pago') ||
            str_contains($texto, 'pagos') ||
            str_contains($texto, 'servicio') ||
            str_contains($texto, 'agua') ||
            str_contains($texto, 'luz') ||
            str_contains($texto, 'electricidad')
        ) {

            return 'movimientos';
        }


        /*
        |--------------------------------------------------------------------------
        | SEGUIMIENTOS FINANCIEROS NATURALES
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/\bcu[aá]nto\s+ingresamos\b/u',
                $texto
            ) ||
            preg_match(
                '/\bcu[aá]nto\s+ingres[oó]\b/u',
                $texto
            ) ||
            preg_match(
                '/\bcu[aá]nto\s+recaudamos\b/u',
                $texto
            ) ||
            preg_match(
                '/\bcu[aá]nto\s+gastamos\b/u',
                $texto
            ) ||
            preg_match(
                '/\bcu[aá]nto\s+gast[oó]\b/u',
                $texto
            ) ||
            preg_match(
                '/\bcu[aá]nto\s+egresamos\b/u',
                $texto
            ) ||
            preg_match(
                '/\b(?:los|las)?\s*ingresos\b/u',
                $texto
            ) ||
            preg_match(
                '/\b(?:los|las)?\s*egresos\b/u',
                $texto
            )
        ) {

            return 'movimientos';
        }


        /*
        |--------------------------------------------------------------------------
        | LISTAS / DETALLES DE MOVIMIENTOS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'qué ingresos') ||
            str_contains($texto, 'que ingresos') ||
            str_contains($texto, 'qué egresos') ||
            str_contains($texto, 'que egresos') ||
            str_contains($texto, 'qué gastos') ||
            str_contains($texto, 'que gastos')
        ) {

            return 'movimientos';
        }


        /*
        |--------------------------------------------------------------------------
        | PERIODOS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'periodo') ||
            str_contains($texto, 'período') ||
            str_contains($texto, 'saldo') ||
            str_contains($texto, 'cierre') ||
            str_contains($texto, 'saldo final') ||
            str_contains($texto, 'saldo inicial')
        ) {

            return 'periodos';
        }


        /*
        |--------------------------------------------------------------------------
        | USUARIOS POR ROL
        |--------------------------------------------------------------------------
        |
        | Esta detección se ejecuta ANTES de "roles".
        |
        | Es importante porque palabras como "administrador", "tesorero"
        | y "secretario" representan usuarios cuando aparecen en preguntas
        | que buscan personas.
        |
        */

        if (
            $this->esConsultaUsuariosPorRol($texto)
        ) {

            return 'usuarios';
        }


        /*
        |--------------------------------------------------------------------------
        | ROLES
        |--------------------------------------------------------------------------
        |
        | Aquí quedan las consultas que realmente preguntan por los roles
        | registrados en SIGEFIV.
        |
        */

        if (
            str_contains($texto, 'rol') ||
            str_contains($texto, 'roles')
        ) {

            return 'roles';
        }


        /*
        |--------------------------------------------------------------------------
        | USUARIOS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'usuario') ||
            str_contains($texto, 'usuarios') ||
            str_contains($texto, 'persona') ||
            str_contains($texto, 'personas')
        ) {

            return 'usuarios';
        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORIAS
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'categoría') ||
            str_contains($texto, 'categoria') ||
            str_contains($texto, 'categorías') ||
            str_contains($texto, 'categorias')
        ) {

            return 'categorias';
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR CONSULTA DE USUARIOS POR ROL
    |--------------------------------------------------------------------------
    */

    private function esConsultaUsuariosPorRol(
        string $texto
    ): bool {

        $tieneRolAdministrativo =
            str_contains($texto, 'administrador') ||
            str_contains($texto, 'administradores') ||
            str_contains($texto, 'secretario') ||
            str_contains($texto, 'secretarios') ||
            str_contains($texto, 'tesorero') ||
            str_contains($texto, 'tesoreros');


        $tieneRolConsulta =
            str_contains($texto, 'rol consulta') ||
            str_contains($texto, 'rol de consulta') ||
            str_contains($texto, 'rol "consulta"') ||
            str_contains($texto, "rol 'consulta'") ||
            str_contains($texto, 'rol consulta');


        $preguntaResponsable =
            str_contains($texto, 'quien es') ||
            str_contains($texto, 'quién es') ||
            str_contains($texto, 'quienes son') ||
            str_contains($texto, 'quiénes son') ||
            str_contains($texto, 'quien administra') ||
            str_contains($texto, 'quién administra') ||
            str_contains($texto, 'quien tiene el rol') ||
            str_contains($texto, 'quién tiene el rol') ||
            str_contains($texto, 'dime quien') ||
            str_contains($texto, 'dime quién');


        $preguntaUsuarios =
            str_contains($texto, 'usuario') ||
            str_contains($texto, 'usuarios') ||
            str_contains($texto, 'persona') ||
            str_contains($texto, 'personas');


        $preguntaCantidad =
            $this->esConsultaCantidad(
                $texto
            );


        $preguntaLista =
            $this->esConsultaListaUsuarios(
                $texto
            );


        /*
         * Administrador, Secretario o Tesorero:
         *
         * "¿Cuántos administradores hay?"
         * "¿Quién es el tesorero?"
         * "Muéstrame los secretarios"
         */

        if (
            $tieneRolAdministrativo &&
            (
                $preguntaResponsable ||
                $preguntaCantidad ||
                $preguntaLista ||
                $preguntaUsuarios
            )
        ) {

            return true;
        }


        /*
         * Rol Consulta:
         *
         * "Muéstrame los usuarios que tienen el rol Consulta."
         * "¿Cuántos usuarios tienen el rol Consulta?"
         */

        if (
            $tieneRolConsulta &&
            (
                $preguntaUsuarios ||
                $preguntaCantidad ||
                $preguntaLista
            )
        ) {

            return true;
        }


        /*
         * "Muéstrame los usuarios con rol Consulta"
         *
         * Esta forma puede no contener la expresión exacta "rol consulta"
         * si existe puntuación o palabras intermedias, por lo que comprobamos
         * también la combinación general de usuario + consulta.
         */

        if (
            str_contains($texto, 'consulta') &&
            $preguntaUsuarios &&
            (
                str_contains($texto, 'rol') ||
                str_contains($texto, 'tienen') ||
                str_contains($texto, 'tiene')
            )
        ) {

            return true;
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR CANTIDAD
    |--------------------------------------------------------------------------
    */

    private function esConsultaCantidad(
        string $texto
    ): bool {

        return
            str_contains($texto, 'cuántos') ||
            str_contains($texto, 'cuantos') ||
            str_contains($texto, 'cuántas') ||
            str_contains($texto, 'cuantas') ||
            str_contains($texto, 'cantidad') ||
            str_contains($texto, 'número de') ||
            str_contains($texto, 'numero de');
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR LISTA DE USUARIOS
    |--------------------------------------------------------------------------
    */

    private function esConsultaListaUsuarios(
        string $texto
    ): bool {

        return
            str_contains($texto, 'muéstrame') ||
            str_contains($texto, 'muestrame') ||
            str_contains($texto, 'mostrar') ||
            str_contains($texto, 'dame') ||
            str_contains($texto, 'lista') ||
            str_contains($texto, 'listado') ||
            str_contains($texto, 'quiénes') ||
            str_contains($texto, 'quienes') ||
            str_contains($texto, 'usuarios del sistema') ||
            str_contains($texto, 'usuarios existen');
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR OPERACION
    |--------------------------------------------------------------------------
    */

    private function detectarOperacion(string $texto): string
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

        if (
            preg_match(
                '/^(?:hola|hola sigi|buenos dias|buenos días|buenas tardes|buenas noches)(?: sigi)?$/u',
                $textoLimpio
            )
        ) {

            return 'greeting';
        }


        /*
        |--------------------------------------------------------------------------
        | CONVERSACION GENERAL
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

        if (
            $esLista &&
            ($esIngreso || $esEgreso)
        ) {

            return 'show';
        }


        /*
        |--------------------------------------------------------------------------
        | SUMA
        |--------------------------------------------------------------------------
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
        | CANTIDAD
        |--------------------------------------------------------------------------
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


    /*
    |--------------------------------------------------------------------------
    | DETECTAR FECHA
    |--------------------------------------------------------------------------
    */

    private function detectarFecha(string $texto): array
    {
        $meses = [

            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'setiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12,

        ];


        $mes = null;

        $mesDesde = null;

        $mesHasta = null;


        /*
        |--------------------------------------------------------------------------
        | SEMESTRES
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'primer semestre') ||
            str_contains($texto, '1er semestre') ||
            str_contains($texto, '1 semestre')
        ) {

            $mesDesde = 1;

            $mesHasta = 6;
        }


        elseif (
            str_contains($texto, 'segundo semestre') ||
            str_contains($texto, '2do semestre') ||
            str_contains($texto, '2 semestre')
        ) {

            $mesDesde = 7;

            $mesHasta = 12;
        }


        /*
        |--------------------------------------------------------------------------
        | TRIMESTRES
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains($texto, 'primer trimestre') ||
            str_contains($texto, '1er trimestre') ||
            str_contains($texto, '1 trimestre')
        ) {

            $mesDesde = 1;

            $mesHasta = 3;
        }


        elseif (
            str_contains($texto, 'segundo trimestre') ||
            str_contains($texto, '2do trimestre') ||
            str_contains($texto, '2 trimestre')
        ) {

            $mesDesde = 4;

            $mesHasta = 6;
        }


        elseif (
            str_contains($texto, 'tercer trimestre') ||
            str_contains($texto, '3er trimestre') ||
            str_contains($texto, '3 trimestre')
        ) {

            $mesDesde = 7;

            $mesHasta = 9;
        }


        elseif (
            str_contains($texto, 'cuarto trimestre') ||
            str_contains($texto, '4to trimestre') ||
            str_contains($texto, '4 trimestre')
        ) {

            $mesDesde = 10;

            $mesHasta = 12;
        }


        /*
        |--------------------------------------------------------------------------
        | RANGO NATURAL
        |--------------------------------------------------------------------------
        */

        else {

            $mesEncontrados = [];


            foreach (
                $meses as $nombre => $numero
            ) {

                $posicion =
                    mb_strpos(
                        $texto,
                        $nombre
                    );


                if (
                    $posicion !== false
                ) {

                    $mesEncontrados[] = [

                        'mes' =>
                            $numero,

                        'posicion' =>
                            $posicion,

                    ];
                }
            }


            usort(
                $mesEncontrados,
                function ($a, $b) {

                    return
                        $a['posicion']
                        <=>
                        $b['posicion'];
                }
            );


            if (
                count($mesEncontrados) >= 2
            ) {

                $mesPrimero =
                    $mesEncontrados[0]['mes'];


                $mesSegundo =
                    $mesEncontrados[1]['mes'];


                $esRango =
                    str_contains($texto, ' a ') ||
                    str_contains($texto, ' hasta ') ||
                    str_contains($texto, ' entre ') ||
                    str_contains($texto, ' desde ') ||
                    str_contains($texto, ' al ');


                if ($esRango) {

                    $mesDesde =
                        min(
                            $mesPrimero,
                            $mesSegundo
                        );


                    $mesHasta =
                        max(
                            $mesPrimero,
                            $mesSegundo
                        );
                }
            }


            if (
                $mesDesde === null &&
                !empty($mesEncontrados)
            ) {

                $mes =
                    $mesEncontrados[0]['mes'];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | AÑO
        |--------------------------------------------------------------------------
        */

        $anio = null;

        $anioExplicito = false;

        if (
            preg_match(
                '/\b(20\d{2})\b/',
                $texto,
                $coincidencia
            )
        ) {

            $anio =
                (int) $coincidencia[1];

            $anioExplicito = true;
        }


        /*
        |--------------------------------------------------------------------------
        | ESTE MES
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'este mes')
        ) {

            $fechaActual =
                now();

            $mes =
                $fechaActual->month;

            $anio =
                $fechaActual->year;
        }


        /*
        |--------------------------------------------------------------------------
        | MES PASADO
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains($texto, 'mes pasado') ||
            str_contains($texto, 'el mes pasado')
        ) {

            $fechaAnterior =
                now()->subMonth();

            $mes =
                $fechaAnterior->month;

            $anio =
                $fechaAnterior->year;
        }


        /*
        |--------------------------------------------------------------------------
        | ESTE AÑO
        |--------------------------------------------------------------------------
        */

        elseif (
            str_contains($texto, 'este año') ||
            str_contains($texto, 'este ano')
        ) {

            $anio =
                now()->year;
        }


        /*
        |--------------------------------------------------------------------------
        | AÑO ACTUAL POR DEFECTO
        |--------------------------------------------------------------------------
        */

        elseif (
            !$anioExplicito &&
            (
                $mes !== null ||
                $mesDesde !== null ||
                $mesHasta !== null
            )
        ) {

            $anio =
                now()->year;
        }


        return [

            'mes' =>
                $mes,

            'mes_desde' =>
                $mesDesde,

            'mes_hasta' =>
                $mesHasta,

            'anio' =>
                $anio,

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR CATEGORIA
    |--------------------------------------------------------------------------
    */

    private function detectarCategoria(
        string $texto
    ): ?array {

        $categorias =
            Categoria::query()
                ->where(
                    'activo',
                    true
                )
                ->orderBy('orden')
                ->get([
                    'id',
                    'nombre',
                    'tipo',
                ]);


        foreach (
            $categorias as $categoria
        ) {

            $nombreCategoria =
                mb_strtolower(
                    trim(
                        $categoria->nombre
                    )
                );


            if (
                $nombreCategoria === ''
            ) {

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Coincidencia exacta
            |--------------------------------------------------------------------------
            */

            if (
                str_contains(
                    $texto,
                    $nombreCategoria
                )
            ) {

                return [

                    'id' =>
                        $categoria->id,

                    'nombre' =>
                        $categoria->nombre,

                    'tipo' =>
                        $categoria->tipo,

                ];
            }
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR CONCEPTO
    |--------------------------------------------------------------------------
    */

    private function detectarConcepto(
        string $texto,
        string $operacion = 'show'
    ): ?string {

        $conceptos =
            Movimiento::query()
                ->whereNotNull('concepto')
                ->where(
                    'concepto',
                    '!=',
                    ''
                )
                ->select('concepto')
                ->distinct()
                ->pluck('concepto');


        /*
        |--------------------------------------------------------------------------
        | Normalizar texto
        |--------------------------------------------------------------------------
        */

        $normalizar = function (string $valor): string {

            $valor =
                mb_strtolower(
                    trim($valor)
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
        | CONSULTAS GENERALES
        |--------------------------------------------------------------------------
        */

        $operacionesGenerales = [
            'sum',
            'avg',
            'max',
            'min',
            'max_mes',
            'min_mes',
            'max_mes_ingreso',
            'min_mes_ingreso',
        ];


        if (
            in_array(
                $operacion,
                $operacionesGenerales,
                true
            )
        ) {

            return null;
        }


        /*
        |--------------------------------------------------------------------------
        | Palabras que no sirven para identificar un concepto
        |--------------------------------------------------------------------------
        */

        $palabrasIgnoradas = [

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
            'muestra',
            'mostrar',
            'muestreme',
            'quiero',
            'dime',
            'dame',
            'cual',
            'cuál',
            'que',
            'qué',

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
            'al',

            'del',
            'este',
            'esta',
            'ese',
            'esa',

        ];


        /*
        |--------------------------------------------------------------------------
        | Palabras relevantes de la consulta
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
            | 1. Coincidencia directa
            |--------------------------------------------------------------------------
            */

            if (
                str_contains(
                    $textoNormalizado,
                    $conceptoNormalizado
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
                | 2. Coincidencia por palabras
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

                        if (
                            $palabraConcepto ===
                            $palabraConsulta
                        ) {

                            $coincidencias += 1;

                            break;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Coincidencia parcial para palabras largas
                        |--------------------------------------------------------------------------
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
                | Puntaje
                |--------------------------------------------------------------------------
                */

                $cobertura =
                    $coincidencias /
                    count($palabrasConcepto);


                $puntaje =
                    ($coincidencias * 20) +
                    ($cobertura * 30);


                /*
                |--------------------------------------------------------------------------
                | Si solo coincide una palabra muy genérica,
                | no considerarla suficiente.
                |--------------------------------------------------------------------------
                */

                if (
                    $coincidencias === 1 &&
                    mb_strlen(
                        $palabrasConcepto[0]
                    ) < 6
                ) {

                    continue;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Guardar la mejor coincidencia
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