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
    Schema::table('product_warehouse_stock', function (Blueprint $table) {
      $table->decimal('cost_price', 10)->nullable()->after('maximum_stock')->comment('Costo de la ultima compra');
      $table->decimal('sale_price', 10)->nullable()->after('cost_price')->comment('Precio de venta publico sugerido');
      $table->decimal('tax_rate', 5)->nullable()->after('sale_price')->comment('Tasa de impuesto aplicable al producto');
      $table->boolean('is_taxable')->default(true)->after('tax_rate')->comment('Indica si el producto es gravado o no');
      $table->enum('status', ['ACTIVE', 'INACTIVE', 'DISCONTINUED'])->default('ACTIVE')->after('last_movement_date')->comment('Estado del stock en el almacén');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('product_warehouse_stock', function (Blueprint $table) {
      $table->dropColumn('cost_price');
      $table->dropColumn('sale_price');
      $table->dropColumn('tax_rate');
      $table->dropColumn('is_taxable');
      $table->dropColumn('status');
    });
  }
};
