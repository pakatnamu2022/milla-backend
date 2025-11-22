<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   *
   * IMPORTANT: Before running this migration:
   * 1. Ensure all data has been migrated to product_warehouse_stock table
   * 2. Ensure all product_warehouse_stock records have been created
   * 3. Update any pending code that still references these fields
   * 4. Run the migration that creates product_warehouse_stock first
   * 5. Run a data migration script to transfer stock data
   */
  public function up(): void
  {
    Schema::table('products', function (Blueprint $table) {
      // Drop foreign key first
      $table->dropForeign(['warehouse_id']);

      // Drop deprecated stock-related columns
      $table->dropColumn([
        'warehouse_id',          // Now handled by product_warehouse_stock
        'minimum_stock',         // Now in product_warehouse_stock per warehouse
        'maximum_stock',         // Now in product_warehouse_stock per warehouse
        'current_stock',         // Now in product_warehouse_stock as 'quantity'
      ]);
    });
  }

  /**
   * Reverse the migrations.
   *
   * This will restore the columns if you need to rollback
   */
  public function down(): void
  {
    Schema::table('products', function (Blueprint $table) {
      // Restore warehouse_id
      $table->foreignId('warehouse_id')
        ->nullable()
        ->after('unit_measurement_id')
        ->constrained('warehouse')
        ->nullOnDelete()
        ->comment('DEPRECATED - Main warehouse (use product_warehouse_stock instead)');

      // Restore stock fields
      $table->decimal('minimum_stock', 10, 2)
        ->default(0)
        ->after('ap_class_article_id')
        ->comment('DEPRECATED - Minimum stock (use product_warehouse_stock instead)');

      $table->decimal('maximum_stock', 10, 2)
        ->nullable()
        ->after('minimum_stock')
        ->comment('DEPRECATED - Maximum stock (use product_warehouse_stock instead)');

      $table->decimal('current_stock', 10, 2)
        ->default(0)
        ->after('maximum_stock')
        ->comment('DEPRECATED - Current stock (use product_warehouse_stock instead)');
    });
  }
};
