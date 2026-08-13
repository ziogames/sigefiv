@extends('layouts.app')

@section('title','Categoría')

@section('content')

<div class="card">

    <div class="card-header">

        <h4>

            {{ $categoria->nombre }}

        </h4>

    </div>

    <div class="card-body">

        <table class="table">

            <tr>

                <th>Tipo</th>

                <td>{{ $categoria->tipo }}</td>

            </tr>

            <tr>

                <th>Color</th>

                <td>

                    <span class="badge bg-{{ $categoria->color }}">

                        {{ $categoria->color }}

                    </span>

                </td>

            </tr>

            <tr>

                <th>Icono</th>

                <td>

                    <i class="{{ $categoria->icono }}"></i>

                    {{ $categoria->icono }}

                </td>

            </tr>

            <tr>

                <th>Estado</th>

                <td>

                    @if($categoria->activo)

                        <span class="badge bg-success">

                            Activo

                        </span>

                    @else

                        <span class="badge bg-danger">

                            Inactivo

                        </span>

                    @endif

                </td>

            </tr>

        </table>

    </div>

</div>

@endsection