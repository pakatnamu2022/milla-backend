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
            $table->boolean('output_generation_warehouse')->default(false)->after('has_invoice_generated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_work_orders', function (Blueprint $table) {
            $table->dropColumn('output_generation_warehouse');
        });
    }
};
