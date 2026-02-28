<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->dropColumn('is_take');
    });
  }

  public function down(): void
  {
    Schema::table('ap_supplier_order', function (Blueprint $table) {
      $table->boolean('is_take')->default(false)->after('exchange_rate');
    });
  }
};