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
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      $table->string('photo_front_url')->nullable()->after('customer_signature_url');
      $table->string('photo_back_url')->nullable()->after('photo_front_url');
      $table->string('photo_left_url')->nullable()->after('photo_back_url');
      $table->string('photo_right_url')->nullable()->after('photo_left_url');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_vehicle_inspection', function (Blueprint $table) {
      //
    });
  }
};
