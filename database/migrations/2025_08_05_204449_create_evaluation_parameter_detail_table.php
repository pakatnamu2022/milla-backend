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
        Schema::create('gh_evaluation_parameter_detail', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->decimal('from', 10);
            $table->decimal('to', 10);
            $table->foreignId('parameter_id')
                ->constrained('gh_evaluation_parameter')
                ->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gh_evaluation_parameter_detail');
    }
};
