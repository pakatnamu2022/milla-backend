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
    Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->boolean('has_isc')->default(false)->after('discount');
      $table->decimal('isc')->default(0)->after('has_isc');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->dropColumn(['has_isc', 'isc']);
    });
  }
};
