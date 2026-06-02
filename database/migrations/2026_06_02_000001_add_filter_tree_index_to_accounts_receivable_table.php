<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('accounts_receivable', function (Blueprint $table) {
      $table->index(['company', 'sede_id', 'overdue_status', 'due_year'], 'ar_filter_tree_idx');
    });
  }

  public function down(): void
  {
    Schema::table('accounts_receivable', function (Blueprint $table) {
      $table->dropIndex('ar_filter_tree_idx');
    });
  }
};
