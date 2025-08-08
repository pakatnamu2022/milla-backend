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
            $table->foreign('person_id')->references('id')->on('rrhh_persona');

            $table->integer('chief_id'); // mismo tipo que rrhh_persona.id
            $table->foreign('chief_id')->references('id')->on('rrhh_persona');

            $table->integer('position_id'); // mismo tipo que rrhh_cargo.id
            $table->foreign('position_id')->references('id')->on('rrhh_cargo');

            $table->integer('sede_id'); // mismo tipo que rrhh_cargo.id
            $table->foreign('sede_id')->references('id')->on('config_sede');

            $table->integer('area_id'); // mismo tipo que rrhh_cargo.id
            $table->foreign('area_id')->references('id')->on('rrhh_area');

            $table->foreignId('cycle_id')->constrained('gh_evaluation_cycle');
            $table->foreignId('category_id')->constrained('gh_hierarchical_category');
            $table->foreignId('objective_id')->constrained('gh_evaluation_objective');

            $table->string('person');
            $table->string('chief')->nullable();
            $table->string('position');
            $table->string('sede');
            $table->string('area');
            $table->string('category');
            $table->text('objective');
            $table->decimal('goal');
            $table->decimal('weight')->nullable();
            $table->string('status')->nullable(); // atrasasdo, en camino, en riesgo


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
