<?php

namespace App\Services\Sigi;

use Spatie\Permission\Models\Role;

class SigiRolesService
{
    public function consultarRoles(
        array $interpretacion,
        string $operacion
    ): array {

        $roles =
            Role::query();


        if ($operacion === 'count') {

            $resultado =
                $roles->count();


            return [

                'success' => true,

                'tipo' => 'numero',

                'resultado' =>
                    $resultado,

                'mensaje' =>
                    'Actualmente existen ' .
                    $resultado .
                    ' roles registrados.',

            ];
        }


        $resultado =
            $roles
                ->select(
                    'id',
                    'name'
                )
                ->get();


        return [

            'success' => true,

            'tipo' => 'lista',

            'resultado' =>
                $resultado,

            'mensaje' =>
                'Encontré ' .
                $resultado->count() .
                ' roles registrados.',

        ];
    }
}