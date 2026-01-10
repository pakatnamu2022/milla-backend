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
    Schema::table('ap_class_article', function (Blueprint $table) {
      $table->dropColumn('type');
      $table->foreignId('type_operation_id')->nullable()->after('account')
        ->constrained('ap_masters');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('ap_class_article', function (Blueprint $table) {
      $table->string('type', 50)->after('account');
      $table->dropForeign(['type_operation_id']);
      $table->dropColumn('type_operation_id');
    });
  }
};
