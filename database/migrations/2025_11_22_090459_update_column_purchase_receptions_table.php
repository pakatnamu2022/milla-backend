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
    Schema::table('purchase_receptions', function (Blueprint $table) {
      $table->enum('status', ['APPROVED', 'PARTIAL', 'INCOMPLETE'])
        ->default('APPROVED')
        ->comment('Reception status: APPROVED, PARTIAL, INCOMPLETE')
        ->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('purchase_receptions', function (Blueprint $table) {
      $table->enum('status', ['PENDING_REVIEW', 'APPROVED', 'REJECTED', 'PARTIAL'])
        ->default('PENDING_REVIEW')
        ->comment('Reception status: PENDING_REVIEW, APPROVED, REJECTED, PARTIAL')
        ->change();
    });
  }
};
