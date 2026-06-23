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
    Schema::table('purchase_reception_details', function (Blueprint $table) {
      $table->boolean('is_credit_note')->default(false)->after('observed_quantity');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('purchase_reception_details', function (Blueprint $table) {
      $table->dropColumn('is_credit_note');
    });
  }
};
