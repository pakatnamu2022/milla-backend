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
    Schema::create('ap_deductible_work_order', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('work_order_id');
      $table->unsignedBigInteger('electronic_document_id');
      $table->integer('created_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('work_order_id')
        ->references('id')
        ->on('ap_work_orders')
        ->onDelete('cascade');

      $table->foreign('electronic_document_id')
        ->references('id')
        ->on('ap_billing_electronic_documents')
        ->onDelete('cascade');

      $table->foreign('created_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');

      $table->index('work_order_id');
      $table->index('electronic_document_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_deductible_work_order');
  }
};
