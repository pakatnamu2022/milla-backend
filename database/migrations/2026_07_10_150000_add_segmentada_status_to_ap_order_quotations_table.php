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
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->enum('status', ['Aperturado', 'Descartado', 'Por Facturar', 'Facturado', 'Segmentada'])->default('Aperturado')->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->enum('status', ['Aperturado', 'Descartado', 'Por Facturar', 'Facturado'])->default('Aperturado')->change();
    });
  }
};
