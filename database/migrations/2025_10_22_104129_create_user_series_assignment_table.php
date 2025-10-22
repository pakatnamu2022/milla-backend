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
    Schema::create('user_series_assignment', function (Blueprint $table) {
      $table->id();
      $table->integer('worker_id');
      $table->foreign('worker_id')->references('id')->on('usr_users');
      $table->foreignId('voucher_id')
        ->constrained('assign_sales_series')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('user_series_assignment');
  }
};
