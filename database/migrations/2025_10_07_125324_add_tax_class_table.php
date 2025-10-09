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
    Schema::table('tax_class_types', function (Blueprint $table) {
      $table->string('tax_class')->after('description')->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('tax_class_types', function (Blueprint $table) {
      $table->dropColumn('tax_class');
    });
  }
};
