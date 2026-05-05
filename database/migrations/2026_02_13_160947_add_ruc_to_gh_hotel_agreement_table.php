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
    Schema::table('gh_hotel_agreement', function (Blueprint $table) {
      $table->string('ruc', 11)->nullable()->after('name');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_hotel_agreement', function (Blueprint $table) {
      $table->dropColumn('ruc');
    });
  }
};
