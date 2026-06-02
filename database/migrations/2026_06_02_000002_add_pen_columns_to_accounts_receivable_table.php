<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('accounts_receivable', function (Blueprint $table) {
      $table->decimal('amount_pen', 15, 5)->nullable()->after('balance');
      $table->decimal('balance_pen', 15, 5)->nullable()->after('amount_pen');
      $table->index('balance_pen', 'ar_balance_pen_idx');
    });
  }

  public function down(): void
  {
    Schema::table('accounts_receivable', function (Blueprint $table) {
      $table->dropIndex('ar_balance_pen_idx');
      $table->dropColumn(['amount_pen', 'balance_pen']);
    });
  }
};
