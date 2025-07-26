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
        Schema::create('gh_evaluation_person_cycle_detail', function (Blueprint $table) {
            $table->id();
            $table->integer('person_id'); // mismo tipo que rrhh_persona.id
            $table->foreign('person_id')
                ->references('id')
                ->on('rrhh_persona')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreignId('evaluation_cycle_id')->constrained('gh_evaluation_cycle')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->integer('cargo_id'); // mismo tipo que rrhh_cargo.id
            $table->foreign('cargo_id')
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
        Schema::dropIfExists('gh_evaluation_person_cycle_detail');
    }
};
