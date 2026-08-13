<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    protected $fillable = [

        'anio',

        'mes',

        'nombre',

        'saldo_inicial',

        'total_ingresos',

        'total_egresos',

        'saldo_final',

        'estado',

    ];

    protected $casts = [

        'saldo_inicial'  => 'decimal:2',

        'total_ingresos' => 'decimal:2',

        'total_egresos'  => 'decimal:2',

        'saldo_final'    => 'decimal:2',

    ];

    /**
     * Relación con los movimientos
     */
    public function movimientos()
    {
        return $this->hasMany(
            Movimiento::class
        );
    }

    /**
     * Devuelve el nombre del período.
     * Ejemplo: Agosto 2026
     */
    public function getNombreCompletoAttribute(): string
    {
        return self::nombreMes($this->mes) . ' ' . $this->anio;
    }

    /**
     * Nombre del mes.
     */
    public static function nombreMes(int $mes): string
    {
        return [

            1  => 'Enero',

            2  => 'Febrero',

            3  => 'Marzo',

            4  => 'Abril',

            5  => 'Mayo',

            6  => 'Junio',

            7  => 'Julio',

            8  => 'Agosto',

            9  => 'Septiembre',

            10 => 'Octubre',

            11 => 'Noviembre',

            12 => 'Diciembre',

        ][$mes];
    }

    /**
     * Saber si el período está abierto.
     */
    public function estaAbierto(): bool
    {
        return $this->estado === 'Abierto';
    }

    /**
     * Saber si el período está cerrado.
     */
    public function estaCerrado(): bool
    {
        return $this->estado === 'Cerrado';
    }
    /**
 * Devuelve el período abierto.
 */
public static function obtenerAbierto(): ?self
{
    return self::where('estado', 'Abierto')
        ->orderByDesc('anio')
        ->orderByDesc('mes')
        ->first();
}
}