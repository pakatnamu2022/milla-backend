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
    Schema::create('electronic_document_internal_notes', function (Blueprint $table) {
      $table->id();
      $table->foreignId('electronic_document_id')->constrained('ap_billing_electronic_documents', 'id', 'fk_elect_doc_internal_notes')->onDelete('cascade');
      $table->foreignId('internal_note_id')->constrained('ap_internal_notes')->onDelete('cascade');
      $table->timestamps();

      // Indexes
      $table->index('electronic_document_id');
      $table->index('internal_note_id');

      // Prevent duplicates
      $table->unique(['electronic_document_id', 'internal_note_id'], 'unique_doc_note');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('electronic_document_internal_notes');
  }
};
