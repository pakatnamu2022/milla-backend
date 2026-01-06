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
      $table->foreignId('currency_id')->default(3)->after('sede_id')->constrained('type_currency');
      $table->decimal('exchange_rate', 15, 6)->default(1)->after('currency_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropForeign(['currency_id']);
      $table->dropColumn('currency_id');
      $table->dropColumn('exchange_rate');
    });
  }
};
