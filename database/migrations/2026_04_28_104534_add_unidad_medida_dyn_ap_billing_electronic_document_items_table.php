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
      $table->string('unidad_medida_dyn', 3)->default('UND')->after('unidad_de_medida');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->dropColumn('unidad_medida_dyn');
    });
  }
};
