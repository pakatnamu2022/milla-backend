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
      $table->foreignId('discard_reason_id')->nullable()->after('currency_id')->constrained('ap_masters')->onDelete('set null');
      $table->integer('discarded_by')->nullable()->after('discard_reason_id');
      $table->foreign('discarded_by')->references('id')->on('usr_users')->onDelete('set null');
      $table->dateTime('discarded_at')->nullable()->after('discarded_by');
      $table->string('discarded_note')->nullable()->after('discarded_at');
      $table->string('customer_signature_url', 500)->nullable()->after('output_generation_warehouse');
      $table->enum('status', ['Aperturado', 'Descartado', 'Por Facturar', 'Facturado'])->default('Aperturado')->after('customer_signature_url');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_order_quotations', function (Blueprint $table) {
      $table->dropForeign(['discard_reason_id']);
      $table->dropColumn('discard_reason_id');
      $table->dropForeign(['discarded_by']);
      $table->dropColumn('discarded_by');
      $table->dropColumn('discarded_at');
      $table->dropColumn('discarded_note');
      $table->dropColumn('customer_signature_url');
      $table->dropColumn('status');
    });
  }
};
