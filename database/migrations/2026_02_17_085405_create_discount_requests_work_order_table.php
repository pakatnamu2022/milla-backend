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
    Schema::create('discount_requests_work_order', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_work_order_id')->nullable()->constrained('ap_work_orders')->onDelete('set null');
      $table->integer('manager_id');
      $table->integer('approved_id')->nullable();
      $table->unsignedBigInteger('part_labour_id')->nullable();
      $table->string('part_labour_model')->nullable();
      $table->datetime('request_date');
      $table->decimal('requested_discount_percentage', 5);
      $table->decimal('requested_discount_amount', 10);
      $table->datetime('approval_date')->nullable();
      $table->foreign('manager_id')->references('id')->on('usr_users')->onDelete('cascade');
      $table->foreign('approved_id')->references('id')->on('usr_users')->onDelete('cascade');
      $table->enum('type', ['GLOBAL', 'PARTIAL']); // Tipo de descuento solicitado
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('discount_requests_work_order');
  }
};
