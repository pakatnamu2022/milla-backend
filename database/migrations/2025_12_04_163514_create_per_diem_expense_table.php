<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * *per_diem_expenses**
   * ```
   * - id
   * - per_diem_request_id (foreignId -> per_diem_requests, cascadeOnDelete, indexed)
   * - expense_type_id (foreignId -> expense_types)
   * - expense_date (date, indexed)
   * - concept (string)
   * - receipt_amount (decimal 8,2)
   * - company_amount (decimal 8,2)
   * - employee_amount (decimal 8,2)
   * - receipt_type (string) - "invoice", "ticket", "no_receipt"
   * - receipt_number (string, nullable)
   * - receipt_path (string, nullable)
   * - notes (text, nullable)
   * - validated (boolean, default false)
   * - validated_by (foreignId -> users, nullable)
   * - validated_at (timestamp, nullable)
   * - timestamps
   */
  public function up(): void
  {
    Schema::create('per_diem_expense', function (Blueprint $table) {
      $table->id();
      $table->foreignId('per_diem_request_id')->index()->comment('Reference to the per diem request')->constrained('per_diem_requests')->cascadeOnDelete();
      $table->foreignId('expense_type_id')->comment('Reference to the expense type')->constrained('expense_types');
      $table->date('expense_date')->index()->comment('Date of the expense incurred');
      $table->string('concept')->comment('Concept or description of the expense');
      $table->decimal('receipt_amount', 8, 2)->comment('Amount as per the receipt');
      $table->decimal('company_amount', 8, 2)->comment('Amount covered by the company');
      $table->decimal('employee_amount', 8, 2)->comment('Amount to be covered by the employee');
      $table->string('receipt_type')->comment('Type of receipt, e.g., invoice, ticket, no_receipt');
      $table->string('receipt_number')->nullable()->comment('Receipt number, if applicable');
      $table->string('receipt_path')->nullable()->comment('Path to the receipt document');
      $table->text('notes')->nullable()->comment('Additional notes regarding the expense');
      $table->boolean('validated')->default(false)->comment('Indicates if the expense has been validated');
      $table->foreignId('validated_by')->comment('Reference to the user who validated the expense')->nullable()->constrained('users');
      $table->timestamp('validated_at')->nullable()->comment('Timestamp when the expense was validated');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('per_diem_expense');
  }
};
