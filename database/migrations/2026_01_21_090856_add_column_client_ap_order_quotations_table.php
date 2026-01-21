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
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->foreignId('client_id')->nullable()->after('vehicle_id')->constrained('business_partners')->onDelete('set null');
      $table->unsignedBigInteger('vehicle_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropForeign(['client_id']);
      $table->dropColumn('client_id');
      $table->unsignedBigInteger('vehicle_id')->nullable(false)->change();
    });
  }
};
