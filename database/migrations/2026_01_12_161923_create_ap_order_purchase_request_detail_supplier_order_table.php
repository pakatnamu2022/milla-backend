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
    Schema::create('ap_order_purchase_request_detail_supplier_order', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_order_purchase_request_detail_id')->constrained('ap_order_purchase_request_details', 'id', 'fk_order_pur_req_detail')->onDelete('cascade');
      $table->foreignId('ap_supplier_order_id')->constrained('ap_supplier_order', 'id', 'fk_ap_supplier_order')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();

      // Index para búsquedas rápidas
      $table->index('ap_order_purchase_request_detail_id', 'idx_opr_detail');
      $table->index('ap_supplier_order_id', 'idx_supplier_order');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_order_purchase_request_detail_supplier_order');
  }
};
