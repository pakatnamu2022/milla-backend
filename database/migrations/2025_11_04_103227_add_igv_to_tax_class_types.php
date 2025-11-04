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
      $table->decimal('igv')->after('tax_class')->default(0);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('tax_class_types', function (Blueprint $table) {
      $table->dropColumn('igv');
    });
  }
};
