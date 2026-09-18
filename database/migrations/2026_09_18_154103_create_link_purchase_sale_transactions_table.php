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
        Schema::create('link_purchase_sale_transactions', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->unsignedBigInteger('billing_electronic_document_item_id');
            $table->unsignedBigInteger('purchase_order_item_id');

            // Cantidad asociada
            $table->decimal('cantidad', 10, 4);

            // Status: active, reverted
            $table->enum('status', ['active', 'reverted'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('billing_electronic_document_item_id', 'fk_link_billing_item')
                ->references('id')
                ->on('ap_billing_electronic_document_items')
                ->onDelete('cascade');

            $table->foreign('purchase_order_item_id', 'fk_link_purchase_item')
                ->references('id')
                ->on('ap_purchase_order_item')
                ->onDelete('cascade');

            // Indexes
            $table->index('status', 'idx_lpst_status');
            $table->index(['billing_electronic_document_item_id', 'status'], 'idx_lpst_billing_item_status');
            $table->index(['purchase_order_item_id', 'status'], 'idx_lpst_purchase_item_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_purchase_sale_transactions');
    }
};
