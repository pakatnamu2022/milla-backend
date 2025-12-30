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
    Schema::create('ap_order_purchase_request_detail_purchase_order_item', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_order_purchase_request_detail_id')->constrained('ap_order_purchase_request_details')->onDelete('cascade');
      $table->foreignId('purchase_order_id')->constrained('ap_purchase_order')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();

      // Index para búsquedas rápidas
      $table->index('ap_order_purchase_request_detail_id', 'idx_request_detail');
      $table->index('purchase_order_item_id', 'idx_purchase_order_item');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_order_purchase_request_detail_purchase_order_item');
  }
};
