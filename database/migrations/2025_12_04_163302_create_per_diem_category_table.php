<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * *per_diem_categories** (Master table for categories)
   * ```
   * - id
   * - name (string) - "Managers", "Other Employees", etc.
   * - description (text, nullable)
   * - active (boolean, default true)
   * - timestamps
   * ```
   */
  public function up(): void
  {
    Schema::create('gh_per_diem_category', function (Blueprint $table) {
      $table->id();
      $table->string('name')->comment('e.g., "Managers", "Other Employees"');
      $table->text('description')->nullable()->comment('Description of the per diem category');
      $table->boolean('active')->default(true)->comment('Indicates if the category is active');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_per_diem_category');
  }
};
