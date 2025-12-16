<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('purchase_receptions', function (Blueprint $table) {
      // Costo del flete
      $table->decimal('freight_cost', 12, 2)->after('shipping_guide_number')
        ->comment('Costo del flete de la recepción');

      // ID del transportista (business partner)
      $table->foreignId('carrier_id')->comment('ID del transportista (business partner)')
        ->after('total_quantity')
        ->constrained('business_partners')->onDelete('restrict');

      // ID tipo de moneda para el costo del flete (opcional)
      $table->foreignId('currency_id')->default('3')->comment('ID por defecto pertenece al SOL PERUANO')
        ->after('carrier_id')
        ->constrained('type_currency')->onDelete('restrict');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('purchase_receptions', function (Blueprint $table) {
      // Eliminar foreign key primero
      $table->dropForeign(['carrier_id']);
      $table->dropForeign(['currency_id']);

      // Eliminar columnas
      $table->dropColumn(['freight_cost', 'carrier_id', 'currency_id']);
    });
  }
};
