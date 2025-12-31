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
      $table->decimal('retail_price_external', 15, 4)->nullable()->after('purchase_price')->comment('Precio de venta al public Dealer Portal');
      $table->decimal('flete_external', 15, 4)->nullable()->after('retail_price_external')->comment('Costo de flete Dealer Portal');
      $table->decimal('percentage_flete_external', 5, 2)->nullable()->after('flete_external')->comment('Porcentaje para multiplicar el flete en Dealer Portal');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotation_details', function (Blueprint $table) {
      $table->dropColumn('retail_price_external');
      $table->dropColumn('flete_external');
      $table->dropColumn('percentage_flete_external');
    });
  }
};
