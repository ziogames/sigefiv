<?php

namespace App\Services\Zoe;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ZoeQueryService
{
    /**
     * Conexión exclusiva de lectura de ZOE.
     */
    protected string $connection = 'zoe';

    /**
     * Vista de movimientos detallados.
     */
    protected string $vistaMovimientos = 'public.zoe_movimientos_detallados';

    /**
     * Vista de períodos financieros.
     */
    protected string $vistaPeriodos = 'public.zoe_periodos_financieros';

    /**
     * Obtiene la consulta base de movimientos.
     */
    protected function movimientosQuery()
    {
        return DB::connection($this->connection)
            ->table($this->vistaMovimientos);
    }

    /**
     * Obtiene la consulta base de períodos.
     */
    protected function periodosQuery()
    {
        return DB::connection($this->connection)
            ->table($this->vistaPeriodos);
    }

    /*
    |--------------------------------------------------------------------------
    | MOVIMIENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Lista movimientos.
     */
    public function listarMovimientos(array $filtros = []): Collection
    {
        $query = $this->movimientosQuery();

        $this->aplicarFiltrosMovimientos($query, $filtros);

        $limite = isset($filtros['limite'])
            ? max(1, min((int) $filtros['limite'], 100))
            : 20;

        return $query
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();
    }

    /**
     * Cuenta movimientos.
     */
    public function contarMovimientos(array $filtros = []): int
    {
        $query = $this->movimientosQuery();

        $this->aplicarFiltrosMovimientos($query, $filtros);

        return (int) $query->count();
    }

    /**
     * Suma movimientos.
     */
    public function sumarMovimientos(array $filtros = []): float
    {
        $query = $this->movimientosQuery();

        $this->aplicarFiltrosMovimientos($query, $filtros);

        return (float) ($query->sum('monto') ?? 0);
    }

    /**
     * Promedio de movimientos.
     */
    public function promedioMovimientos(array $filtros = []): float
    {
        $query = $this->movimientosQuery();

        $this->aplicarFiltrosMovimientos($query, $filtros);

        return (float) ($query->avg('monto') ?? 0);
    }

    /**
     * Obtiene el mayor movimiento.
     */
    public function maximoMovimiento(array $filtros = []): ?object
    {
        $query = $this->movimientosQuery();

        $this->aplicarFiltrosMovimientos($query, $filtros);

        return $query
            ->orderByDesc('monto')
            ->orderByDesc('fecha')
            ->first();
    }

    /**
     * Obtiene el menor movimiento.
     */
    public function minimoMovimiento(array $filtros = []): ?object
    {
        $query = $this->movimientosQuery();

        $this->aplicarFiltrosMovimientos($query, $filtros);

        return $query
            ->orderBy('monto')
            ->orderByDesc('fecha')
            ->first();
    }

