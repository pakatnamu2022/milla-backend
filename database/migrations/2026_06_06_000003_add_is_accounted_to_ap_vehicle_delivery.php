<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->boolean('is_accounted')->default(false)->after('status_delivery');
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->dropColumn('is_accounted');
    });
  }
};
