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
        Schema::table('gh_per_diem_request', function (Blueprint $table) {
            $table->string('deposit_voucher_url')->nullable()->after('with_request');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_per_diem_request', function (Blueprint $table) {
            $table->dropColumn('deposit_voucher_url');
        });
    }
};
