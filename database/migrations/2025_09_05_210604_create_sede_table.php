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
    Schema::create('sede', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('abbreviation');
      $table->string('address');
      $table->foreignId('company_id')
        ->constrained('companies')
        ->onDelete('cascade');
      $table->foreignId('district_id')
        ->constrained('district')
        ->onDelete('cascade');
      $table->foreignId('province_id')
        ->constrained('province')
        ->onDelete('cascade');
      $table->foreignId('department_id')
        ->constrained('department')
        ->onDelete('cascade');
      $table->boolean('status')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('sede');
  }
};
