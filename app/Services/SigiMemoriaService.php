<?php

namespace App\Services;

use App\Models\MemoriaSigi;
use Illuminate\Support\Collection;

class SigiMemoriaService
{
    /**
     * Guarda o actualiza una memoria de SIGI.
     */
    public function recordar(
        ?int $usuarioId,
        string $tipo,
        string $clave,
        string $contenido,
        int $importancia = 3
    ): MemoriaSigi {
        return MemoriaSigi::updateOrCreate(
            [
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'clave' => $clave,
            ],
            [
                'contenido' => $contenido,
                'importancia' => max(1, min(5, $importancia)),
            ]
        );
    }

    /**
     * Recupera una memoria concreta.
     */
    public function recordarUna(
        ?int $usuarioId,
        string $tipo,
        string $clave
    ): ?MemoriaSigi {
        return MemoriaSigi::query()
            ->where(function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId)
                    ->orWhereNull('usuario_id');
            })
            ->where('tipo', $tipo)
            ->where('clave', $clave)
            ->orderByDesc('usuario_id')
            ->first();
    }

    /**
     * Recupera las memorias disponibles para un usuario.
     */
    public function memorias(
        ?int $usuarioId,
        ?string $tipo = null
    ): Collection {
        return MemoriaSigi::query()
            ->where(function ($query) use ($usuarioId) {
                $query->where('usuario_id', $usuarioId)
                    ->orWhereNull('usuario_id');
            })
            ->when(
                $tipo,
                fn ($query) => $query->where('tipo', $tipo)
            )
            ->orderByDesc('importancia')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * Elimina una memoria concreta.
     */
    public function olvidar(
        ?int $usuarioId,
        string $tipo,
        string $clave
    ): bool {
        return MemoriaSigi::query()
            ->where('usuario_id', $usuarioId)
            ->where('tipo', $tipo)
            ->where('clave', $clave)
            ->delete() > 0;
    }

    /**
     * Construye el contexto de memoria que recibirá SIGI.
     */
    public function contexto(
        ?int $usuarioId,
        ?string $tipo = null
    ): string {
        $memorias = $this->memorias($usuarioId, $tipo);

        if ($memorias->isEmpty()) {
            return '';
        }

        return $memorias
            ->map(function (MemoriaSigi $memoria) {
                return sprintf(
                    '- %s: %s',
                    $memoria->clave,
                    $memoria->contenido
                );
            })
            ->implode("\n");
    }
    /**
 * Detecta una solicitud explícita de memoria.
 */
public function detectarYRecordar(
    ?int $usuarioId,
    string $mensaje
): ?MemoriaSigi {
    $texto = trim($mensaje);

    $patrones = [
        '/^recuerda que\s+(.+)$/iu',
        '/^acuérdate de que\s+(.+)$/iu',
        '/^acuerdate de que\s+(.+)$/iu',
        '/^quiero que recuerdes que\s+(.+)$/iu',
        '/^no olvides que\s+(.+)$/iu',
    ];

    foreach ($patrones as $patron) {

        if (preg_match($patron, $texto, $coincidencia)) {

            $contenido = trim($coincidencia[1]);

            if ($contenido === '') {
                return null;
            }

            return $this->recordar(
                $usuarioId,
                'contexto',
                'recuerdo_' . md5(mb_strtolower($contenido)),
                $contenido,
                4
            );
        }
    }

    return null;
}
}