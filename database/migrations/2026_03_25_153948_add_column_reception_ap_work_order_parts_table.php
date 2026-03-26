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
    Schema::table('ap_work_order_parts', function (Blueprint $table) {
      $table->boolean('is_received')->default(false)->after('registered_by');
      $table->dateTime('received_date')->nullable()->after('is_received');
      $table->string('received_signature_url')->nullable()->after('received_date');
      $table->integer('received_by')->nullable()->after('received_signature_url');
      $table->foreign('received_by')->references('id')->on('usr_users');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_order_parts', function (Blueprint $table) {
      $table->dropForeign(['received_by']);
      $table->dropColumn('is_received');
      $table->dropColumn('received_date');
      $table->dropColumn('received_signature_url');
      $table->dropForeign(['received_by']);
      $table->dropColumn('received_by');
    });
  }
};
