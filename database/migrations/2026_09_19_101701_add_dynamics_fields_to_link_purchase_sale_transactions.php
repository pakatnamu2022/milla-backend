<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('link_purchase_sale_transactions', function (Blueprint $table) {
      $table->string('dyn_transaction_id', 50)
        ->nullable()
        ->after('status')
        ->comment('TransaccionId enviado a Dynamics (ejemplo: TRV-10)');

      $table->integer('dyn_asiento_number')
        ->nullable()
        ->after('dyn_transaction_id')
        ->comment('Número de asiento contable en Dynamics');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('link_purchase_sale_transactions', function (Blueprint $table) {
      $table->dropColumn(['dyn_transaction_id', 'dyn_asiento_number']);
    });
  }
};