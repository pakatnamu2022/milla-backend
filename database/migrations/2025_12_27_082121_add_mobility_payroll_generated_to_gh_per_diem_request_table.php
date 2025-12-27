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
            $table->boolean('mobility_payroll_generated')->default(false)->after('settled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_per_diem_request', function (Blueprint $table) {
            $table->dropColumn('mobility_payroll_generated');
        });
    }
};
