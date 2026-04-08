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
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->string('num_doc_contact', 20)->nullable()->after('sede_id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_work_orders', function (Blueprint $table) {
      $table->dropColumn('num_doc_contact');
    });
  }
};
