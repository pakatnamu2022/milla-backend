<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('attendance_rules', function (Blueprint $table) {
      $table->string('description', 255)->nullable()->after('code');
    });
  }

  public function down(): void
  {
    Schema::table('attendance_rules', function (Blueprint $table) {
      $table->dropColumn('description');
    });
  }
};

