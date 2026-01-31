<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('phone_line_worker', function (Blueprint $table) {
      $table->timestamp('unassigned_at')->nullable()->after('active');
    });
  }

  public function down(): void
  {
    Schema::table('phone_line_worker', function (Blueprint $table) {
      $table->dropColumn('unassigned_at');
    });
  }
};
