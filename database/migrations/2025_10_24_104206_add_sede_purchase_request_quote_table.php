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
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->integer('sede_id')->nullable()->after('warranty');
      $table->foreign('sede_id')->references('id')->on('config_sede');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('purchase_request_quote', function (Blueprint $table) {
      $table->dropForeign(['sede_id']);
      $table->dropColumn('sede_id');
    });
  }
};
