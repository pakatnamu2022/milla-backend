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
    Schema::create('gh_payroll_schedules', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id')->comment('Worker ID');
      $table->date('work_date')->comment('Work date');
      $table->decimal('hours_worked', 5, 2)->default(0)->comment('Hours worked');
      $table->decimal('extra_hours', 5, 2)->default(0)->comment('Extra hours');
      $table->string('notes', 255)->nullable()->comment('Notes');
      $table->enum('status', ['SCHEDULED', 'WORKED', 'ABSENT', 'VACATION', 'SICK_LEAVE', 'PERMISSION'])->default('SCHEDULED')->comment('Schedule status');
      $table->timestamps();
      $table->softDeletes();

      // Foreign keys
      $table->foreign('worker_id')->references('id')->on('rrhh_persona')->onDelete('cascade');
      $table->foreignId('work_type_id')->comment('Work type ID')->constrained('gh_payroll_work_types', 'id')->onDelete('restrict');
      $table->foreignId('period_id')->comment('Period ID')->constrained('gh_payroll_periods', 'id')->onDelete('cascade');

      // Unique constraint for worker and date
      $table->unique(['worker_id', 'work_date'], 'unique_worker_date');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_schedules');
  }
};
