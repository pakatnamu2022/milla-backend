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
    Schema::table('config_sede', function (Blueprint $table) {
      $table->unsignedBigInteger('shop_id')->nullable()->after('id');
      $table->foreign('shop_id')->references('id')->on('ap_masters')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('config_sede', function (Blueprint $table) {
      $table->dropForeign(['shop_id']);
      $table->dropColumn('shop_id');
    });
  }
};
