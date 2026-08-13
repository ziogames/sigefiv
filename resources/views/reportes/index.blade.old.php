@extends('layouts.app')

@section('title','Reportes')

@section('content')

<div class="card shadow-sm border-0">

    <div class="card-header">

        <h4>

            <i class="cil-chart"></i>

            Reportes

        </h4>

    </div>

    <div class="card-body">

        <form method="GET" action="{{ route('reportes.index') }}">

            <div class="row">

                <div class="col-md-4">

                    <label class="form-label">

                        Reporte

                    </label>
<select
    name="reporte"
    class="form-select">

    <option
        value="estado"
        {{ request('reporte','estado') == 'estado' ? 'selected' : '' }}>

        Estado de Cuenta

    </option>

    <option
        value="ingresos"
        {{ request('reporte') == 'ingresos' ? 'selected' : '' }}>

        Ingresos

    </option>

    <option
        value="egresos"
        {{ request('reporte') == 'egresos' ? 'selected' : '' }}>

        Egresos

    </option>

    <option
        value="caja"
        {{ request('reporte') == 'caja' ? 'selected' : '' }}>

        Caja

    </option>

</select>

                </div>

                <div class="col-md-2">

                    <label class="form-label">

                        Año

                    </label>

                    <select
                        name="anio"
                        class="form-select">

                        @foreach($anios as $anio)

                           <option
value="{{ $anio }}"
@selected(request('anio')==$anio)>

{{ $anio }}

</option>

                        @endforeach

                    </select>

                </div>

                <div class="col-md-3">

                    <label class="form-label">

                        Mes

                    </label>

                    <select
                        name="mes"
                        class="form-select">

                            <option value="">Todos</option>

                            @foreach($meses as $numero=>$mes)

                                <option
value="{{ $numero }}"
@selected(request('mes')==$numero)>

{{ $mes }}

</option>

                            @endforeach

                    </select>

                </div>

                <div class="col-md-3 d-flex align-items-end">

                    <button
class="btn btn-primary">

<i class="cil-search"></i>

Consultar 
</button>

                </div>

            </div>

        </form>

        <hr>
            @if(request()->filled('anio') && request()->filled('mes'))

<div class="card shadow-sm border-0 mb-4">

    <div class="card-body">

        <div class="row">

            <div class="col-md-4">

                <small class="text-muted">

                    Reporte

                </small>

                <h5>

                    {{ $titulo }}

                </h5>

            </div>

            <div class="col-md-4">

                <small class="text-muted">

                    Período

                </small>

                <h5>

                    {{ $meses[request('mes')] }}

                    {{ request('anio') }}

                </h5>

            </div>

            <div class="col-md-4">

                <small class="text-muted">

                    Estado

                </small>

                <h5>

                    @if($periodo)

                        @if($periodo->estado=='Abierto')

                            <span class="badge bg-success">

                                Abierto

                            </span>

                        @else

                            <span class="badge bg-danger">

                                Cerrado

                            </span>

                        @endif

                    @else

                        <span class="badge bg-secondary">

                            Sin período

                        </span>

                    @endif

                </h5>

            </div>

        </div>

    </div>

</div>

@endif
@if(request()->filled('anio') && request()->filled('mes'))

@if($movimientos->isEmpty())

<div class="alert alert-warning">

    <i class="cil-warning"></i>

    No existen movimientos registrados para

    <strong>

        {{ $meses[request('mes')] }}

        {{ request('anio') }}

    </strong>

</div>

@endif

@endif
@if($movimientos->count())

<div class="card shadow-sm border-0 mb-4">

    <div class="card-header">

        <h5 class="mb-0">

            <i class="cil-spreadsheet"></i>

            Resumen del Estado de Cuenta

        </h5>

    </div>

    <div class="card-body p-0">

        <div class="row text-center g-0">

            <div class="col border-end py-4">

                <small class="text-muted d-block">

                    Saldo Inicial

                </small>

                <h3 class="text-primary mb-0">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['saldo_inicial'],2) }}

                </h3>

            </div>

            <div class="col border-end py-4">

                <small class="text-muted d-block">

                    Total Ingresos

                </small>

                <h3 class="text-success mb-0">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['ingresos'],2) }}

                </h3>

            </div>

            <div class="col border-end py-4">

                <small class="text-muted d-block">

                    Disponible

                </small>

                <h3 class="text-warning mb-0">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['disponible'],2) }}

                </h3>

            </div>

            <div class="col border-end py-4">

                <small class="text-muted d-block">

                    Total Egresos

                </small>

                <h3 class="text-danger mb-0">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['egresos'],2) }}

                </h3>

            </div>

            <div class="col py-4">

                <small class="text-muted d-block">

                    Saldo en Caja

                </small>

                <h3 class="text-info mb-0">

                    {{ $configuracionGlobal->simbolo_moneda }}

                    {{ number_format($resumen['saldo_caja'],2) }}

                </h3>

            </div>

        </div>

    </div>

