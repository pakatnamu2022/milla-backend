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
    Schema::create('ap_deductible_order_quotation', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('order_quotation_id');
      $table->unsignedBigInteger('electronic_document_id');
      $table->unsignedBigInteger('order_quotation_detail_id');
      $table->integer('created_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('order_quotation_id')
        ->references('id')
        ->on('ap_order_quotations')
        ->onDelete('cascade');

      $table->foreign('electronic_document_id')
        ->references('id')
        ->on('ap_billing_electronic_documents')
        ->onDelete('cascade');

      $table->foreign('order_quotation_detail_id')
        ->references('id')
        ->on('ap_order_quotation_details')
        ->onDelete('cascade');

      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->index('order_quotation_id');
      $table->index('order_quotation_detail_id');
      $table->index('electronic_document_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_deductible_order_quotation');
  }
};
