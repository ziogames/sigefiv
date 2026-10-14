<?php

namespace App\Services\Zoe;

use App\Models\ZoeConversationContext;

class ZoeConversationMemoryService
{
    public function obtener(int $usuarioId): array
    {
        $registro = ZoeConversationContext::where(
            'usuario_id',
            $usuarioId
        )->first();

        return $registro?->contexto ?? [];
    }

    public function actualizar(
        int $usuarioId,
        array $datos
    ): array {

        $registro = ZoeConversationContext::firstOrCreate(
            [
                'usuario_id' => $usuarioId,
            ],
            [
                'contexto' => [],
            ]
        );

        $contextoActual = $registro->contexto ?? [];

        $contextoNuevo = array_replace_recursive(
            $contextoActual,
            $datos
        );

        $registro->update([
            'contexto' => $contextoNuevo,
        ]);

        return $contextoNuevo;
    }

    public function limpiar(int $usuarioId): void
    {
        ZoeConversationContext::where(
            'usuario_id',
            $usuarioId
        )->delete();
    }
}