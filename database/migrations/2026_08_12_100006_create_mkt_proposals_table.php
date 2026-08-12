<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('mkt_proposals', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('activity_id');
      $table->unsignedBigInteger('supplier_id');
      $table->unsignedBigInteger('currency_id');
      $table->decimal('amount', 14, 2)->default(0);
      $table->text('description')->nullable();
      $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
      $table->text('notes')->nullable();
      $table->unsignedBigInteger('reviewed_by')->nullable();
      $table->timestamp('reviewed_at')->nullable();
      $table->unsignedBigInteger('created_by')->nullable();
      $table->unsignedBigInteger('updated_by')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreign('activity_id')->references('id')->on('mkt_activities')->cascadeOnDelete();
      $table->foreign('supplier_id')->references('id')->on('business_partners');
      $table->foreign('currency_id')->references('id')->on('type_currency');
      $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('mkt_proposals');
  }
};
