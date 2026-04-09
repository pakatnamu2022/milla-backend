<?php

use App\Models\ap\postventa\taller\ApOrderQuotations;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('ap_order_purchase_request_details', function (Blueprint $table) {
      $table->string('supply_type')->after('requested_delivery_date')->default('CENTRAL')->comment('Tipo de suministro: CENTRAL, LOCAL, IMPORTACIÓN');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_purchase_request_details', function (Blueprint $table) {
      $table->dropColumn('supply_type');
    });
  }
};
