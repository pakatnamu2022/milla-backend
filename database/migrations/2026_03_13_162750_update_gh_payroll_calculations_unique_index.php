<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new unique key FIRST (it covers period_id as prefix, keeping the FK intact),
        // then drop the old one. Order matters: MySQL won't allow dropping an index that
        // is the sole support for a FK unless a replacement already exists.
        Schema::table('gh_payroll_calculations', function (Blueprint $table) {
            $table->unique(['period_id', 'worker_id', 'biweekly'], 'unique_period_worker_biweekly');
        });
        Schema::table('gh_payroll_calculations', function (Blueprint $table) {
            $table->dropUnique('unique_period_worker');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_calculations', function (Blueprint $table) {
            $table->unique(['period_id', 'worker_id'], 'unique_period_worker');
        });
        Schema::table('gh_payroll_calculations', function (Blueprint $table) {
            $table->dropUnique('unique_period_worker_biweekly');
        });
    }
};
