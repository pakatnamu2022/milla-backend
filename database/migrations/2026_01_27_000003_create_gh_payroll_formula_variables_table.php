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
    Schema::create('gh_payroll_formula_variables', function (Blueprint $table) {
      $table->id();
      $table->string('code', 50)->unique()->comment('Variable code: RMV, UIT, etc.');
      $table->string('name', 100)->comment('Variable name');
      $table->string('description', 255)->nullable()->comment('Description');
      $table->enum('type', ['FIXED', 'SYSTEM', 'CALCULATED'])->default('FIXED')->comment('Variable type');
      $table->decimal('value', 15, 4)->nullable()->comment('Fixed value');
      $table->string('source_field', 100)->nullable()->comment('Source field if SYSTEM type');
      $table->string('formula', 500)->nullable()->comment('Formula if CALCULATED type');
      $table->boolean('active')->default(true)->comment('Active status');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_formula_variables');
  }
};
