<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('mkt_supports', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('activity_id')->nullable();
      $table->unsignedBigInteger('purchase_order_id')->nullable();
      $table->enum('type', ['receipt', 'invoice', 'photo', 'report', 'other'])->default('receipt');
      $table->string('document_series', 10)->nullable();
      $table->string('document_number', 20)->nullable();
      $table->date('issue_date')->nullable();
      $table->unsignedBigInteger('supplier_id')->nullable();
      $table->unsignedBigInteger('currency_id')->nullable();
      $table->decimal('amount', 14, 2)->nullable();
      $table->string('file_path', 500)->nullable();
      $table->text('notes')->nullable();
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('activity_id')->references('id')->on('mkt_activities')->nullOnDelete();
      $table->foreign('purchase_order_id')->references('id')->on('mkt_purchase_orders')->nullOnDelete();
      $table->foreign('supplier_id')->references('id')->on('business_partners')->nullOnDelete();
      $table->foreign('currency_id')->references('id')->on('type_currency')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('mkt_supports');
  }
};
