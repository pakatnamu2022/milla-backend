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
      $table->text('descripcion')->change()->comment('Descripción del ítem en el documento electrónico de facturación');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->string('descripcion', 255)->change()->comment('Descripción del ítem en el documento electrónico de facturación');
    });
  }
};
