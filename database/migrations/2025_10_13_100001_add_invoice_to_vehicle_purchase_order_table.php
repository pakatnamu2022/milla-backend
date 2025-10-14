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
    Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->string('invoice_dynamics')
        ->nullable()
        ->after('migration_status')
        ->comment('Número de factura en el sistema Dynamics');

      $table->string('receipt_dynamics')
        ->nullable()
        ->after('migration_status')
        ->comment('Número de recibo en el sistema Dynamics');

      $table->string('credit_note_dynamics')
        ->nullable()
        ->after('migration_status')
        ->comment('Número de nota de crédito en el sistema Dynamics');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_purchase_order', function (Blueprint $table) {
      $table->dropColumn(['invoice_dynamics', 'receipt_dynamics', 'credit_note_dynamics']);
    });
  }
};
