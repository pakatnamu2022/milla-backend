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
        // Fix ap_billing_electronic_documents table
        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            // Change detraccion_total from decimal(12,10) to decimal(12,2)
            $table->decimal('detraccion_total', 12, 2)->nullable()->change();
        });

        // Fix ap_billing_electronic_document_items table
        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            // Change all decimal(12,10) columns to decimal(12,2)
            $table->decimal('cantidad', 12, 2)->change();
            $table->decimal('valor_unitario', 12, 2)->comment('Valor sin IGV')->change();
            $table->decimal('precio_unitario', 12, 2)->comment('Precio con IGV')->change();
            $table->decimal('subtotal', 12, 2)->comment('cantidad * valor_unitario')->change();
            $table->decimal('igv', 12, 2)->change();
            $table->decimal('total', 12, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert ap_billing_electronic_documents table
        Schema::table('ap_billing_electronic_documents', function (Blueprint $table) {
            // Revert detraccion_total to decimal(12,10)
            $table->decimal('detraccion_total', 12, 10)->nullable()->change();
        });

        // Revert ap_billing_electronic_document_items table
        Schema::table('ap_billing_electronic_document_items', function (Blueprint $table) {
            // Revert all columns back to decimal(12,10)
            $table->decimal('cantidad', 12, 10)->change();
            $table->decimal('valor_unitario', 12, 10)->comment('Valor sin IGV')->change();
            $table->decimal('precio_unitario', 12, 10)->comment('Precio con IGV')->change();
            $table->decimal('subtotal', 12, 10)->comment('cantidad * valor_unitario')->change();
            $table->decimal('igv', 12, 10)->change();
            $table->decimal('total', 12, 10)->change();
        });
    }
};
