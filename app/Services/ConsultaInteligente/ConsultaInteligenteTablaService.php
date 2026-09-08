<?php

namespace App\Services\ConsultaInteligente;

class ConsultaInteligenteTablaService
{

        public function detectarTabla(string $texto): ?string
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
        str_contains($texto, 'caja') ||
        str_contains($texto, 'cajas') ||
        str_contains($texto, 'disponible') ||
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


        public function esConsultaUsuariosPorRol(
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
}
