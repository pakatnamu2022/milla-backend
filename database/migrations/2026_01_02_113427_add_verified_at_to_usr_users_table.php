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
    Schema::table('usr_users', function (Blueprint $table) {
      $table->timestamp('verified_at')->nullable()->after('password');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('usr_users', function (Blueprint $table) {
      $table->dropColumn('verified_at');
    });
  }
};
