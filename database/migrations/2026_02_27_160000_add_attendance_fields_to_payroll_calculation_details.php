<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   *
   * Adds fields to support attendance-based payroll calculations
   * Fields for HN, HED, HEN, and other attendance codes
   */
  public function up(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      // Add fields for base worker info at calculation time
      $table->decimal('salary', 10, 2)->default(0)->after('worker_id')->comment('Base salary snapshot');
      $table->decimal('shift_hours', 5, 2)->default(8)->after('salary')->comment('Work shift hours snapshot');
      $table->decimal('base_hour_value', 10, 2)->default(0)->after('shift_hours')->comment('Base hour value (salary/30/shift_hours)');

      // Add contributions total (separate from earnings/deductions)
      $table->decimal('total_contributions', 10, 2)->default(0)->after('total_deductions')->comment('Total employer contributions (AFP, ISSS, etc.)');

      // Add payment tracking
      $table->timestamp('paid_at')->nullable()->after('approved_at')->comment('Payment timestamp');
      $table->integer('paid_by')->nullable()->after('paid_at')->comment('User who processed payment');
      $table->foreign('paid_by')->references('id')->on('usr_users')->onDelete('set null');
    });

    Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
      // Add category for different types of concepts
      $table->enum('category', ['ATTENDANCE', 'BONUS', 'TAX', 'INSURANCE', 'LOAN', 'OTHER'])
        ->default('OTHER')
        ->after('type')
        ->comment('Category of concept');

      // Fields specific to attendance calculations
      $table->string('hour_type', 20)->nullable()->after('category')->comment('Hour type: DIURNO, NOCTURNO');
      $table->decimal('hours', 5, 2)->nullable()->after('hour_type')->comment('Hours per day/event');
      $table->integer('days_worked')->default(0)->after('hours')->comment('Days worked with this code');
      $table->decimal('multiplier', 5, 4)->default(1)->after('days_worked')->comment('Multiplier from attendance rule');
      $table->boolean('use_shift')->default(true)->after('multiplier')->comment('Uses worker shift hours');

      // Fields for tax/insurance/loan calculations
      $table->decimal('base_amount', 10, 2)->nullable()->after('use_shift')->comment('Base amount for calculation');
      $table->decimal('rate', 5, 4)->nullable()->after('base_amount')->comment('Rate or percentage (e.g., 0.13 for 13%)');

      // Hour value calculated (includes night surcharge and multiplier)
      $table->decimal('hour_value', 10, 2)->default(0)->after('rate')->comment('Calculated hour value');

      // Rename calculated_amount and final_amount to be clearer
      $table->decimal('amount', 10, 2)->default(0)->after('hour_value')->comment('Total amount (positive for earnings, negative for deductions)');
    });

    // Drop the old calculated_amount and final_amount columns
    Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
      $table->dropColumn(['calculated_amount', 'final_amount']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('gh_payroll_calculations', function (Blueprint $table) {
      $table->dropForeign(['paid_by']);
      $table->dropColumn(['salary', 'shift_hours', 'base_hour_value', 'total_contributions', 'paid_at', 'paid_by']);
    });

    Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
      $table->decimal('calculated_amount', 12, 2)->default(0);
      $table->decimal('final_amount', 12, 2)->default(0);
    });

    Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
      $table->dropColumn([
        'category',
        'hour_type',
        'hours',
        'days_worked',
        'multiplier',
        'use_shift',
        'base_amount',
        'rate',
        'hour_value',
        'amount',
      ]);
    });
  }
};