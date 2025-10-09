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
    Schema::create('discount_coupons', function (Blueprint $table) {
      $table->id();
      $table->string('description', 100);
      $table->decimal('percentage', 5, 4);
      $table->decimal('amount', 12, 4);
      $table->foreignId('concept_code_id')
        ->constrained('ap_commercial_masters')->onDelete('cascade');
      $table->foreignId('purchase_request_quote_id')
        ->constrained('purchase_request_quote')->onDelete('cascade');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('discount_coupons');
  }
};
