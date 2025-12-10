<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   * per_diem_policies
   * - id
   * - version (string) - "V1-2024", "V2-2025"
   * - name (string) - "Política de Viáticos 2024"
   * - effective_from (date) - Desde cuándo aplica
   * - effective_to (date, nullable) - Hasta cuándo (null = vigente)
   * - is_current (boolean) - Solo una puede ser true
   * - document_path (string, nullable) - PDF de la política
   * - notes (text, nullable)
   * - created_by (foreignId -> usr_users)
   * - timestamps
   */
  public function up(): void
  {
    Schema::create('gh_per_diem_policy', function (Blueprint $table) {
      $table->id();
      $table->string('version')->comment('e.g., V1-2024, V2-2025');
      $table->string('name')->comment('e.g., Política de Viáticos 2024');
      $table->date('effective_from')->comment('Start date for the policy validity');
      $table->date('effective_to')->nullable()->comment('End date for the policy validity (null = current)');
      $table->boolean('is_current')->default(false)->comment('Indicates if this is the current active policy');
      $table->string('document_path')->nullable()->comment('Path to the PDF document of the policy');
      $table->text('notes')->nullable()->comment('Additional notes about the policy');
      $table->integer('created_by')->comment('Reference to the user who created the policy');
      $table->foreign('created_by')->references('id')->on('usr_users')->onDelete('cascade');
      $table->timestamps();
      $table->softDeletes();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('gh_per_diem_policy');
  }
};
