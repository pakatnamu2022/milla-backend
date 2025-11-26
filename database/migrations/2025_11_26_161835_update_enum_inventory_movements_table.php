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
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->enum('status', ['DRAFT', 'APPROVED', 'IN_TRANSIT', 'CANCELLED'])->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('inventory_movements', function (Blueprint $table) {
      $table->enum('status', ['DRAFT', 'APPROVED', 'CANCELLED'])->change();
    });
  }
};
