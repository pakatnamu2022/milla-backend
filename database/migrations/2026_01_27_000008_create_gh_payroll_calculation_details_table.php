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
    Schema::create('gh_payroll_calculation_details', function (Blueprint $table) {
      $table->id();
      $table->string('concept_code', 20)->comment('Concept code snapshot');
      $table->string('concept_name', 150)->comment('Concept name snapshot');
      $table->enum('type', ['EARNING', 'DEDUCTION', 'EMPLOYER_CONTRIBUTION', 'INFO'])->comment('Concept type');
      $table->string('formula_used', 1000)->nullable()->comment('Formula used');
      $table->json('variables_snapshot')->nullable()->comment('Variables used');
      $table->decimal('calculated_amount', 12, 2)->default(0)->comment('Calculated amount');
      $table->decimal('final_amount', 12, 2)->default(0)->comment('Final amount');
      $table->integer('calculation_order')->default(0)->comment('Calculation order');
      $table->timestamps();

      // Foreign keys
      $table->foreignId('calculation_id')->comment('Calculation ID')->constrained('gh_payroll_calculations')->onDelete('cascade');
      $table->foreignId('concept_id')->comment('Concept ID')->constrained('gh_payroll_concepts')->onDelete('restrict');

      // Index for faster queries
      $table->index(['calculation_id', 'type']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_calculation_details');
  }
};
