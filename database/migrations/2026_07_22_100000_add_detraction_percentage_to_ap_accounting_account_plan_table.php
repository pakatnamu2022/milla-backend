<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->unsignedTinyInteger('detraction_percentage')->nullable()->after('is_detraction');
    });
  }

  public function down(): void
  {
    Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
      $table->dropColumn('detraction_percentage');
    });
  }
};
