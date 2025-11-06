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
      //agregamos el campo article_class
      $table->foreignId('ap_class_article_id')->after('series')->nullable()
        ->constrained('ap_class_article')
        ->onUpdate('cascade')
        ->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('shipping_guides', function (Blueprint $table) {
      //
      $table->dropForeign(['ap_class_article_id']);
      $table->dropColumn('ap_class_article_id');
    });
  }
};
