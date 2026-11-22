<?php

namespace App\Services\Sigi;

use App\Models\User;

class SigiSecurityService
{
    /**
     * Roles que pueden consultar información
     * administrativa de otros usuarios.
     */
    private const ROLES_ADMINISTRATIVOS = [
        'Administrador',
        'Secretario',
        'Tesorero',
    ];

    /**
     * Determinar si un usuario puede consultar
     * información administrativa de otros usuarios.
     */
    public static function puedeConsultarUsuarios(
        User $usuario
    ): bool {
        return $usuario->hasAnyRole(
            self::ROLES_ADMINISTRATIVOS
        );
    }

    /**
     * Determinar si una solicitud intenta obtener
     * información confidencial o peligrosa.
     */
    public static function esSolicitudSensible(
        string $mensaje
    ): bool {

        $mensaje = mb_strtolower(
            trim($mensaje),
            'UTF-8'
        );

        /*
        |--------------------------------------------------------------------------
        | Credenciales y secretos
        |--------------------------------------------------------------------------
        */

        $patronesSensibles = [

            'contraseña',

            'contrasena',

            'password',

            'clave del administrador',

            'clave de administrador',

            'credenciales',

            'credential',

            'token',

            'api key',

            'apikey',

            'secret key',

            'clave secreta',

            'clave privada',

            'private key',

            '.env',

            'archivo env',

            'credenciales de base de datos',

            'contraseña de base de datos',

            'password de base de datos',

            'usuario de postgres',

            'contraseña de postgres',

            'password de postgres',

            'credenciales de postgres',

            'cookie',

            'sesión',

            'session',

            'session token',

            'access token',

            'refresh token',

        ];


        foreach ($patronesSensibles as $patron) {

            if (str_contains(
                $mensaje,
                $patron
            )) {

                return true;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Intentos de obtener información interna
        |--------------------------------------------------------------------------
        */

        $patronesInternos = [

            'muéstrame el hash',

            'muestrame el hash',

            'hash de contraseña',

            'hash de password',

            'muéstrame el código fuente',

            'muestrame el codigo fuente',

            'código fuente del sistema',

            'codigo fuente del sistema',

            'estructura interna',

            'variables de entorno',

            'environment variables',

        ];


        foreach ($patronesInternos as $patron) {

            if (str_contains(
                $mensaje,
                $patron
            )) {

                return true;

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Intentos destructivos
        |--------------------------------------------------------------------------
        */

        $patronesPeligrosos = [

            'borra todos los usuarios',

            'elimina todos los usuarios',

            'eliminar todos los usuarios',

            'borra toda la base de datos',

            'elimina toda la base de datos',

            'eliminar toda la base de datos',

            'borra la base de datos',

            'elimina la base de datos',

            'eliminar la base de datos',

            'drop database',

            'drop table',

            'truncate table',

            'borra todo',

            'elimina todo',

            'eliminar todo',

            'ejecuta este comando',

            'ejecuta el comando',

        ];


        foreach ($patronesPeligrosos as $patron) {

            if (str_contains(
                $mensaje,
                $patron
            )) {

                return true;

            }

        }


        return false;
    }

    /**
     * Obtener una respuesta segura para una
     * solicitud sensible o peligrosa.
     */
    public static function respuestaSolicitudSensible(): string
    {
        return '🔐 Buena jugada, pero no. No puedo proporcionar contraseñas, credenciales, tokens, claves privadas, secretos del sistema ni ejecutar instrucciones que puedan comprometer SIGEFIV. 😏';
    }

    /**
     * Obtener una respuesta cuando el usuario no
     * tiene autorización para consultar usuarios.
     */
    public static function respuestaSinPermisoUsuarios(): string
    {
        return '🔒 Esa información está reservada para usuarios con permisos administrativos. No puedo mostrar el directorio de usuarios ni sus roles.';
    }
}