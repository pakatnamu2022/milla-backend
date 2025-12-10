<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * *per_diem_requests**
   * ```
   * - id
   * - code (string, unique) - "PDR-2024-0001"
   * - employee_id (foreignId -> usr_users)
   * - company_id (foreignId -> companies)
   * - destination (string)
   * - per_diem_category_id (foreignId -> per_diem_categories)
   * - start_date (date)
   * - end_date (date)
   * - days_count (integer)
   * - purpose (text)
   * - cost_center (string)
   * - status (string, indexed) - "draft", "pending_manager", "pending_hr", "approved", etc.
   * - total_budget (decimal 10,2)
   * - cash_amount (decimal 10,2)
   * - transfer_amount (decimal 10,2)
   * - paid (boolean, default false)
   * - payment_date (date, nullable)
   * - payment_method (string, nullable) - "transfer", "cash"
   * - settled (boolean, default false)
   * - settlement_date (date, nullable)
   * - total_spent (decimal 10,2, default 0)
   * - balance_to_return (decimal 10,2, default 0)
   * - notes (text, nullable)
   * - timestamps
   * - softDeletes
   * ```
   */
  public function up(): void
  {
    Schema::create('gh_per_diem_request', function (Blueprint $table) {
      $table->id();
      $table->string('code')->comment('e.g., PDR-2024-0001, auto-generated unique code for the per diem request');
      $table->integer('employee_id')->comment('Reference to the employee requesting the per diem');
      $table->foreign('employee_id')->references('id')->on('rrhh_persona')->cascadeOnDelete();
      $table->foreignId('company_id')->comment('Reference to the company')->constrained('companies')->cascadeOnDelete();
      $table->string('destination')->comment('Destination for the per diem request');
      $table->foreignId('per_diem_category_id')->comment('Reference to the per diem category')->constrained('gh_per_diem_category')->cascadeOnDelete();
      $table->date('start_date')->comment('Start date of the per diem period');
      $table->date('end_date')->comment('End date of the per diem period');
      $table->integer('days_count')->comment('Number of days for the per diem');
      $table->text('purpose')->comment('Purpose of the trip or expense');
      $table->text('final_result')->comment('Final result or outcome of the trip or expense');
      $table->string('status')->index()->comment('Status of the request, e.g., draft, pending_manager, approved');
      $table->decimal('total_budget', 10, 2)->comment('Total budget allocated for the per diem');
      $table->decimal('cash_amount', 10, 2)->comment('Amount to be paid in cash');
      $table->decimal('transfer_amount', 10, 2)->comment('Amount to be paid via transfer');
      $table->boolean('paid')->default(false)->comment('Indicates if the per diem has been paid');
      $table->date('payment_date')->nullable()->comment('Date when the payment was made');
      $table->string('payment_method')->nullable()->comment('Method of payment, e.g., transfer, cash');
      $table->boolean('settled')->default(false)->comment('Indicates if the per diem has been settled');
      $table->date('settlement_date')->nullable()->comment('Date when the per diem was settled');
      $table->decimal('total_spent', 10, 2)->default(0)->comment('Total amount spent from the per diem');
      $table->decimal('balance_to_return', 10, 2)->default(0)->comment('Balance amount to be returned if any');
      $table->text('notes')->nullable()->comment('Additional notes regarding the per diem request');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_per_diem_request');
  }
};
