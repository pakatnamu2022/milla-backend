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
        Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
            $table->unsignedBigInteger('internal_note_id')->nullable()->after('electronic_document_id');
            $table->foreign('internal_note_id')->references('id')->on('ap_internal_notes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_vehicle_purchase_order_migration_log', function (Blueprint $table) {
            $table->dropForeign(['internal_note_id']);
            $table->dropColumn('internal_note_id');
        });
    }
};
