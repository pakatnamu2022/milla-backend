<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('ap_assignment_leadership_periods', function (Blueprint $table) {
      $table->tinyInteger('hierarchy')->default(0)->after('status');
    });
  }

  public function down(): void
  {
    Schema::table('ap_assignment_leadership_periods', function (Blueprint $table) {
      $table->dropColumn('hierarchy');
    });
  }
};
