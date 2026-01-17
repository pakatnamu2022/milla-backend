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
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->foreignId('currency_id')->nullable()->after('vehicle_id')->default(3)->constrained('type_currency')->onUpdate('cascade')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropForeign(['currency_id']);
      $table->dropColumn('currency_id');
    });
  }
};
