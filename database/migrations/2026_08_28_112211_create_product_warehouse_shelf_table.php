<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_warehouse_shelf', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_warehouse_stock_id');
            $table->unsignedBigInteger('product_shelf_id');
            $table->string('position', 50)->nullable();
            $table->timestamps();

            $table->foreign('product_warehouse_stock_id')->references('id')->on('product_warehouse_stock')->onDelete('cascade');
            $table->foreign('product_shelf_id')->references('id')->on('product_shelves')->onDelete('cascade');

            $table->unique(['product_warehouse_stock_id', 'product_shelf_id'], 'unique_product_shelf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_warehouse_shelf');
    }
};
