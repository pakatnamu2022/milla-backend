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
    Schema::create('type_planning_work_order', function (Blueprint $table) {
      $table->id();
      $table->string('code');
      $table->string('description');
      $table->boolean('validate_receipt')->default(true);
      $table->boolean('validate_labor')->default(true);
      $table->enum('type_document', ['INTERNA', 'PAYMENT_RECEIPTS'])->default('INTERNA');
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
    Schema::dropIfExists('type_planning_work_order');
  }
};
