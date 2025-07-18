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
        Schema::create('gh_hierarchical_category_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hierarchical_category_id')->constrained('gh_hierarchical_category')->cascadeOnDelete();
            $table->integer('position_id'); // mismo tipo que rrhh_cargo.id
            $table->foreign('position_id')
                ->references('id')
                ->on('rrhh_cargo')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_hierarchical_category_detail');
    }
};
