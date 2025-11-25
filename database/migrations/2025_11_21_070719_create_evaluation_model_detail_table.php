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
    Schema::create('gh_evaluation_model_detail', function (Blueprint $table) {
      $table->id();
      $table->foreignId('evaluation_id')->constrained('gh_evaluation');
      $table->string('categories')->comment('Categories included in the evaluation model');
      $table->decimal('leadership_weight', 5, 2)->default(0);
      $table->decimal('self_weight', 5, 2)->default(0);
      $table->decimal('par_weight', 5, 2)->default(0);
      $table->decimal('report_weight', 5, 2)->default(0);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_evaluation_model_detail');
  }
};
