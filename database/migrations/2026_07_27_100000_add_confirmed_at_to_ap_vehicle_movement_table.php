<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->timestamp('confirmed_at')->nullable()->after('movement_date');
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_movement', function (Blueprint $table) {
      $table->dropColumn('confirmed_at');
    });
  }
};
