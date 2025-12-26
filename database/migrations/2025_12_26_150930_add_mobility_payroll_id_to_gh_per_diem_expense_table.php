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
        Schema::table('gh_per_diem_expense', function (Blueprint $table) {
            $table->unsignedBigInteger('mobility_payroll_id')->nullable()->after('rejected_at')->comment('ID de la planilla de movilidad');
            $table->foreign('mobility_payroll_id')->references('id')->on('gh_mobility_payroll')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_per_diem_expense', function (Blueprint $table) {
            $table->dropForeign(['mobility_payroll_id']);
            $table->dropColumn('mobility_payroll_id');
        });
    }
};