    /**
     * Aplica filtros controlados a movimientos.
     *
     * IMPORTANTE:
     * Nunca recibe SQL generado por Ollama.
     */
    protected function aplicarFiltrosMovimientos($query, array $filtros): void
    {
        /*
        |--------------------------------------------------------------------------
        | TIPO
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['tipo_movimiento'])) {

            $tipo = $filtros['tipo_movimiento'];

            if (in_array($tipo, ['Ingreso', 'Egreso'], true)) {
                $query->where('tipo', $tipo);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | AÑO
        |--------------------------------------------------------------------------
        */

        if (
            isset($filtros['anio']) &&
            is_numeric($filtros['anio'])
        ) {
            $query->where(
                'anio',
                (int) $filtros['anio']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MES
        |--------------------------------------------------------------------------
        */

        if (
            isset($filtros['mes']) &&
            is_numeric($filtros['mes'])
        ) {
            $mes = (int) $filtros['mes'];

            if ($mes >= 1 && $mes <= 12) {
                $query->where('mes', $mes);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MÚLTIPLES MESES
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['meses']) && is_array($filtros['meses'])) {

            $meses = array_values(
                array_filter(
                    array_map('intval', $filtros['meses']),
                    fn ($mes) => $mes >= 1 && $mes <= 12
                )
            );

            if (!empty($meses)) {
                $query->whereIn('mes', $meses);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORÍA
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['categoria']) &&
            is_scalar($filtros['categoria'])
        ) {

            $categoria = trim(
                (string) $filtros['categoria']
            );

            if ($categoria !== '') {

                $query->where(
                    'categoria',
                    'ILIKE',
                    '%' . $categoria . '%'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CATEGORÍAS
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['categorias']) &&
            is_array($filtros['categorias'])
        ) {

            $categorias = [];

            array_walk_recursive(
                $filtros['categorias'],
                function ($valor) use (&$categorias) {
                    if (is_scalar($valor)) {
                        $valor = trim((string) $valor);

                        if ($valor !== '') {
                            $categorias[] = $valor;
                        }
                    }
                }
            );

            $categorias = array_values(array_unique($categorias));

            if (!empty($categorias)) {

                $query->where(function ($subQuery) use ($categorias) {

                    foreach ($categorias as $index => $categoria) {

                        if ($index === 0) {

                            $subQuery->where(
                                'categoria',
                                'ILIKE',
                                '%' . $categoria . '%'
                            );

                        } else {

                            $subQuery->orWhere(
                                'categoria',
                                'ILIKE',
                                '%' . $categoria . '%'
                            );
                        }
                    }
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CONCEPTO
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['concepto']) &&
            is_scalar($filtros['concepto'])
        ) {

            $concepto = trim(
                (string) $filtros['concepto']
            );

            if ($concepto !== '') {

                $query->where(
                    'concepto',
                    'ILIKE',
                    '%' . $concepto . '%'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PERSONA
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['persona']) &&
            is_scalar($filtros['persona'])
        ) {

            $persona = trim(
                (string) $filtros['persona']
            );

            if ($persona !== '') {

                $query->where(
                    'persona',
                    'ILIKE',
                    '%' . $persona . '%'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FORMA DE PAGO
        |--------------------------------------------------------------------------
        */

        if (
            !empty($filtros['forma_pago']) &&
            is_scalar($filtros['forma_pago'])
        ) {

            $formaPago = trim(
                (string) $filtros['forma_pago']
            );

            if ($formaPago !== '') {

                $query->where(
                    'forma_pago',
                    'ILIKE',
                    '%' . $formaPago . '%'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FECHA DESDE
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['fecha_desde'])) {

            $query->where(
                'fecha',
                '>=',
                $filtros['fecha_desde']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FECHA HASTA
        |--------------------------------------------------------------------------
        */

        if (!empty($filtros['fecha_hasta'])) {

            $query->where(
                'fecha',
                '<=',
                $filtros['fecha_hasta']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PERÍODOS
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene períodos.
     */
    public function listarPeriodos(array $filtros = []): Collection
    {
        $query = $this->periodosQuery();

        $this->aplicarFiltrosPeriodos($query, $filtros);

        return $query
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->limit(100)
            ->get();
    }

    /**
     * Obtiene un período concreto.
     */
    public function obtenerPeriodo(
        ?int $anio = null,
        ?int $mes = null
    ): ?object {

        $query = $this->periodosQuery();

        if ($anio !== null) {
            $query->where('anio', $anio);
        }

        if ($mes !== null) {
            $query->where('mes', $mes);
        }

        return $query
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->first();
    }

    /**
     * Aplica filtros de períodos.
     */
    protected function aplicarFiltrosPeriodos(
        $query,
        array $filtros
    ): void {

        if (
            isset($filtros['anio']) &&
            is_numeric($filtros['anio'])
        ) {
            $query->where(
                'anio',
                (int) $filtros['anio']
            );
        }

        if (
            isset($filtros['mes']) &&
            is_numeric($filtros['mes'])
        ) {

            $mes = (int) $filtros['mes'];

            if ($mes >= 1 && $mes <= 12) {
                $query->where('mes', $mes);
            }
        }

        if (!empty($filtros['meses']) && is_array($filtros['meses'])) {

            $meses = array_values(
                array_filter(
                    array_map('intval', $filtros['meses']),
                    fn ($mes) => $mes >= 1 && $mes <= 12
                )
            );

            if (!empty($meses)) {
                $query->whereIn('mes', $meses);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FINANZAS POR PERÍODO
    |--------------------------------------------------------------------------
    */

    /**
     * Obtiene el total de ingresos de un período.
     */
    public function totalIngresosPeriodo(
        int $anio,
        int $mes
    ): float {

        $periodo = $this->obtenerPeriodo($anio, $mes);

        if (!$periodo) {
            return 0.0;
        }

        return (float) $periodo->total_ingresos;
    }

    /**
     * Obtiene el total de egresos de un período.
     */
    public function totalEgresosPeriodo(
        int $anio,
        int $mes
    ): float {

        $periodo = $this->obtenerPeriodo($anio, $mes);

        if (!$periodo) {
            return 0.0;
        }

        return (float) $periodo->total_egresos;
    }

    /**
     * Obtiene el saldo final de un período.
     */
    public function saldoFinalPeriodo(
        int $anio,
        int $mes
    ): float {

        $periodo = $this->obtenerPeriodo($anio, $mes);

        if (!$periodo) {
            return 0.0;
        }

        return (float) $periodo->saldo_final;
    }

    /**
     * Obtiene el saldo inicial de un período.
     */
    public function saldoInicialPeriodo(
        int $anio,
        int $mes
    ): float {

        $periodo = $this->obtenerPeriodo($anio, $mes);

        if (!$periodo) {
            return 0.0;
        }

        return (float) $periodo->saldo_inicial;
    }

    /*
    |--------------------------------------------------------------------------
    | RESUMEN
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve un resumen financiero de un período.
     */
    public function resumenPeriodo(
        int $anio,
        int $mes
    ): ?array {

        $periodo = $this->obtenerPeriodo($anio, $mes);

        if (!$periodo) {
            return null;
        }

        return [
            'id'             => $periodo->id,
            'anio'           => (int) $periodo->anio,
            'mes'            => (int) $periodo->mes,
            'nombre'         => $periodo->nombre,
            'saldo_inicial'  => (float) $periodo->saldo_inicial,
            'total_ingresos' => (float) $periodo->total_ingresos,
            'total_egresos'  => (float) $periodo->total_egresos,
            'saldo_final'    => (float) $periodo->saldo_final,
            'estado'         => $periodo->estado,
            'fecha_cierre'   => $periodo->fecha_cierre,
        ];
    }

    /**
     * Devuelve el último período registrado.
     */
    public function periodoActual(): ?object
    {
        return $this->periodosQuery()
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->first();
    }
}