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
        Schema::create('scrum_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('scrum_projects')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 7)->default('#6B7280');
            $table->timestamps();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrum_tags');
    }
};
