<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Makes concept_id nullable to support attendance-based calculations
     * that don't have a predefined concept
     */
    public function up(): void
    {
        Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['concept_id']);

            // Make concept_id nullable
            $table->foreignId('concept_id')->nullable()->change();

            // Re-add the foreign key constraint allowing null values
            $table->foreign('concept_id')
                ->references('id')
                ->on('gh_payroll_concepts')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_payroll_calculation_details', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['concept_id']);

            // Make concept_id NOT nullable again
            $table->foreignId('concept_id')->nullable(false)->change();

            // Re-add the foreign key constraint
            $table->foreign('concept_id')
                ->references('id')
                ->on('gh_payroll_concepts')
                ->onDelete('restrict');
        });
    }
};
