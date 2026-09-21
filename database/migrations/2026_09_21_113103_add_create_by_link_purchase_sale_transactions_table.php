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
    Schema::table('link_purchase_sale_transactions', function (Blueprint $table) {
      $table->integer('create_by')->nullable()->after('id');
      $table->foreign('create_by', 'fk_link_create_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('link_purchase_sale_transactions', function (Blueprint $table) {
      $table->dropForeign('fk_link_create_by');
      $table->dropColumn('create_by');
    });
  }
};
