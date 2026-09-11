<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Agrega campos para rastrear el costo original, moneda y tipo de cambio
     * Esto permite trazabilidad completa sin consultar inventory_movement
     */
    public function up(): void
    {
        Schema::table('weighted_average_cost_history', function (Blueprint $table) {
            // Costo unitario en moneda original (ej: 8.16 USD)
            $table->decimal('unit_cost_original', 10, 2)->default(0)->after('unit_cost_pen');

            // ID de la moneda original (referencia a type_currency)
            $table->unsignedBigInteger('currency_id')->nullable()->after('unit_cost_original');

            // Tipo de cambio usado en la conversión (ej: 3.403)
            // Si es PEN, será 1.00
            $table->decimal('exchange_rate', 10, 4)->default(1.0000)->after('currency_id');

            // Foreign key constraint
            $table->foreign('currency_id')
                ->references('id')
                ->on('type_currency')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weighted_average_cost_history', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['currency_id']);

            // Drop columns
            $table->dropColumn(['unit_cost_original', 'currency_id', 'exchange_rate']);
        });
    }
};
