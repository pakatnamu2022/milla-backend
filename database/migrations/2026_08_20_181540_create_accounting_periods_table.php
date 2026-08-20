<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla accounting_periods para gestionar períodos contables cerrados.
     *
     * Un período contable representa un mes calendario completo que puede cerrarse
     * para evitar modificaciones retroactivas en movimientos de inventario y
     * garantizar integridad contable.
     *
     * IMPORTANTE: Los períodos SIEMPRE representan un mes calendario completo
     * (start_date = primer día del mes, end_date = último día del mes).
     */
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();

            // Identificación del período
            $table->string('name', 100)->comment('Nombre del período (ej: "Enero 2026")');

            // Rango de fechas (SIEMPRE un mes calendario completo)
            $table->date('start_date')->comment('Fecha de inicio (siempre día 1 del mes)');
            $table->date('end_date')->comment('Fecha de fin (siempre último día del mes)');

            // Estado del período
            $table->boolean('is_closed')->default(false)->comment('Indica si el período está cerrado');
            $table->timestamp('closed_at')->nullable()->comment('Fecha y hora de cierre del período');
            $table->integer('closed_by')->nullable()->comment('Usuario que cerró el período');

            // Timestamps
            $table->timestamps();

            // Índices
            $table->index(['start_date', 'end_date'], 'idx_date_range');
            $table->index('is_closed', 'idx_is_closed');

            // Constraint: no períodos solapados (mismo rango de fechas)
            $table->unique(['start_date', 'end_date'], 'unique_period');

            // Foreign key
            $table->foreign('closed_by')
                ->references('id')
                ->on('usr_users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};