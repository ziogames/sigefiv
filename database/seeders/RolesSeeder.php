<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate([
            'name' => 'Administrador'
        ]);

        $tesorero = Role::firstOrCreate([
            'name' => 'Tesorero'
        ]);

        $secretario = Role::firstOrCreate([
            'name' => 'Secretario'
        ]);

        $consulta = Role::firstOrCreate([
            'name' => 'Consulta'
        ]);

        // Administrador tiene todos los permisos

        $admin->syncPermissions(
            Permission::all()
        );

        // Usuario administrador

        $user = User::find(1);

        if ($user) {

            $user->assignRole($admin);

        }
    }
}