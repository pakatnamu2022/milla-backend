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
    Schema::create('gh_payroll_concepts', function (Blueprint $table) {
      $table->id();
      $table->string('code', 20)->unique()->comment('Unique code');
      $table->string('name', 150)->comment('Concept name');
      $table->string('description', 500)->nullable()->comment('Description');
      $table->enum('type', ['EARNING', 'DEDUCTION', 'EMPLOYER_CONTRIBUTION', 'INFO'])->comment('Concept type');
      $table->enum('category', [
        'BASE_SALARY',
        'OVERTIME',
        'BONUSES',
        'ALLOWANCES',
        'COMMISSIONS',
        'SOCIAL_SECURITY',
        'TAXES',
        'LOANS',
        'OTHER_DEDUCTIONS',
        'OTHER_EARNINGS',
        'EMPLOYER_TAXES',
        'INFORMATIVE'
      ])->comment('Concept category');
      $table->string('formula', 1000)->nullable()->comment('Calculation formula');
      $table->text('formula_description')->nullable()->comment('Human-readable description');
      $table->boolean('is_taxable')->default(true)->comment('Is taxable');
      $table->integer('calculation_order')->default(0)->comment('Calculation order');
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
    Schema::dropIfExists('gh_payroll_concepts');
  }
};
