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
    Schema::table('ap_vehicles', function (Blueprint $table) {
      $table->string('plate', 20)->nullable()->after('id')->comment('Placa del vehículo');
      $table->foreignId('customer_id')->nullable()->after('warehouse_physical_id')->constrained('business_partners')->comment('ID del cliente asociado al vehículo');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicles', function (Blueprint $table) {
      $table->dropColumn('plate');
      $table->dropForeign(['customer_id']);
      $table->dropColumn('customer_id');
    });
  }
};
