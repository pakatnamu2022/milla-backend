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
    Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
      $table->enum('item_type', ['product', 'labor', 'material'])
        ->default('product')
        ->comment('Tipo: product=repuesto, labor=mano de obra, material=materiales')
        ->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
      $table->enum('item_type', ['PRODUCT', 'LABOR'])
        ->default('PRODUCT')
        ->comment('Tipo: PRODUCT=repuesto, LABOR=mano de obra')
        ->change();
    });
  }
};
