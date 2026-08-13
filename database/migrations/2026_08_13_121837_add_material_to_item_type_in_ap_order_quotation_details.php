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
    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
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
    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->enum('item_type', ['PRODUCT', 'LABOR'])
        ->default('PRODUCT')
        ->comment('Tipo: PRODUCT=repuesto, LABOR=mano de obra')
        ->change();
    });
  }
};
