<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movimientos', function (Blueprint $table) {

            $table->id();

            // Número automático
            $table->string('numero',20)->unique();

            // Período contable
            $table->foreignId('periodo_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Fecha del movimiento
            $table->date('fecha');

            // Tipo de movimiento
            $table->enum('tipo', [
                'Ingreso',
                'Egreso'
            ]);

            // Categoría
            $table->foreignId('categoria_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Concepto
            $table->string('concepto',250);

            // Persona o proveedor
            $table->string('persona',150)
                ->nullable();

            // Forma de pago
            $table->enum('forma_pago',[
                'Efectivo',
                'Yape',
                'Plin',
                'Transferencia',
                'Depósito',
                'Otro'
            ])->default('Efectivo');

            // Importe
            $table->decimal('monto',12,2);

            // Comprobante
            $table->string('comprobante')
                ->nullable();

                        // Comprobante
            $table->string('comprobante')
                ->nullable();

            // Referencia
            $table->string('referencia',100)
                ->nullable();

            // Observaciones
            $table->text('observaciones')
                ->nullable();


            // Observaciones
            $table->text('observaciones')
                ->nullable();

            // Estado
            $table->enum('estado',[
                'Registrado',
                'Anulado'
            ])->default('Registrado');

            // Usuario que registró
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos');
    }
};