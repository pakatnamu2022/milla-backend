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
        Schema::table('evaluation_dashboards', function (Blueprint $table) {
            $table->timestamp('full_recalculation_queued_at')->nullable()->after('last_calculated_at');
            $table->index('full_recalculation_queued_at');
        });

        Schema::table('gh_evaluation_person', function (Blueprint $table) {
            $table->index(['evaluation_id', 'wasEvaluated', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluation_dashboards', function (Blueprint $table) {
            $table->dropIndex(['full_recalculation_queued_at']);
            $table->dropColumn('full_recalculation_queued_at');
        });

        Schema::table('gh_evaluation_person', function (Blueprint $table) {
            $table->dropIndex(['evaluation_id', 'wasEvaluated', 'deleted_at']);
        });
    }
};
