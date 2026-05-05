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
      $table->string('correlative_dyn', 20)->nullable()->after('correlative');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      $table->dropColumn('correlative_dyn');
    });
  }
};