</div>

@endif

        @if($movimientos->count())

<div class="table-responsive">

<table class="table table-bordered table-hover">

   <thead class="table-dark">
    
<tr>
@if(in_array($reporte,['estado','caja']))

<th width="90">

    Tipo

</th>

@endif
    <th width="90">

        Fecha

    </th>

    <th width="110">

        Documento

    </th>

    <th width="170">

        Categoría

    </th>

    <th>

        Concepto

    </th>

    <th width="130">

        Persona

    </th>

   @if(in_array($reporte,['estado','caja','ingresos']))
<th class="text-end" width="110">
    Ingreso
</th>
@endif

@if(in_array($reporte,['estado','caja','egresos']))
<th class="text-end" width="110">
    Egreso
</th>
@endif

@if(in_array($reporte,['estado','caja']))
<th class="text-end" width="120">
    Saldo
</th>
@endif

</tr>

</thead>

    <tbody>

@foreach($movimientos as $movimiento)

<tr>

@if(in_array($reporte,['estado','caja']))

<td class="text-center">

    @if($movimiento->tipo=='Ingreso')

        <span class="badge bg-success">

            <i class="cil-arrow-thick-top"></i>

            Ingreso

        </span>

    @else

        <span class="badge bg-danger">

            <i class="cil-arrow-thick-bottom"></i>

            Egreso

        </span>

    @endif

</td>

@endif

<td>

    {{ $movimiento->fecha->format('d/m/Y') }}

</td>

<td>
        <span class="badge bg-secondary">

            {{ $movimiento->numero }}

        </span>

    </td>

    <td>

        {{ $movimiento->categoria->nombre }}

    </td>

    <td>

        {{ $movimiento->concepto }}

    </td>

    <td>

        {{ $movimiento->persona }}

    </td>

 @if(in_array($reporte,['estado','caja','ingresos']))
<td class="text-end">

    @if($movimiento->tipo=='Ingreso')

        <span class="text-success fw-bold">

            {{ $configuracionGlobal->simbolo_moneda }}

            {{ number_format($movimiento->monto,2) }}

        </span>

    @endif

</td>
@endif

@if(in_array($reporte,['estado','caja','egresos']))
<td class="text-end">

    @if($movimiento->tipo=='Egreso')

        <span class="text-danger fw-bold">

            - {{ $configuracionGlobal->simbolo_moneda }}

            {{ number_format($movimiento->monto,2) }}

        </span>

    @endif

</td>
@endif

@if(in_array($reporte,['estado','caja']))
<td class="text-end">

    <span class="fw-bold {{ $movimiento->saldo >= 0 ? 'text-primary' : 'text-danger' }}">

        {{ $configuracionGlobal->simbolo_moneda }}

        {{ number_format($movimiento->saldo,2) }}

    </span>

</td>
@endif

</tr>

@endforeach
</tbody>
<tfoot class="table-dark">

<tr>

    <th colspan="
@if($reporte=='estado' || $reporte=='caja')
6
@else
5
@endif
" class="text-end">

        TOTALES

    </th>

   @if(in_array($reporte,['estado','caja','ingresos']))
<th class="text-end text-success">

    {{ $configuracionGlobal->simbolo_moneda }}

    {{ number_format($resumen['ingresos'],2) }}

</th>
@endif

@if(in_array($reporte,['estado','caja','egresos']))
<th class="text-end text-danger">

    {{ $configuracionGlobal->simbolo_moneda }}

    {{ number_format($resumen['egresos'],2) }}

</th>
@endif

@if(in_array($reporte,['estado','caja']))
<th class="text-end text-info">

    {{ $configuracionGlobal->simbolo_moneda }}

    {{ number_format($resumen['saldo_caja'],2) }}

</th>
@endif

</tr>

</tfoot>


</table>

</div>

@endif

        <div id="resultado">

        </div>

    </div>

</div>

@endsection