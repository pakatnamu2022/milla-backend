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
    Schema::create('gh_payroll_periods', function (Blueprint $table) {
      $table->id();
      $table->string('code', 20)->unique()->comment('Period code: 2026-01');
      $table->string('name', 100)->comment('Period name: January 2026');
      $table->integer('year')->comment('Year');
      $table->integer('month')->comment('Month (1-12)');
      $table->date('start_date')->comment('Start date');
      $table->date('end_date')->comment('End date');
      $table->date('payment_date')->nullable()->comment('Payment date');
      $table->enum('status', ['OPEN', 'PROCESSING', 'CALCULATED', 'APPROVED', 'CLOSED'])->default('OPEN')->comment('Period status');
      $table->timestamps();
      $table->softDeletes();

      // Foreign keys
      $table->foreignId('company_id')->nullable()->comment('Company ID')->constrained('companies', 'id')->onDelete('set null');

      // Unique constraint for year, month, and company
      $table->unique(['year', 'month', 'company_id'], 'unique_period_company');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_periods');
  }
};
