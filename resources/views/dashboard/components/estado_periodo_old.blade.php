{{-- @if($cierrePeriodo['requiere_cierre']) --}}
@if(true)

<div class="periodo-card">

   <div class="periodo-card-header">

    <div>

        <div class="periodo-title">

            <i class="cil-calendar me-2"></i>

            Centro de Control del Período

        </div>

        <div class="periodo-subtitle">

            {{ $cierrePeriodo['periodo']->nombre_completo }}

            ha finalizado.

        </div>

    </div>

    <div class="periodo-status">

        <span class="status-dot"></span>

        Pendiente de cierre

    </div>

</div>

    <div class="periodo-divider"></div>

    <div class="periodo-message">

        Antes de comenzar

        <strong>

            {{ $cierrePeriodo['siguiente_periodo'] }}

        </strong>

        es necesario cerrar el período actual.

    </div>
<div class="periodo-grid">

    <div class="periodo-item">

        <span class="periodo-label">

            Saldo inicial

        </span>

        <span class="periodo-value">

            S/ {{ number_format($cierrePeriodo['resumen']['saldo_inicial'],2) }}

        </span>

    </div>

    <div class="periodo-item">

        <span class="periodo-label">

            Ingresos

        </span>

        <span class="periodo-value periodo-success">

            S/ {{ number_format($cierrePeriodo['resumen']['ingresos'],2) }}

        </span>

    </div>

    <div class="periodo-item">

        <span class="periodo-label">

            Egresos

        </span>

        <span class="periodo-value periodo-danger">

            S/ {{ number_format($cierrePeriodo['resumen']['egresos'],2) }}

        </span>

    </div>

    <div class="periodo-item periodo-total">

        <span class="periodo-label">

            Saldo final

        </span>

        <span class="periodo-value">

            S/ {{ number_format($cierrePeriodo['resumen']['saldo_final'],2) }}

        </span>

    </div>

</div>
        <div class="periodo-checks">

    <div class="checks-title">

        <i class="cil-shield-alt me-2"></i>

        Verificaciones antes del cierre

    </div>

    <div class="checks-grid">

        <div class="check-item ok">

            <i class="cil-check-circle"></i>

            Totales recalculados

        </div>

        <div class="check-item ok">

            <i class="cil-check-circle"></i>

            Saldo actualizado

        </div>

        <div class="check-item ok">

            <i class="cil-check-circle"></i>

            Bitácora disponible

        </div>

        <div class="check-item ok">

            <i class="cil-check-circle"></i>

            Sistema listo para cerrar

        </div>

    </div>

</div>
    <div class="periodo-actions">
<button
    class="btn-sigefiv btn-sigefiv-secondary">

    <i class="cil-list me-2"></i>

    Revisar movimientos

</button>

<button
    class="btn-sigefiv btn-sigefiv-primary">

    <i class="cil-lock-locked me-2"></i>

    Cerrar período

</button>

    </div>

</div>

@endif