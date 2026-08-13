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

        $operacion = $this->detectarOperacion($texto);

        return [

            'consulta_original' =>
                $consulta,

            'tabla' =>
                $this->detectarTabla($texto),

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
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'egresos') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastos') ||
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
        | ROLES
        |--------------------------------------------------------------------------
        */

        if (
            str_contains($texto, 'rol') ||
            str_contains($texto, 'roles') ||
            str_contains($texto, 'administrador') ||
            str_contains($texto, 'tesorero')
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
    | DETECTAR OPERACION
    |--------------------------------------------------------------------------
    */

    private function detectarOperacion(string $texto): string
    {
        /*
        |--------------------------------------------------------------------------
        | SALUDOS
        |--------------------------------------------------------------------------
        |
        | Solo se reconoce como saludo cuando la consulta completa corresponde
        | a un saludo. Así no interferimos con consultas como:
        |
        | "Hola Sigi, ¿cuánto gastamos en agua?"
        |
        */

        $textoSaludo = trim(
            preg_replace(
                '/[¿?¡!.,;:]+/u',
                ' ',
                $texto
            )
        );

        $textoSaludo = trim(
            preg_replace(
                '/\\s+/u',
                ' ',
                $textoSaludo
            )
        );

        $saludos = [
            'hola',
            'holaaa',
            'holaaaa',
            'hey',
            'buenas',
            'buen dia',
            'buen día',
            'buenos dias',
            'buenos días',
            'buenas tardes',
            'buenas noches',
            'que tal',
            'qué tal',
            'como estas',
            'cómo estas',
            'cómo estás',
            'hola sigi',
            'hola asistente',
        ];

        if (in_array($textoSaludo, $saludos, true)) {
            return 'greeting';
        }

        /*
        |--------------------------------------------------------------------------
        | MAYORES INGRESOS POR MES
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        | Estas condiciones deben estar ANTES de "mayor" genérico.
        |
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
            str_contains(
                $texto,
                'mayor ingreso'
            ) ||
            str_contains(
                $texto,
                'mayores ingresos'
            ) ||
            str_contains(
                $texto,
                'más ingresos'
            ) ||
            str_contains(
                $texto,
                'mas ingresos'
            ) ||
            str_contains(
                $texto,
                'mes con mayor ingreso'
            ) ||
            str_contains(
                $texto,
                'mes con mayores ingresos'
            ) ||
            str_contains(
                $texto,
                'mes que mayor ingreso'
            ) ||
            str_contains(
                $texto,
                'mes que mayores ingresos'
            )
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
            str_contains(
                $texto,
                'menor ingreso'
            ) ||
            str_contains(
                $texto,
                'menores ingresos'
            ) ||
            str_contains(
                $texto,
                'menos ingresos'
            )
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
            str_contains(
                $texto,
                'mayores egresos'
            ) ||
            str_contains(
                $texto,
                'mayores gastos'
            ) ||
            str_contains(
                $texto,
                'más egresos'
            ) ||
            str_contains(
                $texto,
                'mas egresos'
            ) ||
            str_contains(
                $texto,
                'más gastos'
            ) ||
            str_contains(
                $texto,
                'mas gastos'
            )
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
            str_contains(
                $texto,
                'menores egresos'
            ) ||
            str_contains(
                $texto,
                'menores gastos'
            ) ||
            str_contains(
                $texto,
                'menos egresos'
            ) ||
            str_contains(
                $texto,
                'menos gastos'
            )
        ) {

            return 'min_mes';
        }


        /*
        |--------------------------------------------------------------------------
        | MESES EN LOS QUE APARECE UN CONCEPTO
        |--------------------------------------------------------------------------
        |
        | Ejemplos:
        | "¿En qué meses el profesor de taekwondo hizo sus pagos?"
        | "¿En qué meses hubo pagos del vaso de leche?"
        |
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
        | CONVERSACIÓN GENERAL
        |--------------------------------------------------------------------------
        |
        | Si no encontramos una tabla de SIGEFIV ni una operación financiera
        | conocida, la consulta pasa a OpenRouter.
        |
        | Las consultas financieras siguen siendo locales.
        |--------------------------------------------------------------------------
        */

        if (
            $this->detectarTabla($texto) === null
        ) {

            return 'conversation';

        }


        return 'show';
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

                /*
                |--------------------------------------------------------------------------
                | Buscar el nombre del mes como palabra completa
                |--------------------------------------------------------------------------
                |
                | Importante:
                | "mayo" aparece dentro de "mayor".
                | Si usamos mb_strpos() directamente, una consulta como
                | "mayor ingreso de enero" detecta también "mayo"
                | dentro de "mayor" y puede terminar seleccionando mayo.
                |
                | Por eso exigimos que el mes esté separado por límites
                | de palabra/letras.
                |
                */

                $patronMes =
                    '/(?<![\p{L}])' .
                    preg_quote($nombre, '/') .
                    '(?![\p{L}])/iu';

                if (
                    preg_match(
                        $patronMes,
                        $texto,
                        $coincidencia,
                        PREG_OFFSET_CAPTURE
                    )
                ) {

                    $mesEncontrados[] = [

                        'mes' =>
                            $numero,

                        'posicion' =>
                            $coincidencia[0][1],

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


        if (
            preg_match(
                '/\b(20\d{2})\b/',
                $texto,
                $coincidencia
            )
        ) {

            $anio =
                (int) $coincidencia[1];

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
    |
    | Los conceptos se obtienen directamente de SQLite.
    |
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
        |
        | Permitimos comparar:
        |
        | "basquet" = "básquet"
        | "colegio" = "colegio"
        | "reflectores" = "reflectores"
        |
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
        |
        | Para sumas, promedios y consultas de mayor/menor monto,
        | no debemos detectar un concepto accidentalmente.
        |
        | Ejemplo:
        | "suma de ingresos de enero hasta febrero de 2025"
        |
        | Esta consulta debe sumar todos los ingresos del período y
        | NO seleccionar un concepto que contenga "enero", "febrero"
        | o "pago".
        |
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
                |
                | Esto permite consultas como:
                |
                | "pago por alquiler de reflectores"
                | "pago del profesor de basquet"
                | "pagos del colegio"
                |
                | aunque el texto almacenado tenga más palabras.
                |
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
