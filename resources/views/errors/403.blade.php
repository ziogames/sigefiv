@extends('layouts.app')

@section('title','Acceso denegado')

@section('content')

<div class="container-fluid">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow border-0">

                <div class="card-body text-center p-5">

                    <i class="cil-lock-locked text-danger"
                       style="font-size:90px"></i>

                    <h1 class="mt-4">

                        403

                    </h1>

                    <h4>

                        Acceso denegado

                    </h4>

                    <p class="text-body-secondary">

                        No tiene permisos para acceder a este módulo.

                    </p>

                    <a
                        href="{{ route('dashboard') }}"
                        class="btn btn-primary">

                        <i class="cil-arrow-left"></i>

                        Volver al Dashboard

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection