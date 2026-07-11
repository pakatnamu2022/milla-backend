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
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->decimal('descuento_unitario', 15, 3)->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->decimal('descuento_unitario', 15, 2)->change();
    });
  }
};
