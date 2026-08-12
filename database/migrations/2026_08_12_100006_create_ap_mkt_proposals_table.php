<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('ap_mkt_proposals', function (Blueprint $table) {
      $table->id();
      $table->decimal('amount', 14, 2)->default(0);
      $table->text('description')->nullable();
      $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
      $table->text('notes')->nullable();
      $table->integer('reviewed_by')->nullable();
      $table->integer('created_by')->nullable();
      $table->integer('updated_by')->nullable();
      $table->timestamp('reviewed_at')->nullable();
      $table->timestamps();
      $table->softDeletes();

      $table->foreignId('activity_id')->constrained('ap_mkt_activities');
      $table->foreignId('supplier_id')->constrained('business_partners');
      $table->foreignId('currency_id')->constrained('type_currency');
      $table->foreign('reviewed_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('created_by')->references('id')->on('usr_users')->nullOnDelete();
      $table->foreign('updated_by')->references('id')->on('usr_users')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('ap_mkt_proposals');
  }
};
