<x-card
    title="Mi Rol y Permisos"
    icon="cil-shield-alt"
    class="mt-4">

    <div class="mb-4">

        <h6 class="text-body-secondary">

            Rol asignado

        </h6>

        @forelse($usuario->roles as $rol)

            <span class="badge bg-primary fs-6">

                {{ $rol->name }}

            </span>

        @empty

            <span class="badge bg-secondary">

                Sin Rol

            </span>

        @endforelse

    </div>

    <hr>

    <h6 class="text-body-secondary mb-3">

        Funciones disponibles

    </h6>

    @php

        $permisos = $usuario->getAllPermissions();

        $nombres = [

           // Usuarios
'usuarios.index' => 'Ver usuarios',
'usuarios.create' => 'Crear usuarios',
'usuarios.edit' => 'Editar usuarios',
'usuarios.delete' => 'Eliminar usuarios',
'usuarios.destroy' => 'Eliminar usuarios',

// Roles
'roles.index' => 'Ver roles',
'roles.create' => 'Crear roles',
'roles.edit' => 'Editar roles',
'roles.delete' => 'Eliminar roles',
'roles.destroy' => 'Eliminar roles',

// Ingresos
'ingresos.index' => 'Ver ingresos',
'ingresos.create' => 'Registrar ingresos',
'ingresos.edit' => 'Editar ingresos',
'ingresos.delete' => 'Eliminar ingresos',
'ingresos.destroy' => 'Eliminar ingresos',

// Egresos
'egresos.index' => 'Ver egresos',
'egresos.create' => 'Registrar egresos',
'egresos.edit' => 'Editar egresos',
'egresos.delete' => 'Eliminar egresos',
'egresos.destroy' => 'Eliminar egresos',

// Movimientos
'movimientos.index' => 'Ver movimientos',
'movimientos.create' => 'Registrar movimientos',
'movimientos.edit' => 'Editar movimientos',
'movimientos.delete' => 'Eliminar movimientos',
'movimientos.destroy' => 'Eliminar movimientos',

// Caja
'caja.index' => 'Ver caja',
'caja.create' => 'Registrar movimiento de caja',
'caja.edit' => 'Editar movimiento de caja',
'caja.delete' => 'Eliminar movimiento de caja',
'caja.destroy' => 'Eliminar movimiento de caja',

// Reportes
'reportes.index' => 'Ver reportes',
'reportes.create' => 'Generar reportes',
'reportes.export' => 'Exportar reportes',

// Mi Cuenta
'perfil.index' => 'Ver mi cuenta',
'perfil.update' => 'Actualizar mi información',
'perfil.password' => 'Cambiar contraseña',
'perfil.foto' => 'Cambiar fotografía',

// Configuración
'configuracion.index' => 'Ver configuración',
'configuracion.update' => 'Modificar configuración',

// Auditoría
'auditoria.index' => 'Ver auditoría',

// Dashboard
'dashboard' => 'Acceder al Dashboard',

        ];

    @endphp

    @forelse($permisos as $permiso)

        <div class="d-flex align-items-center mb-2">

            <i class="cil-check-circle text-success me-2"></i>

            <span>

                {{ $nombres[$permiso->name] ?? $permiso->name }}

            </span>

        </div>

    @empty

        <div class="alert alert-warning mb-0">

            Este usuario no tiene funciones asignadas.

        </div>

    @endforelse

</x-card>