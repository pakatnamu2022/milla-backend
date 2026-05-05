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
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('vehicle_inspection_id')->nullable()->after('order_quotation_id');
            $table->foreign('vehicle_inspection_id')->references('id')->on('ap_vehicle_inspection')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->dropForeign(['vehicle_inspection_id']);
            $table->dropColumn('vehicle_inspection_id');
        });
    }
};
