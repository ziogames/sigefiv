<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $fillable = [

        'numero',

        'periodo_id',

        'fecha',

        'tipo',

        'categoria_id',

        'concepto',

        'persona',

        'forma_pago',

        'monto',

        'comprobante',

        'referencia',

        'observaciones',

        'estado',

        'user_id',

    ];

    protected $casts = [

        'fecha' => 'date',

        'monto' => 'decimal:2',

    ];

    /**
     * Relación con el período contable.
     */
    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    /**
     * Relación con la categoría.
     */
    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    /**
     * Usuario que registró el movimiento.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Genera el siguiente número de movimiento.
     */
    public static function siguienteNumero(): string
    {
        $ultimo = self::orderByDesc('id')->first();

        if (!$ultimo) {

            return 'MOV000001';

        }

        $numero = (int) substr($ultimo->numero, 3);

        return 'MOV' . str_pad(

            $numero + 1,

            6,

            '0',

            STR_PAD_LEFT

        );
    }
}