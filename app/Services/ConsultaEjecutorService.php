<?php

namespace App\Services;

use App\Services\SigiAiService;
use App\Services\Sigi\SigiChistesService;
use App\Services\Sigi\SigiClimaService;
use App\Services\Sigi\SigiEstatutosService;
use App\Services\Sigi\SigiFinanzasService;
use App\Services\Sigi\SigiPeriodosService;
use App\Services\Sigi\SigiRolesService;
use App\Services\Sigi\SigiSecurityService;
use App\Services\Sigi\SigiUsuariosService;

class ConsultaEjecutorService
{
    private SigiAiService $sigiAi;

    private SigiEstatutosService $sigiEstatutos;

    private SigiFinanzasService $sigiFinanzas;

    private SigiClimaService $sigiClima;

    private SigiChistesService $sigiChistes;

    private SigiUsuariosService $sigiUsuarios;

    private SigiRolesService $sigiRoles;

    private SigiPeriodosService $sigiPeriodos;

    private SigiSecurityService $sigiSecurity;

    public function __construct(
        SigiAiService $sigiAi,
        SigiEstatutosService $sigiEstatutos,
        SigiFinanzasService $sigiFinanzas,
        SigiClimaService $sigiClima,
        SigiChistesService $sigiChistes,
        SigiUsuariosService $sigiUsuarios,
        SigiRolesService $sigiRoles,
        SigiPeriodosService $sigiPeriodos,
        SigiSecurityService $sigiSecurity
    ) {

        $this->sigiAi =
            $sigiAi;

        $this->sigiEstatutos =
            $sigiEstatutos;

        $this->sigiFinanzas =
            $sigiFinanzas;

        $this->sigiClima =
            $sigiClima;

        $this->sigiChistes =
            $sigiChistes;

        $this->sigiUsuarios =
            $sigiUsuarios;

        $this->sigiRoles =
            $sigiRoles;

        $this->sigiPeriodos =
            $sigiPeriodos;

        $this->sigiSecurity =
            $sigiSecurity;
    }

