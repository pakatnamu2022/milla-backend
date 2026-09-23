<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('ap_technician_hourly_costs', function (Blueprint $table) {
      $table->id();
      $table->integer('year')->comment('Año del periodo');
      $table->integer('month')->comment('Mes del periodo (1-12)');
      $table->decimal('cost_per_hour', 10, 2)->comment('Costo por hora para este periodo');
      $table->string('notes', 255)->nullable()->comment('Notas del cambio');
      $table->integer('created_by')->nullable();
      $table->timestamps();

      $table->unique(['year', 'month'], 'unique_period');
      $table->index(['year', 'month'], 'idx_year_month');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_technician_hourly_costs');
  }
};
