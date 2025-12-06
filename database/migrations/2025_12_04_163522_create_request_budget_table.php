<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * *request_budgets** (Budget breakdown by expense type)
   * ```
   * - id
   * - per_diem_request_id (foreignId -> per_diem_requests, cascadeOnDelete)
   * - expense_type_id (foreignId -> expense_types)
   * - daily_amount (decimal 8,2)
   * - days (integer)
   * - total (decimal 8,2)
   * - timestamps
   * ```
   */
  public function up(): void
  {
    Schema::create('request_budget', function (Blueprint $table) {
      $table->id();
      $table->foreignId('per_diem_request_id')->comment('Reference to the per diem request')->constrained('per_diem_requests')->cascadeOnDelete();
      $table->foreignId('expense_type_id')->comment('Reference to the expense type')->constrained('expense_types');
      $table->decimal('daily_amount')->comment('Daily amount allocated for this expense type');
      $table->integer('days')->comment('Number of days for this expense type');
      $table->decimal('total')->comment('Total amount for this expense type (daily_amount * days)');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('request_budget');
  }
};
