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
            $table->unsignedBigInteger('parent_quotation_id')->nullable()->after('id');
            $table->foreign('parent_quotation_id')->references('id')->on('ap_order_quotations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->dropForeign(['parent_quotation_id']);
            $table->dropColumn('parent_quotation_id');
        });
    }
};
