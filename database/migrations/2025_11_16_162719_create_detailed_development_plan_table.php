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
        Schema::create('detailed_development_plan', function (Blueprint $table) {
            $table->id();
            $table->string('description', 500);
            $table->boolean('boss_confirms')->default(false);
            $table->boolean('worker_confirms')->default(false);
            $table->boolean('boss_confirms_completion')->default(false);
            $table->boolean('worker_confirms_completion')->default(false);
            $table->integer('worker_id');
            $table->foreign('worker_id')->references('id')->on('rrhh_persona');
            $table->integer('boss_id');
            $table->foreign('boss_id')->references('id')->on('rrhh_persona');
            $table->foreignId('gh_evaluation_id')->constrained('gh_evaluation')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detailed_development_plan');
    }
};
