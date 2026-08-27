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
    Schema::table('attendance_sync', function (Blueprint $table) {
      $table->enum('record_type', ['manual', 'automatic'])->after('mark_type')->default('automatic');
      $table->integer('created_by')->nullable()->after('person_id');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('set null');
      $table->integer('zkbio_transaction_id')->nullable()->change();
      $table->dropUnique('attendance_sync_zkbio_transaction_id_unique');
      $table->integer('sede_id')->nullable()->after('created_by');
      $table->foreign('sede_id')->references('id')->on('config_sede');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('attendance_sync', function (Blueprint $table) {
      $table->integer('zkbio_transaction_id')->nullable(false)->change();
      $table->dropColumn('record_type');
      $table->dropForeign('attendance_sync_created_by_foreign');
      $table->dropColumn('created_by');
      $table->unique('zkbio_transaction_id');
    });
  }
};
