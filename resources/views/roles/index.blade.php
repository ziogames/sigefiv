@extends('layouts.app')

@section('title','Roles')

@section('content')

<div class="container-fluid">

    <div class="row mb-4">

        <div class="col">

            <h2>

                <i class="cil-shield-alt"></i>

                Roles

            </h2>

            <small class="text-body-secondary">

                Administración de Roles

            </small>

        </div>

        <div class="col-auto">

            <a href="{{ route('roles.create') }}"
               class="btn btn-primary">

                <i class="cil-plus"></i>

                Nuevo Rol

            </a>

        </div>

    </div>

    <div class="row">

        @foreach($roles as $rol)

        <div class="col-md-6 col-xl-4 mb-4">

            <div class="card h-100 shadow-sm border-0">

                <div class="card-body">

                    <div class="d-flex justify-content-between">

                        <div>

                            <h4>

                                <i class="cil-shield-alt text-primary"></i>

                                {{ $rol->name }}

                            </h4>

                        </div>

                        <span class="badge bg-primary">

                            #{{ $rol->id }}

                        </span>

                    </div>

                    <hr>

                    <div class="mb-2">

                        <i class="cil-user"></i>

                        Usuarios

                        <span class="float-end">

                            {{ $rol->users()->count() }}

                        </span>

                    </div>

                    <div>

                        <i class="cil-lock-locked"></i>

                        Permisos

                        <span class="float-end">

                            {{ $rol->permissions()->count() }}

                        </span>

                    </div>

                </div>

                <div class="card-footer bg-white border-0">

                    <div class="d-grid gap-2">

                        <a
                            href="{{ route('roles.edit',$rol) }}"
                            class="btn btn-warning">

                            <i class="cil-pencil"></i>

                            Editar

                        </a>

                       
                                    @if($rol->name !== 'Administrador')

                                <form
                                    action="{{ route('roles.destroy',$rol) }}"
                                    method="POST"
                                    class="eliminar">

                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-danger w-100">

                                        <i class="cil-trash"></i>

                                        Eliminar

                                    </button>

                                </form>

                                @endif

                       

                    </div>

                </div>

            </div>

        </div>

        @endforeach

    </div>

    <div class="mt-4">

        {{ $roles->links() }}

    </div>

</div>

@push('js')

<script>

document.querySelectorAll('.eliminar').forEach(form=>{

form.addEventListener('submit',function(e){

e.preventDefault();

Swal.fire({

title:'Eliminar Rol',

text:'¿Desea continuar?',

icon:'warning',

showCancelButton:true,

confirmButtonText:'Sí',

cancelButtonText:'Cancelar'

}).then((r)=>{

if(r.isConfirmed){

form.submit();

}

});

});

});

</script>

@endpush

@endsection