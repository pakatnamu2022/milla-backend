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
    Schema::table('business_partners', function (Blueprint $table) {
      $table->unsignedBigInteger('origin_id')->nullable()->change();
      $table->unsignedBigInteger('activity_economic_id')->nullable()->change();
      $table->unsignedBigInteger('activity_economic_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('business_partners', function (Blueprint $table) {
      $table->unsignedBigInteger('origin_id')->nullable(false)->change();
      $table->unsignedBigInteger('activity_economic_id')->nullable(false)->change();
      $table->unsignedBigInteger('activity_economic_id')->nullable(false)->change();
    });
  }
};
