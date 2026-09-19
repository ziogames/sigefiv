<?php
namespace App\Services\Zoe;

use App\Services\Zoe\ZoeQueryService;


class ZoePeriodoConsultaService
{
    public function __construct(
        private ZoeQueryService $zoeQuery
    ) {
    }

    /**
     * Resuelve el mes y año que utilizará la consulta.
     *
     * Reglas:
     *
     * 1. Sin mes ni año:
     *    utiliza el período activo.
     *
     * 2. Con mes y sin año:
     *    utiliza el mes indicado y el año activo.
     *
     * 3. Con año y sin mes:
     *    utiliza el año indicado y el mes activo.
     *
     * 4. Con mes y año:
     *    respeta ambos.
     */
    public function resolverPeriodo(
        array $interpretacion
    ): array {

        $periodoActivo =
            $this->zoeQuery->periodoActual();

        /*
        |--------------------------------------------------------------------------
        | DATOS EXPLÍCITOS DE LA CONSULTA
        |--------------------------------------------------------------------------
        */

        $anio =
            $interpretacion['anio']
            ?? $interpretacion['año']
            ?? null;

        $mes =
            $interpretacion['mes']
            ?? null;

        $meses =
            $interpretacion['meses']
            ?? [];

        if (!is_array($meses)) {
            $meses = [];
        }

        /*
        |--------------------------------------------------------------------------
        | NORMALIZAR VALORES
        |--------------------------------------------------------------------------
        */

        $anio =
            is_numeric($anio)
                ? (int) $anio
                : null;

        $mes =
            is_numeric($mes)
                ? (int) $mes
                : null;

        $meses =
            array_values(
                array_filter(
                    array_map(
                        static fn ($valor) =>
                            is_numeric($valor)
                                ? (int) $valor
                                : null,
                        $meses
                    ),
                    static fn ($valor) =>
                        $valor !== null &&
                        $valor >= 1 &&
                        $valor <= 12
                )
            );

        /*
        |--------------------------------------------------------------------------
        | DETERMINAR SI EXISTE MES EXPLÍCITO
        |--------------------------------------------------------------------------
        */

        $tieneMesExplicito =
            !empty($meses) || $mes !== null;

        if (
            empty($meses) &&
            $mes !== null
        ) {
            $meses = [$mes];
        }

        /*
        |--------------------------------------------------------------------------
        | APLICAR PERÍODO ACTIVO
        |--------------------------------------------------------------------------
        */

        if ($periodoActivo) {

            /*
            | Si no se indicó año, utilizar año activo.
            */

            if ($anio === null) {

                $anio =
                    (int) $periodoActivo->anio;
            }

            /*
            | Si no se indicó mes, utilizar mes activo.
            */

            if (!$tieneMesExplicito) {

                $mes =
                    (int) $periodoActivo->mes;

                /*
                | Eliminamos cualquier mes heredado.
                */

                $meses = [$mes];

            } elseif ($mes === null) {

                $mes =
                    $meses[0] ?? null;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN FINAL
        |--------------------------------------------------------------------------
        */

        if (
            $mes !== null &&
            ($mes < 1 || $mes > 12)
        ) {
            $mes = null;
        }

        if (
            $anio !== null &&
            ($anio < 2000 || $anio > 2100)
        ) {
            $anio = null;
        }

        return [
            'anio' => $anio,

            'mes' => $mes,

            'meses' => $meses,

            'periodo_activo_encontrado' =>
                $periodoActivo !== null,

            'mes_explicito' =>
                $tieneMesExplicito,

           'anio_explicito' =>
    isset($interpretacion['anio'])
    || isset($interpretacion['año']),
        ];
    }

    /**
     * Determina el límite de movimientos.
     *
     * Sin límite:
     * - Últimos movimientos.
     * - Últimos ingresos.
     * - Últimos egresos.
     *
     * Un movimiento:
     * - Último ingreso.
     * - Último egreso.
     */
    public function resolverLimite(
        string $texto,
        ?int $limiteExplicito = null
    ): ?int {

        $textoNormalizado =
            mb_strtolower(
                trim($texto),
                'UTF-8'
            );

        /*
        |--------------------------------------------------------------------------
        | LÍMITE EXPLÍCITO
        |--------------------------------------------------------------------------
        */

        if ($limiteExplicito !== null) {

            return max(
                1,
                min($limiteExplicito, 100)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTAS SINGULARES
        |--------------------------------------------------------------------------
        */

        $esUltimoIngreso =
            preg_match(
                '/\b(?:ultimo|último)\s+ingreso\b/u',
                $textoNormalizado
            ) === 1;

        $esUltimoEgreso =
            preg_match(
                '/\b(?:ultimo|último)\s+egreso\b/u',
                $textoNormalizado
            ) === 1;

        if (
            $esUltimoIngreso ||
            $esUltimoEgreso
        ) {
            return 1;
        }

        /*
        |--------------------------------------------------------------------------
        | CONSULTAS PLURALES
        |--------------------------------------------------------------------------
        |
        | Null significa que no se debe imponer
        | un límite de 20 o de 1 desde este servicio.
        |--------------------------------------------------------------------------
        */

        return null;
    }
}