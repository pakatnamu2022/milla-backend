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
    Schema::create('scrum_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('project_id')->constrained('scrum_projects')->cascadeOnDelete();
      $table->foreignId('sprint_id')->nullable()->constrained('scrum_sprints')->nullOnDelete();
      $table->foreignId('parent_id')->nullable()->constrained('scrum_items')->nullOnDelete();
      $table->enum('type', ['tarea', 'historia', 'funcion', 'solicitud', 'error'])->default('tarea');
      $table->string('title');
      $table->text('description')->nullable();
      $table->enum('status', ['backlog', 'por_hacer', 'en_progreso', 'en_revision', 'hecho'])->default('backlog');
      $table->enum('priority', ['alta', 'media', 'baja'])->default('media');

      $table->integer('assigned_to')->nullable();
      $table->foreign('assigned_to')->references('id')->on('usr_users')->cascadeOnDelete();

      $table->integer('created_by');
      $table->foreign('created_by')->references('id')->on('usr_users')->cascadeOnDelete();


      $table->unsignedSmallInteger('story_points')->nullable();
      $table->decimal('estimated_hours', 6, 2)->nullable();
      $table->decimal('actual_hours', 6, 2)->nullable();
      $table->unsignedInteger('order')->default(0);
      $table->date('due_date')->nullable();
      $table->timestamp('closed_at')->nullable();
      $table->timestamps();

      $table->index(['project_id', 'status']);
      $table->index(['sprint_id', 'status']);
      $table->index(['sprint_id', 'order']);
      $table->index('assigned_to');
      $table->index('parent_id');
      $table->index('priority');
      $table->index('type');
      $table->index('closed_at');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('scrum_items');
  }
};
