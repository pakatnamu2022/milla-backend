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
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->boolean('is_take_ot')->default(false)->after('is_take')->comment('Indica si la cotización ha sido tomada en una orden de trabajo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->dropColumn('is_take_ot');
        });
    }
};
