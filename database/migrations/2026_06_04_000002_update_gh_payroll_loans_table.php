<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gh_payroll_loans', function (Blueprint $table) {
            $table->dropForeign(['concept_id']);
            $table->dropColumn('concept_id');
            $table->json('payment_days')->nullable()->after('payment_start');
            $table->decimal('remaining_balance', 12, 2)->default(0)->after('installment_amount');
        });
    }

    public function down(): void
    {
        Schema::table('gh_payroll_loans', function (Blueprint $table) {
            $table->dropColumn(['payment_days', 'remaining_balance']);
            $table->unsignedBigInteger('concept_id')->nullable()->after('id');
            $table->foreign('concept_id')->references('id')->on('general_masters');
        });
    }
};