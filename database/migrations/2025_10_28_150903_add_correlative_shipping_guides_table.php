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
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->string('series', 20)->after('document_series_id')->nullable();
      $table->string('correlative', 50)->after('document_number')->nullable();
      $table->boolean('status_nubefac')->after('correlative')->default(false);
      $table->foreignId('document_series_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->dropColumn('series');
      $table->dropColumn('correlative');
      $table->dropColumn('status_nubefac');
      $table->foreignId('document_series_id')->change();
    });
  }
};
