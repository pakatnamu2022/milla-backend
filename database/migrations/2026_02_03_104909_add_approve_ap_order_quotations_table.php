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
      $table->integer('chief_approval_by')->nullable()->after('status')->comment('Usuario que aprobó la cotización como jefe de taller');
      $table->foreign('chief_approval_by')->references('id')->on('usr_users');
      $table->integer('manager_approval_by')->nullable()->after('chief_approval_by')->comment('Usuario que aprobó la cotización como gerente de servicio');
      $table->foreign('manager_approval_by')->references('id')->on('usr_users');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropForeign(['chief_approval_by']);
      $table->dropColumn('chief_approval_by');
      $table->dropForeign(['manager_approval_by']);
      $table->dropColumn('manager_approval_by');
    });
  }
};
