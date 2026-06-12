<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gh_payroll_loan_extra_discounts', function (Blueprint $table) {
            $table->date('scheduled_date')->nullable()->after('loan_id');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_loan_extra_discounts', function (Blueprint $table) {
            $table->dropColumn('scheduled_date');
        });
    }
};