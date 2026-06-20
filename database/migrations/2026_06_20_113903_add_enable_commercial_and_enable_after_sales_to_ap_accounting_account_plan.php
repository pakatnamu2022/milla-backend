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
        Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
            $table->boolean('enable_commercial')->default(1)->after('status');
            $table->boolean('enable_after_sales')->default(1)->after('enable_commercial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ap_accounting_account_plan', function (Blueprint $table) {
            $table->dropColumn(['enable_commercial', 'enable_after_sales']);
        });
    }
};
