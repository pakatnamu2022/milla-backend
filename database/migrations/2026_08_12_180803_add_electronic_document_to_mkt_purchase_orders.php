<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->unsignedBigInteger('electronic_document_id')->nullable()->after('billed_at');
      $table->foreign('electronic_document_id')
        ->references('id')
        ->on('ap_billing_electronic_documents')
        ->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->dropForeign(['electronic_document_id']);
      $table->dropColumn('electronic_document_id');
    });
  }
};
