<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * La OC pasa a poder jalar directamente del Plan (su concepto/descripción)
   * en vez de depender únicamente de una Actividad o Propuesta.
   */
  public function up(): void
  {
    Schema::table('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->unsignedBigInteger('plan_id')->nullable()->after('id');
      $table->foreign('plan_id')->references('id')->on('ap_mkt_plans')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('ap_mkt_purchase_orders', function (Blueprint $table) {
      $table->dropForeign(['plan_id']);
      $table->dropColumn('plan_id');
    });
  }
};
