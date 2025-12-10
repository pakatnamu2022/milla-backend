<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * gh_per_diem_rate
   * - id
   * - per_diem_policy_id (foreignId -> per_diem_policies) ← NUEVO
   * - destination (string, indexed)
   * - per_diem_category_id (foreignId)
   * - expense_type_id (foreignId)
   * - daily_amount (decimal 8,2)
   * - active (boolean, default true)
   * - timestamps
   *
   * Unique index: ['per_diem_policy_id', 'destination', 'per_diem_category_id', 'expense_type_id']
   */
  public function up(): void
  {
    Schema::create('gh_per_diem_rate', function (Blueprint $table) {
      $table->id();
      $table->foreignId('per_diem_policy_id')->comment('Reference to the per diem policy')->constrained('gh_per_diem_policy')->onDelete('cascade');
      $table->foreignId('district_id')->comment('Reference to the district as destination')->constrained('district')->cascadeOnDelete();
      $table->foreignId('per_diem_category_id')->comment('Reference to the per diem category')->constrained('gh_per_diem_category')->onDelete('cascade');
      $table->foreignId('expense_type_id')->comment('Reference to the expense type')->constrained('gh_expense_type')->onDelete('cascade');
      $table->decimal('daily_amount')->comment('Daily per diem amount for the specified destination, category, and expense type');
      $table->boolean('active')->default(true)->comment('Indicates if the per diem rate is active');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_per_diem_rate');
  }
};
