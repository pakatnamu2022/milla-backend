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
      $table->dropColumn('derco_store_code');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('config_sede', function (Blueprint $table) {
      $table->string('derco_store_code')->nullable()->after('abreviatura');
    });
  }
};
