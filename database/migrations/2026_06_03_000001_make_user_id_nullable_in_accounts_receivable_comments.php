<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('accounts_receivable_comments', function (Blueprint $table) {
      $table->integer('user_id')->nullable()->change();
    });
  }

  public function down(): void
  {
    Schema::table('accounts_receivable_comments', function (Blueprint $table) {
      $table->integer('user_id')->nullable(false)->change();
    });
  }
};
