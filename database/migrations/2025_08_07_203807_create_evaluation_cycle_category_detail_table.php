<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gh_evaluation_cycle_category_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')
                ->constrained('gh_evaluation_cycle', 'id', 'fk_cycle_category_cycle');
            $table->foreignId('hierarchical_category_id')
                ->constrained('gh_hierarchical_category', 'id', 'fk_cycle_category_hierarchical');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_evaluation_cycle_category_detail');
    }
};
