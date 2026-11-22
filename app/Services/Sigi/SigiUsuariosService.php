<?php

namespace App\Services\Sigi;

use App\Models\User;

class SigiUsuariosService
{
    public function consultarUsuarios(
        array $interpretacion,
        string $operacion
    ): array {

        $consulta =
            User::query()
                ->with('roles');


        $texto =
            mb_strtolower(
                $interpretacion['texto']
                ?? '',
                'UTF-8'
            );


        /*
        |--------------------------------------------------------------------------
        | DETECTAR ROL SOLICITADO
        |--------------------------------------------------------------------------
        */

        $rolSolicitado =
            $this->detectarRolSolicitado(
                $texto
            );


        /*
        |--------------------------------------------------------------------------
        | FILTRO POR ROL
        |--------------------------------------------------------------------------
        */

        if ($rolSolicitado !== null) {

            $consulta->role(
                $rolSolicitado
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTA DIRECTA DEL RESPONSABLE
        |--------------------------------------------------------------------------
        */

        if (
            $rolSolicitado !== null &&
            $this->esPreguntaDirectaPorRol(
                $texto
            )
        ) {

            return $this->responderResponsable(
                $consulta,
                $rolSolicitado
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CONTAR USUARIOS
        |--------------------------------------------------------------------------
        */

        if (
            $operacion === 'count'
        ) {

            $resultado =
                $consulta->count();


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    $this->mensajeConteo(
                        $resultado,
                        $rolSolicitado
                    ),

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | OBTENER USUARIOS
        |--------------------------------------------------------------------------
        |
        | Solo seleccionamos información segura para SIGI.
        |
        | Nunca seleccionamos:
        |
        | - password
        | - remember_token
        | - google_id
        | - ultima_ip
        | - dni
        | - direccion
        | - datos internos de autenticación
        |
        */

        $usuarios =
            $consulta
                ->select(
                    'id',
                    'name',
                    'email',
                    'estado',
                    'recibir_notificaciones'
                )
                ->orderBy(
                    'name'
                )
                ->limit(50)
                ->get();


        /*
        |--------------------------------------------------------------------------
        | TRANSFORMAR RESULTADO
        |--------------------------------------------------------------------------
        */

        $resultado =
            $usuarios->map(
                function (User $usuario) {

                    return [

                        'id' =>
                            $usuario->id,

                        'nombre' =>
                            $usuario->name,

                        'email' =>
                            $usuario->email,

                        'estado' =>
                            $usuario->estado,

                        'recibir_notificaciones' =>
                            (bool) $usuario->recibir_notificaciones,

                        'roles' =>
                            $usuario
                                ->roles
                                ->pluck('name')
                                ->values()
                                ->all(),

                    ];
                }
            )
            ->values();


        /*
        |--------------------------------------------------------------------------
        | RESPUESTA
        |--------------------------------------------------------------------------
        */

        return [

            'success' => true,

            'tipo' => 'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                $this->mensajeLista(
                    $resultado->count(),
                    $rolSolicitado
                ),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | DETECTAR ROL SOLICITADO
    |--------------------------------------------------------------------------
    */

    private function detectarRolSolicitado(
        string $texto
    ): ?string {

        /*
        |--------------------------------------------------------------------------
        | ADMINISTRADOR
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'administrador'
            ) ||
            str_contains(
                $texto,
                'administradores'
            )
        ) {

            return 'Administrador';
        }


        /*
        |--------------------------------------------------------------------------
        | SECRETARIO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'secretario'
            ) ||
            str_contains(
                $texto,
                'secretarios'
            )
        ) {

            return 'Secretario';
        }


        /*
        |--------------------------------------------------------------------------
        | TESORERO
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $texto,
                'tesorero'
            ) ||
            str_contains(
                $texto,
                'tesoreros'
            )
        ) {

            return 'Tesorero';
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTA
        |--------------------------------------------------------------------------
        |
        | Importante:
        |
        | Solo se considera rol "Consulta" cuando la palabra aparece
        | dentro de un contexto de roles/usuarios.
        |
        | Esto evita confundir una consulta normal de SIGI con el
        | nombre del rol.
        |
        */

        if (
            str_contains(
                $texto,
                'rol consulta'
            ) ||
            str_contains(
                $texto,
                'rol de consulta'
            ) ||
            str_contains(
                $texto,
                'rol "consulta"'
            ) ||
            str_contains(
                $texto,
                "rol 'consulta'"
            ) ||
            (
                str_contains(
                    $texto,
                    'usuarios'
                ) &&
                str_contains(
                    $texto,
                    'consulta'
                )
            )
        ) {

            return 'Consulta';
        }


        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | DETERMINAR SI ES PREGUNTA DIRECTA POR UN ROL
    |--------------------------------------------------------------------------
    */

    private function esPreguntaDirectaPorRol(
        string $texto
    ): bool {

        $expresiones = [

            'quien es el',
            'quién es el',

            'quien es la',
            'quién es la',

            'quienes son los',
            'quiénes son los',

            'quienes son las',
            'quiénes son las',

            'quien administra',
            'quién administra',

            'quien tiene el rol',
            'quién tiene el rol',

            'dime quien es',
            'dime quién es',

            'dime quienes son',
            'dime quiénes son',

        ];


        foreach (
            $expresiones as $expresion
        ) {

            if (
                str_contains(
                    $texto,
                    $expresion
                )
            ) {

                return true;
            }
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | RESPONDER RESPONSABLE
    |--------------------------------------------------------------------------
    */

    private function responderResponsable(
        $consulta,
        string $rol
    ): array {

        $usuarios =
            $consulta
                ->select(
                    'id',
                    'name',
                    'email',
                    'estado'
                )
                ->orderBy(
                    'name'
                )
                ->limit(20)
                ->get();


        /*
        |--------------------------------------------------------------------------
        | NINGÚN USUARIO
        |--------------------------------------------------------------------------
        */

        if (
            $usuarios->isEmpty()
        ) {

            return [

                'success' => true,

                'tipo' => 'texto',

                'resultado' => null,

                'mensaje' =>
                    'Actualmente no encontré ningún usuario con el rol de ' .
                    $rol .
                    '.',

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | UN SOLO RESPONSABLE
        |--------------------------------------------------------------------------
        */

        if (
            $usuarios->count() === 1
        ) {

            $usuario =
                $usuarios->first();


            $icono =
                $this->iconoRol(
                    $rol
                );


            return [

                'success' => true,

                'tipo' => 'texto',

                'resultado' => [

                    'id' =>
                        $usuario->id,

                    'nombre' =>
                        $usuario->name,

                    'email' =>
                        $usuario->email,

                    'estado' =>
                        $usuario->estado,

                    'rol' =>
                        $rol,

                ],

                'mensaje' =>
                    $icono .
                    ' ' .
                    $this->articuloRol(
                        $rol
                    ) .
                    ' es ' .
                    $usuario->name .
                    '. Actualmente tiene el rol de ' .
                    $rol .
                    '.',

            ];
        }


        /*
        |--------------------------------------------------------------------------
        | VARIOS RESPONSABLES
        |--------------------------------------------------------------------------
        */

        $nombres =
            $usuarios
                ->pluck('name')
                ->values()
                ->all();


        $icono =
            $this->iconoRol(
                $rol
            );


        return [

            'success' => true,

            'tipo' => 'texto',

            'resultado' => [

                'usuarios' =>
                    $nombres,

                'rol' =>
                    $rol,

            ],

            'mensaje' =>
                $icono .
                ' Actualmente hay ' .
                count($nombres) .
                ' usuarios con el rol de ' .
                $rol .
                ': ' .
                implode(
                    ', ',
                    $nombres
                ) .
                '.',

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | MENSAJE DE CONTEO
    |--------------------------------------------------------------------------
    */

    private function mensajeConteo(
        int $cantidad,
        ?string $rol
    ): string {

        if ($rol !== null) {

            return
                '👥 Hay ' .
                $cantidad .
                ' usuarios con el rol de ' .
                $rol .
                '.';
        }


        return
            'Encontré ' .
            $cantidad .
            ' usuarios que coinciden con la consulta.';
    }


    /*
    |--------------------------------------------------------------------------
    | MENSAJE DE LISTA
    |--------------------------------------------------------------------------
    */

    private function mensajeLista(
        int $cantidad,
        ?string $rol
    ): string {

        if ($rol !== null) {

            return
                'Encontré ' .
                $cantidad .
                ' usuarios con el rol de ' .
                $rol .
                '.';
        }


        return
            'Encontré ' .
            $cantidad .
            ' usuarios.';
    }


    /*
    |--------------------------------------------------------------------------
    | ARTÍCULO DEL ROL
    |--------------------------------------------------------------------------
    */

    private function articuloRol(
        string $rol
    ): string {

        return match ($rol) {

            'Administrador' =>
                'El administrador',

            'Secretario' =>
                'El secretario',

            'Tesorero' =>
                'El tesorero',

            'Consulta' =>
                'El usuario',

            default =>
                'El usuario',

        };
    }


    /*
    |--------------------------------------------------------------------------
    | ICONO DEL ROL
    |--------------------------------------------------------------------------
    */

    private function iconoRol(
        string $rol
    ): string {

        return match ($rol) {

            'Administrador' =>
                '👑',

            'Secretario' =>
                '📋',

            'Tesorero' =>
                '💰',

            'Consulta' =>
                '👤',

            default =>
                '👤',

        };
    }
}