<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * expense_types
   * - id
   * - parent_id (foreignId -> expense_types, nullable) ← NUEVO
   * - code (string, unique)
   * - name (string)
   * - description (text, nullable)
   * - requires_receipt (boolean, default true)
   * - active (boolean, default true)
   * - order (integer, default 0)
   * - level (integer, default 0) ← NUEVO (0=padre, 1=hijo)
   * - timestamps
   */
  public function up(): void
  {
    Schema::create('gh_expense_type', function (Blueprint $table) {
      $table->id();
      $table->foreignId('parent_id')->comment('Reference to the parent expense type, if applicable')->nullable()->constrained('gh_expense_type')->onDelete('cascade');
      $table->string('code')->comment('e.g., meals, accommodation, local_transport');
      $table->string('name')->comment('e.g., Meals, Accommodation, Local Transportation');
      $table->text('description')->nullable()->comment('Detailed description of the expense type');
      $table->boolean('requires_receipt')->default(true)->comment('Indicates if a receipt is required for this expense type');
      $table->boolean('active')->default(true)->comment('Indicates if the expense type is active');
      $table->integer('order')->default(0)->comment('Order for displaying the expense types');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_expense_type');
  }
};
