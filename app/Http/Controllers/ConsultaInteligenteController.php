<?php

namespace App\Http\Controllers;

use App\Services\ConsultaEjecutorService;
use App\Services\ConsultaInteligenteService;
use App\Services\SigiMemoriaService;
use Illuminate\Http\Request;

class ConsultaInteligenteController extends Controller
{
    public function consultar(
        Request $request,
        ConsultaInteligenteService $interprete,
        ConsultaEjecutorService $ejecutor,
        SigiMemoriaService $memoria
    ) {
        $request->validate([
            'consulta' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $consulta = trim(
            $request->input('consulta')
        );

        /*
        |--------------------------------------------------------------------------
        | USUARIO AUTENTICADO
        |--------------------------------------------------------------------------
        */

        $usuarioId =
            $request->user()?->id;


        /*
        |--------------------------------------------------------------------------
        | 1. DETECTAR Y GUARDAR MEMORIA EXPLÍCITA
        |--------------------------------------------------------------------------
        */

        $memoriaGuardada =
            $memoria->detectarYRecordar(
                $usuarioId,
                $consulta
            );

        if ($memoriaGuardada !== null) {

            return response()->json([

                'success' => true,

                'consulta' =>
                    $consulta,

                'interpretacion' => [
                    'operacion' => 'memory',
                ],

                'resultado' => null,

                'tipo' => 'memory',

                'mensaje' =>
                    'Perfecto. Lo recordaré para futuras conversaciones.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | 2. OBTENER CONTEXTO DE LA CONVERSACIÓN
        |--------------------------------------------------------------------------
        |
        | El contexto se guarda en la sesión del usuario.
        |
        */

        $contextoAnterior =
            $request->session()->get(
                'sigi_contexto_consulta',
                []
            );


        /*
        |--------------------------------------------------------------------------
        | 3. INTERPRETAR LA CONSULTA ACTUAL
        |--------------------------------------------------------------------------
        */

        $interpretacion =
            $interprete->interpretar(
                $consulta
            );


        /*
        |--------------------------------------------------------------------------
        | 4. COMPLETAR LA INTERPRETACIÓN CON EL CONTEXTO
        |--------------------------------------------------------------------------
        |
        | La consulta actual siempre tiene prioridad. El contexto anterior
        | solamente completa datos que la nueva consulta no especifica.
        |
        */

        $interpretacion =
            $this->aplicarContexto(
                $interpretacion,
                $contextoAnterior,
                $consulta
            );


        \Log::info(
            'SIGI CONTEXTO',
            [
                'consulta' =>
                    $consulta,

                'contexto_anterior' =>
                    $contextoAnterior,

                'interpretacion_final' =>
                    $interpretacion,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | 5. EJECUTAR LA CONSULTA
        |--------------------------------------------------------------------------
        |
        | IMPORTANTE:
        |
        | Pasamos el ID del usuario autenticado al ejecutor.
        |
        | Esto permite que SigiSecurityService pueda comprobar
        | los permisos reales del usuario antes de ejecutar consultas
        | administrativas sobre usuarios y roles.
        |
        */

        $resultado =
            $ejecutor->ejecutar(
                $interpretacion,
                $usuarioId
            );


        /*
        |--------------------------------------------------------------------------
        | 6. GUARDAR NUEVO CONTEXTO
        |--------------------------------------------------------------------------
        |
        | Solo guardamos contexto cuando la consulta realmente pertenece
        | al sistema SIGEFIV.
        |
        */

        if (
            ($resultado['success'] ?? false) === true &&
            ($interpretacion['tabla'] ?? null) !== null &&
            !in_array(
                $interpretacion['operacion'] ?? null,
                [
                    'greeting',
                    'conversation',
                    'weather',
                    'joke',
                    'memory',
                ],
                true
            )
        ) {

            /*
             * Si la consulta fue "¿Cuál es el período actual?",
             * guardamos también el año y mes reales del período
             * encontrado. Así una consulta posterior como:
             *
             * "¿Y cuál es el ingreso?"
             *
             * puede heredar correctamente ese período.
             */

            $fechaContexto =
                $interpretacion['fecha']
                ?? [];

            if (
                ($interpretacion['tabla'] ?? null) === 'periodos' &&
                str_contains(
                    mb_strtolower(
                        $consulta,
                        'UTF-8'
                    ),
                    'periodo actual'
                )
            ) {

                $periodoContexto = null;

                $resultadoContexto =
                    $resultado['resultado']
                    ?? null;

                if (
                    $resultadoContexto instanceof
                    \Illuminate\Support\Collection
                ) {

                    $periodoContexto =
                        $resultadoContexto->first();
                }

                if ($periodoContexto) {

                    $fechaContexto = [

                        'anio' =>
                            (int) $periodoContexto->anio,

                        'mes' =>
                            (int) $periodoContexto->mes,
                    ];
                }
            }


            $contextoNuevo = [

                'tabla' =>
                    $interpretacion['tabla']
                    ?? null,

                'operacion' =>
                    $interpretacion['operacion']
                    ?? null,

                'fecha' =>
                    $fechaContexto,

                'categoria' =>
                    $interpretacion['categoria']
                    ?? null,

                'concepto' =>
                    $interpretacion['concepto']
                    ?? null,

                /*
                 * Guardamos también el resultado de la consulta.
                 */

                'resultado' =>
                    $resultado['resultado']
                    ?? null,

                'tipo' =>
                    $resultado['tipo']
                    ?? null,

                'consulta_original' =>
                    $consulta,
            ];


            $request->session()->put(
                'sigi_contexto_consulta',
                $contextoNuevo
            );
        }


        /*
        |--------------------------------------------------------------------------
        | 7. DEVOLVER RESULTADO
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'success' =>
                $resultado['success']
                ?? false,

            'consulta' =>
                $consulta,

            'interpretacion' =>
                $interpretacion,

            'resultado' =>
                $resultado['resultado']
                ?? null,

            'tipo' =>
                $resultado['tipo']
                ?? null,

            'mensaje' =>
                $resultado['mensaje']
                ?? 'Consulta procesada.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | APLICAR CONTEXTO CONVERSACIONAL
    |--------------------------------------------------------------------------
    */

    private function aplicarContexto(
        array $interpretacion,
        array $contexto,
        string $consulta
    ): array {

        if (empty($contexto)) {

            return $interpretacion;
        }


        $texto =
            mb_strtolower(
                trim($consulta),
                'UTF-8'
            );


        /*
        |--------------------------------------------------------------------------
        | CONTEXTO DE PERÍODOS
        |--------------------------------------------------------------------------
        |
        | Si la conversación anterior fue sobre un período y el usuario
        | pregunta después "¿y cuál es el ingreso?" o "¿y cuál es el
        | egreso?", conservamos la tabla "periodos". De esta forma SIGI
        | devuelve el total del período y no un listado de movimientos.
        |
        */

        $textoIngreso =
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresos') ||
            str_contains($texto, 'recaudado') ||
            str_contains($texto, 'recaudación') ||
            str_contains($texto, 'recaudacion');


        $textoEgreso =
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'egresos') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastos');


        if (
            ($contexto['tabla'] ?? null) === 'periodos' &&
            ($interpretacion['tabla'] ?? null) === 'movimientos' &&
            ($textoIngreso || $textoEgreso)
        ) {

            $interpretacion['tabla'] =
                'periodos';

            if ($textoIngreso) {

                $interpretacion['tipo_movimiento'] =
                    'Ingreso';

                $interpretacion['tipo'] =
                    'Ingreso';

            } else {

                $interpretacion['tipo_movimiento'] =
                    'Egreso';

                $interpretacion['tipo'] =
                    'Egreso';
            }
        }


        if (
            ($interpretacion['tabla'] ?? null)
            !== 'movimientos' &&
            ($interpretacion['tabla'] ?? null)
            !== 'periodos'
        ) {

            return $interpretacion;
        }


        /*
        |--------------------------------------------------------------------------
        | FECHA
        |--------------------------------------------------------------------------
        |
        | La fecha de la consulta actual siempre tiene prioridad.
        | El contexto anterior solo se hereda cuando la nueva consulta
        | realmente no contiene información temporal.
        |
        */

        $fechaNueva =
            $interpretacion['fecha']
            ?? [];

        $fechaAnterior =
            $contexto['fecha']
            ?? [];


        $tieneFechaNueva =
            ($fechaNueva['anio'] ?? null) !== null ||
            ($fechaNueva['mes'] ?? null) !== null ||
            !empty($fechaNueva['meses'] ?? []) ||
            ($fechaNueva['mes_desde'] ?? null) !== null ||
            ($fechaNueva['mes_hasta'] ?? null) !== null;


        if (
            !$tieneFechaNueva &&
            !empty($fechaAnterior)
        ) {

            $interpretacion['fecha'] =
                $fechaAnterior;
        }


        /*
        |--------------------------------------------------------------------------
        | PROTECCIÓN DEL AÑO ACTUAL
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $interpretacion['fecha']['mes']
                ?? null
            ) &&
            empty(
                $interpretacion['fecha']['anio']
                ?? null
            )
        ) {

            $interpretacion['fecha']['anio'] =
                now()->year;
        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORÍA Y CONCEPTO
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $interpretacion['categoria']
                ?? null
            ) &&
            !empty(
                $contexto['categoria']
                ?? null
            )
        ) {

            $interpretacion['categoria'] =
                $contexto['categoria'];
        }


        if (
            empty(
                $interpretacion['concepto']
                ?? null
            ) &&
            !empty(
                $contexto['concepto']
                ?? null
            )
        ) {

            $interpretacion['concepto'] =
                $contexto['concepto'];
        }


        /*
        |--------------------------------------------------------------------------
        | TIPO DE MOVIMIENTO
        |--------------------------------------------------------------------------
        */

        if (
            ($interpretacion['tabla'] ?? null)
            !== 'movimientos'
        ) {

            return $interpretacion;
        }


        if (
            str_contains($texto, 'egreso') ||
            str_contains($texto, 'egresos') ||
            str_contains($texto, 'gasto') ||
            str_contains($texto, 'gastos') ||
            str_contains($texto, 'gastamos') ||
            str_contains($texto, 'pagamos') ||
            str_contains($texto, 'pago') ||
            str_contains($texto, 'pagos')
        ) {

            $interpretacion['tipo_movimiento'] =
                'Egreso';

            $interpretacion['tipo'] =
                'Egreso';

        } elseif (
            str_contains($texto, 'ingreso') ||
            str_contains($texto, 'ingresos') ||
            str_contains($texto, 'ingresamos') ||
            str_contains($texto, 'recaud') ||
            str_contains($texto, 'cobramos') ||
            str_contains($texto, 'cobro')
        ) {

            $interpretacion['tipo_movimiento'] =
                'Ingreso';

            $interpretacion['tipo'] =
                'Ingreso';

        } elseif (
            isset($contexto['tipo'])
        ) {

            $interpretacion['tipo'] =
                $contexto['tipo'];

            $interpretacion['tipo_movimiento'] =
                $contexto['tipo'];
        }


        return $interpretacion;
    }
}