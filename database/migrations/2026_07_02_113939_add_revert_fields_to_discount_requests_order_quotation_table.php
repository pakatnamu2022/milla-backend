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
    Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
      $table->integer('reverted_by_id')->nullable()->after('reviewed_by_id');
      $table->timestamp('reverted_at')->nullable()->after('review_date');
      $table->text('reverted_reason')->nullable()->after('reverted_at');

      $table->foreign('reverted_by_id')->references('id')->on('usr_users')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
      $table->dropForeign(['reverted_by_id']);
      $table->dropColumn(['reverted_by_id', 'reverted_at', 'reverted_reason']);
    });
  }
};
