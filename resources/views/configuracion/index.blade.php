@extends('layouts.app')

@section('title','Configuración')

@section('content')

<div class="card shadow-sm border-0">

    <div class="card-header">

        <h4 class="mb-0">

            <i class="cil-settings"></i>

            Configuración del Sistema

        </h4>

    </div>

    <form
        action="{{ route('configuracion.store') }}"
        method="POST"
        enctype="multipart/form-data">

        @csrf

        <div class="card-body">

           

            @if($errors->any())

                <div class="alert alert-danger">

                    <ul class="mb-0">

                        @foreach($errors->all() as $error)

                            <li>{{ $error }}</li>

                        @endforeach

                    </ul>

                </div>

            @endif

            <ul
                class="nav nav-tabs mb-4"
                role="tablist">

                <li class="nav-item">

                    <button
                        type="button"
                        class="nav-link active"
                        data-coreui-toggle="tab"
                        data-coreui-target="#general">

                        <i class="cil-settings"></i>

                        General

                    </button>

                </li>

                <li class="nav-item">

                    <button
                        type="button"
                        class="nav-link"
                        data-coreui-toggle="tab"
                        data-coreui-target="#organizacion">

                        <i class="cil-building"></i>

                        Organización

                    </button>

                </li>

                <li class="nav-item">

                    <button
                        type="button"
                        class="nav-link"
                        data-coreui-toggle="tab"
                        data-coreui-target="#apariencia">

                        <i class="cil-image"></i>

                        Apariencia

                    </button>

                </li>

                <li class="nav-item">

                    <button
                        type="button"
                        class="nav-link"
                        data-coreui-toggle="tab"
                        data-coreui-target="#sistema">

                        <i class="cil-cog"></i>

                        Sistema

                    </button>

                </li>
                                <li class="nav-item">

                    <button
                        type="button"
                        class="nav-link"
                        data-coreui-toggle="tab"
                        data-coreui-target="#contabilidad">

                        <i class="cil-calculator"></i>

                        Contabilidad

                    </button>

                </li>

                <li class="nav-item">

                    <button
                        type="button"
                        class="nav-link"
                        data-coreui-toggle="tab"
                        data-coreui-target="#seguridad">

                        <i class="cil-lock-locked"></i>

                        Seguridad

                    </button>

                </li>

            </ul>

           <div class="tab-content">

    @include('configuracion.tabs.general')

    @include('configuracion.tabs.organizacion')

    @include('configuracion.tabs.apariencia')

    @include('configuracion.tabs.sistema')
    @include('configuracion.tabs.contabilidad')

    @include('configuracion.tabs.seguridad')

</div>

</div>

<div class="card-footer text-end">

    <button
        class="btn btn-primary">

        <i class="cil-save"></i>

        Guardar Configuración

    </button>

</div>

</form>

</div>
@push('scripts')

<script>

function preview(input, id){

    input.addEventListener('change', function(){

        if(this.files.length){

            document.getElementById(id).src =
                URL.createObjectURL(this.files[0]);

        }

    });

}

preview(document.getElementById('logo'),'preview-logo');

preview(document.getElementById('favicon'),'preview-favicon');

preview(document.getElementById('imagen_login'),'preview-login');

</script>

@endpush
@endsection