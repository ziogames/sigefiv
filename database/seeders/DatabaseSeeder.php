<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Mantener los seeders actuales de permisos y roles.
        $this->call(PermissionSeeder::class);
        $this->call(RolesSeeder::class);

        // Obtener el correo configurado en .env.
        $email = config('admin.email');

        if (empty($email)) {
            throw new RuntimeException(
                'Falta configurar SIGEFIV_ADMIN_EMAIL en .env.'
            );
        }

        // Si el usuario ya existe, no modificarlo.
        $admin = User::where('email', $email)->first();

        if ($admin) {
            $this->command?->info(
                'El administrador ya existe. No se modificaron sus datos ni sus roles.'
            );

            return;
        }

        // Solo exigir contraseña cuando se va a crear el usuario.
        $password = config('admin.password');

        if (empty($password)) {
            throw new RuntimeException(
                'Falta configurar SIGEFIV_ADMIN_PASSWORD en .env.'
            );
        }

        // Crear el administrador únicamente si no existe.
        $admin = User::create([
            'name' => config('admin.name', 'Administrador'),
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Asignar el rol únicamente al administrador recién creado.
        $admin->syncRoles('Administrador');

        $this->command?->info(
            'Administrador creado y rol asignado correctamente.'
        );
    }
}
