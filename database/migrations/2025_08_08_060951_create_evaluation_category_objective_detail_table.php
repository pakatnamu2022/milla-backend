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
        Schema::create('gh_evaluation_category_objective_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('gh_evaluation_objective', 'id', 'fk_evaluation_objective_det');
            $table->foreignId('category_id')->constrained('gh_hierarchical_category', 'id', 'fk_hierarchical_category_det');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_evaluation_category_objective_detail');
    }
};
