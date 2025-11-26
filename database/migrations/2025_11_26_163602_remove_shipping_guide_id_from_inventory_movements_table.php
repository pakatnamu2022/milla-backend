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
            // Drop foreign key if exists
            $table->dropForeign(['shipping_guide_id']);
            // Drop column
            $table->dropColumn('shipping_guide_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Add column back
            $table->foreignId('shipping_guide_id')->nullable()->after('warehouse_destination_id')->constrained('shipping_guides')->onDelete('set null');
        });
    }
};
