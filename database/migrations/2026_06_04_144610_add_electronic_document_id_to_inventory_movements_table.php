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
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('electronic_document_id')->nullable()->after('reference_id');
            $table->foreign('electronic_document_id')->references('id')->on('ap_billing_electronic_documents')->nullOnDelete();
            $table->index('electronic_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['electronic_document_id']);
            $table->dropIndex(['electronic_document_id']);
            $table->dropColumn('electronic_document_id');
        });
    }
};
