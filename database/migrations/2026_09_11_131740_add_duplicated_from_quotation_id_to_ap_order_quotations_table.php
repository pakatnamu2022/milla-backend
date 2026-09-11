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
            $table->unsignedBigInteger('duplicated_from_quotation_id')->nullable()->after('parent_quotation_id');
            $table->foreign('duplicated_from_quotation_id')->references('id')->on('ap_order_quotations')->onDelete('set null');
            $table->index('duplicated_from_quotation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_order_quotations', function (Blueprint $table) {
            $table->dropForeign(['duplicated_from_quotation_id']);
            $table->dropIndex(['duplicated_from_quotation_id']);
            $table->dropColumn('duplicated_from_quotation_id');
        });
    }
};
