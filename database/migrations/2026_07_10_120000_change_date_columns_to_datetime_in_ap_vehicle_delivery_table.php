<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->dateTime('scheduled_delivery_date')->nullable()->change();
      $table->dateTime('wash_date')->nullable()->change();
      $table->dateTime('real_delivery_date')->nullable()->change();
      $table->dateTime('real_wash_date')->nullable()->change();
    });
  }

  public function down(): void
  {
    Schema::table('ap_vehicle_delivery', function (Blueprint $table) {
      $table->date('scheduled_delivery_date')->nullable()->change();
      $table->date('wash_date')->nullable()->change();
      $table->date('real_delivery_date')->nullable()->change();
      $table->date('real_wash_date')->nullable()->change();
    });
  }
};
