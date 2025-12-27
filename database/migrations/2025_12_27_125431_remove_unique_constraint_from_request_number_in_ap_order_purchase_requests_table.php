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
        Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
            $table->dropUnique(['request_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_purchase_requests', function (Blueprint $table) {
            $table->unique('request_number');
        });
    }
};
