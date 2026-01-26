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
    Schema::create('gh_payroll_calculations', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id')->comment('Worker ID');
      $table->integer('sede_id')->nullable()->comment('Sede ID');
      $table->decimal('total_normal_hours')->default(0)->comment('Total normal hours');
      $table->decimal('total_extra_hours_25')->default(0)->comment('Overtime 25%');
      $table->decimal('total_extra_hours_35')->default(0)->comment('Overtime 35%');
      $table->decimal('total_night_hours')->default(0)->comment('Night hours');
      $table->decimal('total_holiday_hours')->default(0)->comment('Holiday hours');
      $table->integer('days_worked')->default(0)->comment('Days worked');
      $table->integer('days_absent')->default(0)->comment('Days absent');
      $table->decimal('gross_salary', 12)->default(0)->comment('Gross salary');
      $table->decimal('total_earnings', 12)->default(0)->comment('Total earnings');
      $table->decimal('total_deductions', 12)->default(0)->comment('Total deductions');
      $table->decimal('net_salary', 12)->default(0)->comment('Net salary');
      $table->decimal('employer_cost', 12)->default(0)->comment('Employer cost');
      $table->enum('status', ['DRAFT', 'CALCULATED', 'APPROVED', 'PAID'])->default('DRAFT')->comment('Calculation status');
      $table->timestamp('calculated_at')->nullable()->comment('Calculation timestamp');
      $table->integer('calculated_by')->nullable()->comment('User who calculated');
      $table->timestamp('approved_at')->nullable()->comment('Approval timestamp');
      $table->integer('approved_by')->nullable()->comment('User who approved');
      $table->timestamps();
      $table->softDeletes();

      // Foreign keys
      $table->foreignId('period_id')->comment('Period ID')->constrained('gh_payroll_periods', 'id')->onDelete('cascade');
      $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
      $table->foreignId('company_id')->nullable()->comment('Company ID')->constrained('companies', 'id')->onDelete('set null');
      $table->foreign('sede_id')->references('id')->on('config_sede')->onDelete('set null');
      $table->foreign('calculated_by')->references('id')->on('usr_users')->onDelete('set null');
      $table->foreign('approved_by')->references('id')->on('usr_users')->onDelete('set null');

      // Unique constraint for period and worker
      $table->unique(['period_id', 'worker_id'], 'unique_period_worker');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_calculations');
  }
};
