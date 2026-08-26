<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('purchase_request_quote_adjustment_items', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('adjustment_request_id');
      $table->string('action'); // create | update | delete
      $table->unsignedBigInteger('discount_coupon_id')->nullable();
      $table->unsignedBigInteger('concept_code_id')->nullable();
      $table->string('type')->nullable(); // FIJO | PORCENTAJE
      $table->boolean('is_negative')->default(false);
      $table->boolean('has_retention')->default(false);
      $table->decimal('previous_valor_unitario', 14, 4)->nullable();
      $table->decimal('new_valor_unitario', 14, 4)->nullable();
      $table->decimal('previous_precio_unitario', 14, 4)->nullable();
      $table->decimal('new_precio_unitario', 14, 4)->nullable();
      $table->timestamps();

      $table->foreign('adjustment_request_id', 'prqai_request_fk')
        ->references('id')->on('purchase_request_quote_adjustment_requests')->cascadeOnDelete();
      $table->foreign('discount_coupon_id', 'prqai_discount_coupon_fk')
        ->references('id')->on('discount_coupons')->nullOnDelete();
      $table->foreign('concept_code_id', 'prqai_concept_code_fk')
        ->references('id')->on('ap_masters');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('purchase_request_quote_adjustment_items');
  }
};
