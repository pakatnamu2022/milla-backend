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
        Schema::table('ap_work_order_parts', function (Blueprint $table) {
            $table->dropColumn('is_delivered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_work_order_parts', function (Blueprint $table) {
            $table->boolean('is_delivered')->default(false)->after('registered_by');
        });
    }
};
