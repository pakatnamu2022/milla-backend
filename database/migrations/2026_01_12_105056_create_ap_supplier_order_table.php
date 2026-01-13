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
    Schema::create('ap_supplier_order', function (Blueprint $table) {
      $table->id();
      $table->foreignId('ap_purchase_order_id')->nullable()->constrained('ap_purchase_order')->onDelete('set null');
      $table->foreignId('supplier_id')->constrained('business_partners')->onDelete('cascade');
      $table->integer('sede_id');
      $table->foreign('sede_id')->references('id')->on('config_sede')->onDelete('cascade');
      $table->foreignId('warehouse_id')->constrained('warehouse')->onDelete('cascade');
      $table->foreignId('type_currency_id')->constrained('type_currency')->onDelete('cascade');
      $table->integer('created_by');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('cascade');
      $table->date('order_date');
      $table->string('order_number');
      $table->string('supply_type', 50);
      $table->decimal('total_amount', 15, 4)->default(0);
      $table->decimal('exchange_rate', 15, 6);
      $table->boolean('is_take')->default(false);
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
    Schema::dropIfExists('ap_supplier_order');
  }
};
