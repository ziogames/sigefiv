@csrf

<div class="row">

    <div class="col-md-6">

        <x-input
            label="Nombre"
            name="name"
            :value="$usuario->name ?? ''"
            required />

    </div>

    <div class="col-md-6">

        <x-input
            label="Correo electrónico"
            name="email"
            type="email"
            :value="$usuario->email ?? ''"
            required />

    </div>

</div>

<div class="row">

    <div class="col-md-6">

        <x-select
            label="Rol"
            name="role"
            :options="$roles->pluck('name','name')"
            :selected="old(
                'role',
                isset($usuario)
                    ? $usuario->roles->first()?->name
                    : null
            )" />

    </div>

</div>

<hr>

<div class="row">

   <div class="col-md-6">

    <x-input
        label="Contraseña"
        name="password"
        type="password"
        :placeholder="isset($usuario) ? 'Dejar vacío para conservar la actual' : ''" />

</div>

<div class="col-md-6">

    <x-input
        label="Confirmar contraseña"
        name="password_confirmation"
        type="password" />

</div>

</div>

<div class="d-flex justify-content-end gap-2 mt-4">

    <x-button
        type="button"
        color="secondary"
        icon="cil-arrow-left"
        onclick="location.href='{{ route('usuarios.index') }}'">

        Cancelar

    </x-button>

    <x-button
        color="primary"
        icon="cil-save">

        Guardar

    </x-button>

</div>