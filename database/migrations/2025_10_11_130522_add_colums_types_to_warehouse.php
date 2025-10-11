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
    Schema::table('warehouse', function (Blueprint $table) {
      $table->string('article_class_id')->nullable()->change();
//      otro tipo EXISTENCIAS Y ALMACEN
      $table->string('article_class_id')->nullable()->change();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('warehouse', function (Blueprint $table) {
      //
    });
  }
};
