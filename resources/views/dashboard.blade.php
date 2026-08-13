@extends('layouts.app')

@section('title','Dashboard')

@section('content')

<div class="card">

    <div class="card-header">

        Dashboard

    </div>

    <div class="card-body">

        <h2>Bienvenido {{ Auth::user()->name }}</h2>

        <p>CoreUI ya está funcionando con Laravel.</p>

    </div>

</div>

@endsection
