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
      $table->foreignId('article_class_id')->default(3)->after('description')->constrained('ap_class_article');
      $table->boolean('is_received')->default(true)->after('status');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('warehouse', function (Blueprint $table) {
      $table->dropForeign(['article_class_id']);
      $table->dropColumn('article_class_id');
      $table->dropColumn('is_received');
    });
  }
};
