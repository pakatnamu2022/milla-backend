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
    Schema::create('gh_payroll_work_types', function (Blueprint $table) {
      $table->id();
      $table->string('code', 10)->unique()->comment('Unique code: DT, NT, DF');
      $table->string('name', 100)->comment('Descriptive name');
      $table->string('description', 255)->nullable()->comment('Optional description');
      $table->decimal('multiplier', 5, 4)->default(1.0000)->comment('Multiplier factor');
      $table->integer('base_hours')->default(8)->comment('Base hours per shift');
      $table->boolean('is_extra_hours')->default(false)->comment('Is overtime');
      $table->boolean('is_night_shift')->default(false)->comment('Is night shift');
      $table->boolean('is_holiday')->default(false)->comment('Is holiday');
      $table->boolean('is_sunday')->default(false)->comment('Is Sunday');
      $table->boolean('active')->default(true)->comment('Active status');
      $table->integer('order')->default(0)->comment('Display order');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_payroll_work_types');
  }
};
