<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_supports', function (Blueprint $table) {
      $table->id();
      $table->enum('type', ['receipt', 'invoice', 'photo', 'report', 'other'])->default('receipt');
      $table->string('document_series', 10)->nullable();
      $table->string('document_number', 20)->nullable();
      $table->date('issue_date')->nullable();
      $table->decimal('amount', 14, 2)->nullable();
      $table->string('file_path', 500)->nullable();
      $table->text('notes')->nullable();
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreignId('activity_id')->constrained('ap_mkt_activities');
      $table->foreignId('purchase_order_id')->constrained('ap_mkt_purchase_orders');
      $table->foreignId('supplier_id')->nullable()->constrained('business_partners');
      $table->foreignId('currency_id')->nullable()->constrained('type_currency');
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('usr_users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_supports');
  }
};
