<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_activities', function (Blueprint $table) {
      $table->id();
      $table->string('activity_type', 100);
      $table->string('name', 200);
      $table->text('description')->nullable();
      $table->text('objective')->nullable();
      $table->string('responsible', 150)->nullable();
      $table->string('channel', 150)->nullable();
      $table->date('start_date')->nullable();
      $table->date('end_date')->nullable();
      $table->decimal('estimated_amount', 14, 2)->default(0);
      $table->enum('status', ['planned', 'in_progress', 'executed', 'cancelled'])->default('planned');
      $table->text('notes')->nullable();
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreignId('budget_id')->constrained('ap_mkt_budgets');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreignId('supplier_id')->nullable()->constrained('business_partners')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('usr_users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_activities');
  }
};
