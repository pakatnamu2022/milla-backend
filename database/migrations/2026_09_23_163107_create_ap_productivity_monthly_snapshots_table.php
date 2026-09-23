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
    Schema::create('ap_productivity_monthly_snapshots', function (Blueprint $table) {
      $table->id();

      // Period identification
      $table->unsignedSmallInteger('year')->comment('Year of the snapshot');
      $table->unsignedTinyInteger('month')->comment('Month of the snapshot (1-12)');
      $table->integer('sede_id')->nullable()->comment('NULL = consolidated data (all sedes)');

      // Core metrics
      $table->unsignedInteger('total_technicians')->default(0)->comment('Total active technicians in period');
      $table->decimal('total_billed_hours', 10, 2)->default(0)->comment('Total billed hours');
      $table->decimal('total_standard_hours', 10, 2)->default(0)->comment('Total standard hours (expected)');
      $table->decimal('total_productivity_hours', 10, 2)->default(0)->comment('Total productivity hours (billed - standard)');
      $table->decimal('total_earnings', 12, 2)->default(0)->comment('Total earnings/commissions');
      $table->decimal('average_productivity_percentage', 5, 2)->default(0)->comment('Average productivity percentage');

      // Work order metrics
      $table->unsignedInteger('total_ots_closed')->default(0)->comment('Total closed work orders');
      $table->unsignedInteger('ots_with_labour_charged')->default(0)->comment('OTs with labour charges');
      $table->unsignedInteger('ots_without_labour_charged')->default(0)->comment('OTs without labour charges');

      // Efficiency indicators
      $table->decimal('avg_hours_per_ot', 8, 2)->nullable()->comment('Average hours per work order');
      $table->decimal('billing_rate', 8, 2)->nullable()->comment('Billing rate (billed/standard * 100)');
      $table->decimal('reentry_rate', 8, 2)->nullable()->comment('Reentry rate percentage');
      $table->decimal('labour_coverage_rate', 8, 2)->nullable()->comment('Labour coverage rate (OTs with charge / total OTs * 100)');

      // Status distribution
      $table->unsignedInteger('technicians_exceeded')->default(0)->comment('Technicians with > 100% productivity');
      $table->unsignedInteger('technicians_on_track')->default(0)->comment('Technicians with 99-100% productivity');
      $table->unsignedInteger('technicians_warning')->default(0)->comment('Technicians with 70-99% productivity');
      $table->unsignedInteger('technicians_critical')->default(0)->comment('Technicians with < 70% productivity');

      // Metadata
      $table->timestamp('snapshot_date')->nullable()->comment('When the snapshot was generated');
      $table->unsignedBigInteger('created_by')->nullable()->comment('User who generated the snapshot');
      $table->timestamps();

      // Indexes
      $table->unique(['year', 'month', 'sede_id'], 'unique_period_sede');
      $table->index(['year', 'sede_id'], 'idx_year_sede');
      $table->index('month', 'idx_month');
      $table->index('snapshot_date', 'idx_snapshot_date');

      // Foreign keys
      $table->foreign('sede_id')->references('id')->on('config_sede')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_productivity_monthly_snapshots');
  }
};
