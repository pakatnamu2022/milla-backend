<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->dropForeign(['accounting_type_id']);
      $table->dropColumn('accounting_type_id');
      $table->boolean('is_detraction')->default(false)->after('description');
    });
  }

  public function down(): void
  {
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->dropColumn('is_detraction');
      $table->foreignId('accounting_type_id')->constrained('ap_masters');
    });
  }
};
