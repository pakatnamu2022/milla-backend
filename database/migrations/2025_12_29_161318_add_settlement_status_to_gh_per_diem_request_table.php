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
            $table->string('settlement_status')->nullable()->after('settled')->comment('Status of the settlement: pending, submitted, approved, rejected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_per_diem_request', function (Blueprint $table) {
            $table->dropColumn('settlement_status');
        });
    }
};
