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
    Schema::dropIfExists('ap_work_order_assign_operator');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::create('ap_work_order_assign_operator', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('work_order_id');
      $table->integer('group_number')->nullable();
      $table->unsignedBigInteger('operator_id')->nullable();
      $table->unsignedBigInteger('registered_by')->nullable();
      $table->string('status')->nullable();
      $table->text('observations')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('work_order_id')
        ->references('id')
        ->on('ap_work_orders')
        ->onDelete('cascade');

      $table->foreign('operator_id')
        ->references('id')
        ->on('rrhh_persona')
        ->onDelete('set null');

      $table->foreign('registered_by')
        ->references('id')
        ->on('usr_users')
        ->onDelete('set null');
    });
  }
};
