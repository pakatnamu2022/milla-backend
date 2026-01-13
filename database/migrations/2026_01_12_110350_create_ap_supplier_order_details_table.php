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
    Schema::create('ap_supplier_order_details', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_supplier_order_id')->constrained('ap_supplier_order')->onDelete('cascade');
      $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
      $table->foreignId('unit_measurement_id')->constrained('unit_measurement')->onDelete('cascade');
      $table->string('note')->nullable();
      $table->decimal('unit_price', 15, 4);
      $table->decimal('quantity', 15, 4);
      $table->decimal('total', 15, 4);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_supplier_order_details');
  }
};
