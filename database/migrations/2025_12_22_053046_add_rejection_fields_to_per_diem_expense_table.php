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
    Schema::table('gh_per_diem_expense', function (Blueprint $table) {
      $table->boolean('rejected')->default(false)->after('validated_at');
      $table->integer('rejected_by')->nullable()->after('rejected');
      $table->timestamp('rejected_at')->nullable()->after('rejected_by');
      $table->text('rejection_reason')->nullable()->after('rejected_at');

      $table->foreign('rejected_by')->references('id')->on('rrhh_persona')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_per_diem_expense', function (Blueprint $table) {
      $table->dropForeign(['rejected_by']);
      $table->dropColumn(['rejected', 'rejected_by', 'rejected_at', 'rejection_reason']);
    });
  }
};
