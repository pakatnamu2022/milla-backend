<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('accounts_receivable', function (Blueprint $table) {
      $table->id();
      $table->string('company', 50);
      $table->integer('sede_id')->nullable();
      $table->foreign('sede_id')->references('id')->on('config_sede')->nullOnDelete();

      $table->string('seller')->nullable();
      $table->string('cashier')->nullable();
      $table->string('document_number', 100);
      $table->string('client_id', 20)->nullable();
      $table->string('client_name')->nullable();
      $table->string('client_id_real', 20)->nullable();
      $table->string('client_name_real')->nullable();

      $table->date('document_date')->nullable();
      $table->date('document_due_date')->nullable();
      $table->unsignedSmallInteger('due_year')->nullable();
      $table->string('due_month', 20)->nullable();
      $table->integer('overdue_days')->nullable();
      $table->string('overdue_status', 30)->nullable();

      $table->char('currency', 3)->nullable();
      $table->decimal('exchange_rate', 15, 5)->nullable();
      $table->decimal('amount', 15, 5)->nullable();
      $table->decimal('balance', 15, 5)->nullable();

      $table->string('branch')->nullable();
      $table->text('observations')->nullable();
      $table->date('collection_date')->nullable();

      $table->timestamp('synced_at')->nullable();
      $table->timestamps();

      $table->unique(['company', 'document_number']);
      $table->index('sede_id');
      $table->index('company');
      $table->index('overdue_status');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('accounts_receivable');
  }
};
