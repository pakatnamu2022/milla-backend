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
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->foreignId('per_diem_policy_id')->after('code')->constrained('gh_per_diem_policy', 'id');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_request', function (Blueprint $table) {
      $table->dropForeign(['per_diem_policy_id']);
      $table->dropColumn('per_diem_policy_id');
    });
  }
};
