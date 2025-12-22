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
    Schema::table('gh_per_diem_approval', function (Blueprint $table) {
      $table->dropColumn('approver_type');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_approval', function (Blueprint $table) {
      $table->integer('approver_type')->comment('Type of approver, e.g.,0 - direct_manager, 1 - hr_partner, 2 - general_management');
    });
  }
};
