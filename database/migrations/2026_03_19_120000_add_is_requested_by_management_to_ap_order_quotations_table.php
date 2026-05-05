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
      $table->boolean('is_requested_by_management')
        ->default(false)
        ->after('is_take')
        ->comment('Indica si la cotización fue solicitada por gerencia (true) o es normal (false)');

      $table->unsignedInteger('emails_sent_count')
        ->default(0)
        ->after('is_requested_by_management')
        ->comment('Cantidad de correos enviados para esta cotización');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropColumn(['is_requested_by_management', 'emails_sent_count']);
    });
  }
};

