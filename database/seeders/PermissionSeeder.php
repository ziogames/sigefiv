<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [

            'dashboard',

            'usuarios.index',
            'usuarios.create',
            'usuarios.edit',
            'usuarios.destroy',

            'roles.index',
            'roles.create',
            'roles.edit',
            'roles.destroy',

            'categorias.index',
            'categorias.create',
            'categorias.edit',
            'categorias.destroy',

            'movimientos.index',
            'movimientos.create',
            'movimientos.edit',
            'movimientos.destroy',

            'caja.index',

            'reportes.index',

            'configuracion',

            'bitacora.index',

            'asambleas.index',
            'asambleas.create',
            'asambleas.edit',
            'asambleas.destroy',
            'asambleas.enviar',
            'asambleas.imprimir',
        ];


        /*
        |--------------------------------------------------------------------------
        | Eliminar permisos antiguos
        |--------------------------------------------------------------------------
        */

        Permission::whereIn('name', [

            'ingresos.index',
            'ingresos.create',
            'ingresos.edit',
            'ingresos.delete',

            'egresos.index',
            'egresos.create',
            'egresos.edit',
            'egresos.delete',

        ])->delete();


        /*
        |--------------------------------------------------------------------------
        | Crear permisos actuales
        |--------------------------------------------------------------------------
        */

        foreach ($permisos as $permiso) {

            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);

        }
    }
}