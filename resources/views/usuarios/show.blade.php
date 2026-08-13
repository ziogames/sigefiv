@extends('layouts.app')

@section('title','Detalle del Usuario')

@section('content')

<x-page-title
    title="Detalle del Usuario"
    icon="cil-user">

</x-page-title>

<x-card
    title="Información General"
    icon="cil-user">

    <x-table bordered>

        <tbody>

            <tr>

                <th width="220">

                    ID

                </th>

                <td>

                    {{ $usuario->id }}

                </td>

            </tr>

            <tr>

                <th>

                    Nombre

                </th>

                <td>

                    {{ $usuario->name }}

                </td>

            </tr>

            <tr>

                <th>

                    Correo Electrónico

                </th>

                <td>

                    {{ $usuario->email }}

                </td>

            </tr>

            <tr>

                <th>

                    Rol

                </th>

                <td>

                    @forelse($usuario->roles as $rol)

                        <span class="badge bg-primary">

                            {{ $rol->name }}

                        </span>

                    @empty

                        <span class="badge bg-secondary">

                            Sin Rol

                        </span>

                    @endforelse

                </td>

            </tr>

            <tr>

                <th>

                    Fecha de creación

                </th>

                <td>

                    {{ $usuario->created_at->format('d/m/Y H:i') }}

                </td>

            </tr>

            <tr>

                <th>

                    Última actualización

                </th>

                <td>

                    {{ $usuario->updated_at->format('d/m/Y H:i') }}

                </td>

            </tr>

        </tbody>

    </x-table>

    <div class="d-flex justify-content-end gap-2 mt-4">

        <x-button
            type="button"
            color="secondary"
            icon="cil-arrow-left"
            onclick="location.href='{{ route('usuarios.index') }}'">

            Regresar

        </x-button>

        <x-button
            type="button"
            color="warning"
            icon="cil-pencil"
            onclick="location.href='{{ route('usuarios.edit',$usuario) }}'">

            Editar

        </x-button>

    </div>

</x-card>

@endsection