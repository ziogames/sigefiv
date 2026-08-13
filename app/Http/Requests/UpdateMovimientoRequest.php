<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovimientoRequest extends FormRequest
{
    /**
     * Autorizar la petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [

            'fecha' => 'required|date',

            'categoria_id' => 'required|exists:categorias,id',

            'concepto' => 'required|max:250',

            'persona' => 'nullable|max:150',

            'forma_pago' => 'required|in:Efectivo,Yape,Plin,Transferencia,Depósito,Otro',

            'monto' => 'required|numeric|min:0.01',

            'comprobante' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:4096',

            'referencia' => 'nullable|max:100',

            'observaciones' => 'nullable|max:1000',

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $config = \App\Models\Configuracion::first();

            if (
                !$config ||
                !$config->contabilidad_iniciada
            ) {

                $validator->errors()->add(
                    'fecha',
                    'Primero debe inicializar la contabilidad.'
                );

                return;
            }

            $fecha = \Carbon\Carbon::parse($this->fecha);

            if (

                $fecha->year < $config->anio_inicio ||

                (

                    $fecha->year == $config->anio_inicio &&

                    $fecha->month < $config->mes_inicio

                )

            ) {

                $validator->errors()->add(

                    'fecha',

                    'No puede registrar movimientos antes del período inicial.'

                );

            }

        });
    }
}