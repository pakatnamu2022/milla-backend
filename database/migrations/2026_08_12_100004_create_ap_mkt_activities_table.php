<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('ap_mkt_activities', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('budget_id');
      $table->string('activity_type', 100);
      $table->string('name', 200);
      $table->text('description')->nullable();
      $table->text('objective')->nullable();
      $table->string('responsible', 150)->nullable();
      $table->string('channel', 150)->nullable();
      $table->date('start_date')->nullable();
      $table->date('end_date')->nullable();
      $table->unsignedBigInteger('currency_id');
      $table->decimal('estimated_amount', 14, 2)->default(0);
      $table->unsignedBigInteger('supplier_id')->nullable();
      $table->enum('status', ['planned', 'in_progress', 'executed', 'cancelled'])->default('planned');
      $table->text('notes')->nullable();
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('budget_id')->references('id')->on('ap_mkt_budgets')->cascadeOnDelete();
      $table->foreign('currency_id')->references('id')->on('type_currency');
      $table->foreign('supplier_id')->references('id')->on('business_partners')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_activities');
  }
};
