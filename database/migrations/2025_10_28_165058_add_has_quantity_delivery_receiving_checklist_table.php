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
    Schema::table('ap_delivery_receiving_checklist', function (Blueprint $table) {
      $table->boolean('has_quantity')->default(false)->after('category_id')->comment('Indica si el item requiere cantidad');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_delivery_receiving_checklist', function (Blueprint $table) {
      $table->dropColumn('has_quantity');
    });
  }
};
