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
    Schema::table('type_currency', function (Blueprint $table) {
      $table->foreignId('area_id')->default(826)->after('symbol')->constrained('ap_masters')->onDelete('restrict');
      $table->dropUnique('type_currency_codigo_unique');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('type_currency', function (Blueprint $table) {
      $table->dropForeign(['area_id']);
      $table->dropColumn('area_id');
      $table->unique('code', 'type_currency_codigo_unique');
    });
  }
};
