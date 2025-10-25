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
    Schema::create('ap_receiving_checklist', function (Blueprint $table) {
      $table->id();
      $table->foreignId('receiving_id')->constrained('ap_delivery_receiving_checklist')->onDelete('cascade');
      $table->foreignId('shipping_guide_id')->constrained('shipping_guides')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_receiving_checklist');
  }
};
