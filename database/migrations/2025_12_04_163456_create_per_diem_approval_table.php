<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * *per_diem_approvals**
   * ```
   * - id
   * - per_diem_request_id (foreignId -> per_diem_requests, cascadeOnDelete)
   * - approver_id (foreignId -> users)
   * - approver_type (string) - "direct_manager", "hr_partner", "general_management"
   * - status (string, indexed) - "pending", "approved", "rejected"
   * - comments (text, nullable)
   * - approved_at (timestamp, nullable)
   * - timestamps
   * ```
   */
  public function up(): void
  {
    Schema::create('per_diem_approval', function (Blueprint $table) {
      $table->id();
      $table->foreignId('per_diem_request_id')->comment('Reference to the per diem request')->constrained('per_diem_request')->cascadeOnDelete();
      $table->foreignId('approver_id')->comment('Reference to the approver user')->constrained('users');
      $table->string('approver_type')->comment('Type of approver, e.g., direct_manager, hr_partner, general_management');
      $table->string('status')->index()->comment('Approval status, e.g., pending, approved, rejected');
      $table->text('comments')->nullable()->comment('Comments from the approver');
      $table->timestamp('approved_at')->nullable()->comment('Timestamp when the approval was made');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('per_diem_approval');
  }
};
