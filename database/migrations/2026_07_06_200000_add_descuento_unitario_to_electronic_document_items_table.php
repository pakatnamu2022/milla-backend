<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->decimal('descuento_unitario', 15, 2)->default(0)->after('descuento');
    });

    // Backfill: calcular descuento_unitario para registros existentes con descuento > 0
    DB::statement('
      UPDATE ap_billing_electronic_document_items
      SET descuento_unitario = ROUND(descuento / NULLIF(cantidad, 0), 2)
      WHERE descuento > 0 AND deleted_at IS NULL
    ');
  }

  public function down(): void
  {
    Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
      $table->dropColumn('descuento_unitario');
    });
  }
};
