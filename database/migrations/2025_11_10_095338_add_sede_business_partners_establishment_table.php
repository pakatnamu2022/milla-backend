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
    Schema::table('business_partners_establishment', function (Blueprint $table) {
      $table->integer('sede_id')->after('business_partner_id')->nullable();
      $table->foreign('sede_id')->references('id')->on('config_sede');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('business_partners_establishment', function (Blueprint $table) {
      $table->dropForeign(['sede_id']);
      $table->dropColumn('sede_id');
    });
  }
};
