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
    Schema::table('warehouse', function (Blueprint $table) {
      $table->string('inventory_account', 20)->after('description')->nullable();
      $table->string('counterparty_account', 20)->after('inventory_account')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('warehouse', function (Blueprint $table) {
      $table->dropColumn('inventory_account');
      $table->dropColumn('counterparty_account');
    });
  }
};