    public function ejecutar(
        array $interpretacion,
        ?int $usuarioId = null
    ): array {

        $tabla =
            $interpretacion['tabla']
            ?? null;

        $operacion =
            $interpretacion['operacion']
            ?? 'show';


        /*
        |--------------------------------------------------------------------------
        | SALUDO
        |--------------------------------------------------------------------------
        |
        | Los saludos no pertenecen a ninguna tabla.
        |
        */

        if ($operacion === 'greeting') {

            return $this->responderSaludo(
                $interpretacion['consulta_original']
                ?? ''
            );

        }


        /*
        |--------------------------------------------------------------------------
        | ESTATUTOS DEL GRUPO 21
        |--------------------------------------------------------------------------
        */

        $consultaOriginal =
            $interpretacion['consulta_original']
            ?? $interpretacion['texto']
            ?? '';

        if (
            $this->sigiEstatutos
                ->esConsultaEstatutos(
                    $consultaOriginal
                )
        ) {

            return $this->sigiEstatutos->consultar(
                (string) $consultaOriginal
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CONVERSACIÓN GENERAL
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'conversation') {

            return $this->conversarConSigi(
                $interpretacion['consulta_original']
                ?? '',
                $usuarioId
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CHISTE
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'joke') {

            return $this->sigiChistes->obtener();

        }


        /*
        |--------------------------------------------------------------------------
        | CLIMA
        |--------------------------------------------------------------------------
        */

        if ($operacion === 'weather') {

            return $this->sigiClima->obtener(
                $interpretacion
            );

        }


        /*
        |--------------------------------------------------------------------------
        | INFORMACIÓN ADMINISTRATIVA
        |--------------------------------------------------------------------------
        |
        | Usuarios y roles contienen información administrativa.
        |
        | Solo pueden consultarlos:
        |
        | - Administrador
        | - Secretario
        | - Tesorero
        |
        | Nunca confiamos en el texto de la pregunta para determinar
        | los permisos. Utilizamos el usuario autenticado identificado
        | por $usuarioId.
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
            )
        ) {

            if (!$usuarioId) {

                return [
                    'success' => false,

                    'tipo' => 'seguridad',

                    'resultado' => null,

                    'mensaje' =>
                        $this->sigiSecurity
                            ->respuestaSinPermisoUsuarios(),
                ];
            }


            $usuario =
                \App\Models\User::find(
                    $usuarioId
                );


            if (
                !$usuario ||
                !$this->sigiSecurity
                    ->puedeConsultarUsuarios(
                        $usuario
                    )
            ) {

                return [
                    'success' => false,

                    'tipo' => 'seguridad',

                    'resultado' => null,

                    'mensaje' =>
                        $this->sigiSecurity
                            ->respuestaSinPermisoUsuarios(),
                ];
            }
        }


        /*
        |--------------------------------------------------------------------------
        | EJECUCIÓN DE CONSULTAS
        |--------------------------------------------------------------------------
        */

        return match ($tabla) {

            'movimientos' =>
                $this->sigiFinanzas
                    ->consultarMovimientos(
                        $interpretacion,
                        $operacion
                    ),

            'periodos' =>
                $this->sigiPeriodos
                    ->consultarPeriodos(
                        $interpretacion,
                        $operacion
                    ),

            'usuarios' =>
                $this->sigiUsuarios
                    ->consultarUsuarios(
                        $interpretacion,
                        $operacion
                    ),

            'roles' =>
                $this->sigiRoles
                    ->consultarRoles(
                        $interpretacion,
                        $operacion
                    ),

            default => [

                'success' => false,

                'mensaje' =>
                    'No pude determinar qué información deseas consultar.',
            ],
        };
    }


    /*
    |--------------------------------------------------------------------------
    | CONVERSACIÓN GENERAL CON SIGI
    |--------------------------------------------------------------------------
    */

    private function conversarConSigi(
        string $consulta,
        ?int $usuarioId = null
    ): array {

        $respuesta =
            $this->sigiAi->responder(
                $consulta,

                'Eres Sigi, el asistente virtual de SIGEFIV. '
                . 'Responde siempre en español, de forma natural, amable y cercana. '
                . 'Puedes conversar sobre temas generales y ayudar al usuario. '
                . 'No inventes datos financieros de SIGEFIV. '
                . 'Las consultas sobre ingresos, egresos, movimientos, periodos, '
                . 'usuarios, roles y demás información del sistema son atendidas '
                . 'localmente por SIGEFIV. '
                . 'Si el usuario quiere conversar, responde como un asistente '
                . 'amigable y conciso.',

                $usuarioId
            );


        if ($respuesta !== null) {

            return [

                'success' =>
                    true,

                'tipo' =>
                    'texto',

                'resultado' =>
                    null,

                'mensaje' =>
                    '🤖 ' . $respuesta,

            ];
        }


        return [

            'success' =>
                false,

            'tipo' =>
                'texto',

            'resultado' =>
                null,

            'mensaje' =>
                '🤖 No pude conectarme con mi asistente de conversación en este momento. '
                . 'Puedes intentarlo nuevamente en unos segundos.',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SALUDO DE SIGI
    |--------------------------------------------------------------------------
    */

    private function responderSaludo(
        string $consulta
    ): array {

        $respuesta =
            $this->sigiAi->responder(
                $consulta,

                'Eres Sigi, el asistente virtual de SIGEFIV. '
                . 'Responde en español, de forma muy amable, cercana y afectiva. '
                . 'Cuando el usuario salude, devuélvele un saludo cálido. '
                . 'Menciona de forma natural que también puedes ayudar con consultas '
                . 'financieras de SIGEFIV, chistes y clima. '
                . 'No inventes datos financieros.'
            );


        if ($respuesta !== null) {

            return [

                'success' => true,

                'tipo' => 'texto',

                'resultado' => null,

                'mensaje' => '🤖 ' . $respuesta,

            ];
        }


        return [

            'success' => false,

            'tipo' => 'texto',

            'resultado' => null,

            'mensaje' =>
                '🤖 Hola ❤️. Estoy aquí para ayudarte. También puedo '
                . 'contarte un chiste o informarte sobre el clima.',

        ];
    }
}