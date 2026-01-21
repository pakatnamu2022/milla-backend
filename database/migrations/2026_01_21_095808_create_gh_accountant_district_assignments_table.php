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
        Schema::create('gh_accountant_district_assignments', function (Blueprint $table) {
            $table->id();
            $table->integer('worker_id')->comment('Reference to worker (accountant)');
            $table->foreign('worker_id')->references('id')->on('rrhh_persona')->cascadeOnDelete();
            $table->foreignId('district_id')->comment('Reference to district')->constrained('district')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['worker_id', 'district_id', 'deleted_at'], 'unique_worker_district_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_accountant_district_assignments');
    }
};
