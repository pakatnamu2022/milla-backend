<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->string('extraordinary_reason')->nullable()->after('is_extraordinary');
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->dropColumn('extraordinary_reason');
    });
  }
};
