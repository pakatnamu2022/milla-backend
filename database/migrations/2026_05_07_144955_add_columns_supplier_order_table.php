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
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->integer('discarded_by')->nullable()->after('created_by');
      $table->foreign('discarded_by')->references('id')->on('usr_users')->onDelete('set null');
      $table->string('reason_cancellation')->nullable()->after('status');
      $table->dateTime('discarded_at')->nullable()->after('reason_cancellation');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->dropForeign(['discarded_by']);
      $table->dropColumn('discarded_by');
      $table->dropColumn('reason_cancellation');
      $table->dropColumn('discarded_at');
    });
  }
};
