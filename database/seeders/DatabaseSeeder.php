<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Permisos
        $this->call(PermissionSeeder::class);

        // Roles
        $this->call(RolesSeeder::class);

        // Crear o actualizar administrador
        $admin = User::updateOrCreate(

            [
                'email' => 'admin@sigefiv.com',
            ],

            [
                'name' => 'Administrador',
                'password' => Hash::make('12345678'),
            ]

        );

        // Asignar rol
        $admin->syncRoles('Administrador');
    }
}