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
    Schema::create('assign_sales_series', function (Blueprint $table) {
      $table->id();
      $table->string('series', 4);
      $table->integer('correlative_start');
      $table->boolean('status')->default(true);
      $table->foreignId('type_receipt_id')
        ->constrained('ap_commercial_masters');
      $table->foreignId('type_operation_id')
        ->constrained('ap_commercial_masters');
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('assign_sales_series');
  }
};
