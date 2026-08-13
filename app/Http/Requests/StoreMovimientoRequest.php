<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Services\PeriodoService;
use Carbon\Carbon;

class StoreMovimientoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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

            /*
            |--------------------------------------------------------------------------
            | Verificar configuración contable
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Validar fecha
            |--------------------------------------------------------------------------
            */

            $fecha = Carbon::parse($this->fecha);

            /*
            |--------------------------------------------------------------------------
            | Validar que la fecha no sea anterior al inicio
            |--------------------------------------------------------------------------
            */

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

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Obtener período actualmente abierto
            |--------------------------------------------------------------------------
            */

            $periodoActual = PeriodoService::obtenerPeriodoAbierto();

            if (!$periodoActual) {

                $validator->errors()->add(
                    'fecha',
                    'No existe un período contable abierto.'
                );

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | La fecha debe pertenecer al período abierto
            |--------------------------------------------------------------------------
            */

            if (
                $fecha->year != $periodoActual->anio ||
                $fecha->month != $periodoActual->mes
            ) {

                $validator->errors()->add(
                    'fecha',
                    'La fecha debe pertenecer al período actualmente abierto: '
                    . $periodoActual->nombre
                    . ' '
                    . $periodoActual->anio
                    . '.'
                );

                return;
            }

        });
    }
}