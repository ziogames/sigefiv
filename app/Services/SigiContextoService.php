<?php

namespace App\Services;

class SigiContextoService
{
    /**
     * Contexto temporal de las conversaciones de SIGI.
     *
     * Por ahora utilizamos una memoria en proceso.
     * Más adelante podemos persistir este contexto en BD,
     * Redis o integrarlo con la memoria permanente de SIGI.
     */
    private array $contextos = [];


    /**
     * Obtener el contexto de un usuario.
     */
    public function obtener(?int $usuarioId): array
    {
        if (!$usuarioId) {
            return [];
        }

        return $this->contextos[$usuarioId] ?? [];
    }


    /**
     * Guardar el contexto de un usuario.
     */
    public function guardar(
        ?int $usuarioId,
        array $contexto
    ): void {

        if (!$usuarioId) {
            return;
        }

        $this->contextos[$usuarioId] =
            $contexto;
    }


    /**
     * Actualizar solamente algunos valores
     * del contexto existente.
     */
    public function actualizar(
        ?int $usuarioId,
        array $datos
    ): array {

        if (!$usuarioId) {
            return $datos;
        }

        $actual =
            $this->obtener($usuarioId);

        $nuevo =
            array_replace_recursive(
                $actual,
                $datos
            );

        $this->guardar(
            $usuarioId,
            $nuevo
        );

        return $nuevo;
    }


    /**
     * Eliminar el contexto conversacional.
     */
    public function limpiar(
        ?int $usuarioId
    ): void {

        if (!$usuarioId) {
            return;
        }

        unset(
            $this->contextos[$usuarioId]
        );
    }


    /**
     * Comprobar si existe contexto.
     */
    public function tiene(
        ?int $usuarioId
    ): bool {

        if (!$usuarioId) {
            return false;
        }

        return !empty(
            $this->contextos[$usuarioId] ?? []
        );
    }
}