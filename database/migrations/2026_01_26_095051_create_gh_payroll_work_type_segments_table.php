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
        Schema::create('gh_payroll_work_type_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_type_id')
                ->comment('Work type ID')
                ->constrained('gh_payroll_work_types', 'id')
                ->onDelete('cascade');
            $table->enum('segment_type', ['WORK', 'BREAK'])
                ->default('WORK')
                ->comment('Segment type: WORK or BREAK');
            $table->integer('segment_order')
                ->comment('Execution order within shift');
            $table->decimal('duration_hours', 5, 2)
                ->comment('Duration in hours (e.g., 8.00, 0.75 for 45min)');
            $table->decimal('multiplier', 5, 4)
                ->nullable()
                ->comment('For WORK segments: multiplier to apply');
            $table->string('description', 255)
                ->nullable()
                ->comment('Human-readable description');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['work_type_id', 'segment_order'], 'idx_work_type_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_payroll_work_type_segments');
    }
};
