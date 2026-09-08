<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SigiEvento extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'categoria',
        'estado',
        'resumen',
        'total_reportes',
        'reportes_problema',
        'reportes_resueltos',
        'prioridad',
        'ultimo_reporte_at',
        'ultima_intervencion_at',
        'resuelto_at',
        'cerrado_at',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(
            ChatConversation::class,
            'conversation_id'
        );
    }

    public function reportes(): HasMany
    {
        return $this->hasMany(
            SigiEventoReporte::class,
            'evento_id'
        );
    }

    protected function casts(): array
    {
        return [
            'total_reportes' => 'integer',
            'reportes_problema' => 'integer',
            'reportes_resueltos' => 'integer',
            'ultimo_reporte_at' => 'datetime',
            'ultima_intervencion_at' => 'datetime',
            'resuelto_at' => 'datetime',
            'cerrado_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Estado del evento
    |--------------------------------------------------------------------------
    */

    public function estaActivo(): bool
    {
        return in_array(
            $this->estado,
            [
                'abierto',
                'parcial',
                'resuelto',
            ],
            true
        );
    }

    public function estaAbierto(): bool
    {
        return $this->estado === 'abierto';
    }

    public function estaParcial(): bool
    {
        return $this->estado === 'parcial';
    }

    public function estaResuelto(): bool
    {
        return $this->estado === 'resuelto';
    }

    public function estaCerrado(): bool
    {
        return $this->estado === 'cerrado';
    }

    public function esAltaPrioridad(): bool
    {
        return $this->prioridad === 'alta';
    }

    /*
    |--------------------------------------------------------------------------
    | Conteos generales
    |--------------------------------------------------------------------------
    */

    public function cantidadPorTipo(string $tipo): int
    {
        return $this->reportes()
            ->where('tipo', $tipo)
            ->count();
    }

    public function cantidadUsuariosPorTipo(string $tipo): int
    {
        return $this->reportes()
            ->where('tipo', $tipo)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    public function usuariosPorTipo(string $tipo): array
    {
        return $this->reportes()
            ->where('tipo', $tipo)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->values()
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Último estado conocido de cada vecino
    |--------------------------------------------------------------------------
    */

    /**
     * Devuelve el último reporte conocido de cada usuario.
     *
     * La clave del array es user_id.
     */
    public function ultimoReportePorUsuario(): array
    {
        $reportes = $this->reportes()
            ->whereNotNull('user_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $ultimos = [];

        foreach ($reportes as $reporte) {
            $userId = (int) $reporte->user_id;

            if (!isset($ultimos[$userId])) {
                $ultimos[$userId] = $reporte;
            }
        }

        return $ultimos;
    }

    /**
     * Obtiene los últimos estados conocidos de los vecinos.
     */
    public function estadosActualesUsuarios(): array
    {
        $ultimos = $this->ultimoReportePorUsuario();

        $resultado = [];

        foreach ($ultimos as $userId => $reporte) {
            $resultado[$userId] = [
                'user_id' => $userId,
                'tipo' => $reporte->tipo,
                'mensaje' => $reporte->mensaje,
                'created_at' => $reporte->created_at,
            ];
        }

        return $resultado;
    }

    /**
     * Vecinos cuyo último reporte indica que
     * continúan afectados.
     */
    public function usuariosAfectados(): array
    {
        $ultimos = $this->ultimoReportePorUsuario();

        $tiposAfectacion = [
            'sin_servicio',
            'baja_presion',
            'servicio_intermitente',
        ];

        $usuarios = [];

        foreach ($ultimos as $userId => $reporte) {
            if (
                in_array(
                    $reporte->tipo,
                    $tiposAfectacion,
                    true
                )
            ) {
                $usuarios[] = (int) $userId;
            }
        }

        return array_values(
            array_unique($usuarios)
        );
    }

    /**
     * Vecinos cuyo último reporte indica
     * que recuperaron el servicio.
     */
    public function usuariosRestablecidos(): array
    {
        $ultimos = $this->ultimoReportePorUsuario();

        $usuarios = [];

        foreach ($ultimos as $userId => $reporte) {
            if (
                $reporte->tipo === 'restablecido'
            ) {
                $usuarios[] = (int) $userId;
            }
        }

        return array_values(
            array_unique($usuarios)
        );
    }

    public function cantidadUsuariosAfectados(): int
    {
        return count(
            $this->usuariosAfectados()
        );
    }

    public function cantidadUsuariosRestablecidos(): int
    {
        return count(
            $this->usuariosRestablecidos()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Problemas por tipo
    |--------------------------------------------------------------------------
    */

    public function conteoPorTipo(): array
    {
        return $this->reportes()
            ->selectRaw(
                'tipo, COUNT(*) as total'
            )
            ->groupBy('tipo')
            ->pluck('total', 'tipo')
            ->map(
                fn ($total) => (int) $total
            )
            ->toArray();
    }

    public function resumenReportes(): array
    {
        $conteos = $this->conteoPorTipo();

        return [
            'total' =>
                $this->total_reportes,

            'sin_servicio' =>
                $conteos['sin_servicio'] ?? 0,

            'baja_presion' =>
                $conteos['baja_presion'] ?? 0,

            'servicio_intermitente' =>
                $conteos['servicio_intermitente'] ?? 0,

            'restablecido' =>
                $conteos['restablecido'] ?? 0,

            'alerta' =>
                $conteos['alerta'] ?? 0,

            'informacion' =>
                $conteos['informacion'] ?? 0,

            'usuarios_afectados' =>
                $this->cantidadUsuariosAfectados(),

            'usuarios_restablecidos' =>
                $this->cantidadUsuariosRestablecidos(),
        ];
    }

    public function tipoPredominante(): ?string
    {
        $conteos = $this->conteoPorTipo();

        unset(
            $conteos['informacion'],
            $conteos['restablecido']
        );

        if (empty($conteos)) {
            return null;
        }

        arsort($conteos);

        return array_key_first($conteos);
    }

    public function tieneMultiplesReportes(
        int $minimo = 3
    ): bool {
        $conteos = $this->conteoPorTipo();

        foreach ($conteos as $tipo => $total) {
            if (
                $tipo !== 'informacion' &&
                $tipo !== 'restablecido' &&
                $total >= $minimo
            ) {
                return true;
            }
        }

        return false;
    }

    public function tieneProblemasMixtos(): bool
    {
        $conteos = $this->conteoPorTipo();

        $tiposProblema = array_filter(
            $conteos,
            function ($total, $tipo) {
                return
                    $total > 0 &&
                    !in_array(
                        $tipo,
                        [
                            'informacion',
                            'restablecido',
                        ],
                        true
                    );
            },
            ARRAY_FILTER_USE_BOTH
        );

        return count($tiposProblema) > 1;
    }
}