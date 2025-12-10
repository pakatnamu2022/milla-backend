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
    Schema::create('ap_work_order_items', function (Blueprint $table) {
      $table->id();

      // Item info
      $table->integer('group_number')->comment('Número de grupo para agrupar ítems relacionados');

      // Relations
      $table->foreignId('work_order_id')->comment('Orden de trabajo principal')
        ->constrained('ap_work_orders')->onDelete('cascade');

      $table->foreignId('type_planning_id')->constrained('ap_post_venta_masters')->onDelete('cascade');
      $table->text('description')->comment('Descripción del trabajo a realizar');
      $table->timestamps();
      $table->softDeletes();

      // Indexes
      $table->index(['work_order_id', 'group_number']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('ap_work_order_items');
  }
};
