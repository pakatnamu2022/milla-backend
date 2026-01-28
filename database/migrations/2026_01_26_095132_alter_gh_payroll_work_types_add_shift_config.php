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
        Schema::table('gh_payroll_work_types', function (Blueprint $table) {
            $table->time('shift_start_time')
                ->nullable()
                ->after('base_hours')
                ->comment('Shift start time (e.g., 07:00:00)');
            $table->decimal('shift_duration_hours', 5, 2)
                ->nullable()
                ->after('shift_start_time')
                ->comment('Total shift duration in hours (e.g., 12.00)');
            $table->decimal('nocturnal_base_multiplier', 5, 4)
                ->default(1.0000)
                ->after('shift_duration_hours')
                ->comment('Base multiplier applied to ALL segments (e.g., 1.35 for NT)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gh_payroll_work_types', function (Blueprint $table) {
            $table->dropColumn([
                'shift_start_time',
                'shift_duration_hours',
                'nocturnal_base_multiplier',
            ]);
        });
    }
};
