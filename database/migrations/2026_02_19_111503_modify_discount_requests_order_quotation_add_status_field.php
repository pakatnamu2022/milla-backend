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
        Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
            // Renombrar approved_id a reviewed_by_id
            $table->renameColumn('approved_id', 'reviewed_by_id');
            // Renombrar approval_date a review_date
            $table->renameColumn('approval_date', 'review_date');
            // Agregar campo status
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('item_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_requests_order_quotation', function (Blueprint $table) {
            // Revertir los cambios
            $table->renameColumn('reviewed_by_id', 'approved_id');
            $table->renameColumn('review_date', 'approval_date');
            $table->dropColumn('status');
        });
    }
};
